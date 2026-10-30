<?php

namespace Tests\Feature;

use App\AI\Providers\OpenAiProvider;
use App\Contracts\JdDraftingProvider;
use App\Models\ClientOrganization;
use App\Models\JobDescriptionVersion;
use App\Models\Requirement;
use App\Models\RequirementAssignment;
use App\Models\Role;
use App\Models\User;
use App\Services\UnavailableJdDraftingProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class JobDescriptionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private JobDescriptionVersion $jd;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_assigned_recruiter_can_create_jd_version_linked_to_requirement(): void
    {
        [$requirement, $recruiter] = $this->requirementAndRecruiter();
        $this->actingAs($recruiter)->post(route('job-descriptions.store', $requirement), $this->jdContent())
            ->assertRedirect();

        $jd = JobDescriptionVersion::query()->firstOrFail();
        $this->assertSame($requirement->id, $jd->requirement_id);
        $this->assertSame(1, $jd->version);
        $this->assertSame('Draft', $jd->status);
        $this->assertSame($requirement->salary_ctc, $jd->compensation_information);
        $this->assertDatabaseHas('audit_logs', ['action' => 'jd.created', 'entity_id' => (string) $jd->id]);
    }

    public function test_admin_and_unassigned_recruiter_cannot_create_jd(): void
    {
        [$requirement, $recruiter] = $this->requirementAndRecruiter();
        $unassignedRecruiter = $this->userWithRole('recruiter');
        $this->actingAs($unassignedRecruiter)
            ->post(route('job-descriptions.store', $requirement), $this->jdContent())
            ->assertForbidden();

        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)->get(route('job-descriptions.index', $requirement))->assertForbidden();
        $this->post(route('job-descriptions.store', $requirement), $this->jdContent())->assertForbidden();
        $jd = $this->createJd($requirement, $recruiter);
        $this->actingAs($admin)->get(route('job-descriptions.show', $jd))->assertForbidden();
    }

    public function test_recruiter_can_edit_a_draft_but_cannot_record_client_approval(): void
    {
        [$requirement, $recruiter] = $this->requirementAndRecruiter();
        $jd = $this->createJd($requirement, $recruiter);
        $this->actingAs($recruiter)->patch(route('job-descriptions.update', $jd), array_replace($this->jdContent(), ['title' => 'Revised backend engineer']))
            ->assertRedirect();
        $this->assertSame('Revised backend engineer', $jd->fresh()->title);

        $jd->forceFill(['status' => 'Sent for Client Approval'])->save();
        $this->actingAs($recruiter)
            ->post(route('job-descriptions.client-decision', $jd), ['decision' => 'approved'])
            ->assertForbidden();
        $this->assertSame('Sent for Client Approval', $jd->fresh()->status);
    }

    public function test_recruitment_manager_reviews_and_sends_jd_without_claiming_client_approval(): void
    {
        [$requirement, $recruiter, $manager] = $this->requirementAndRecruiter();
        $jd = $this->createJd($requirement, $recruiter);

        $this->actingAs($recruiter)->post(route('job-descriptions.review', $jd))->assertRedirect();
        $this->assertSame('Recruiter Review', $jd->fresh()->status);
        $this->actingAs($manager)->post(route('job-descriptions.send-for-approval', $jd))->assertRedirect();

        $jd->refresh();
        $this->assertSame('Sent for Client Approval', $jd->status);
        $this->assertSame($manager->id, $jd->reviewed_by);
        $this->assertNotNull($jd->reviewed_at);
        $this->assertNull($jd->approved_at);
        $this->actingAs($manager)->post(route('job-descriptions.client-decision', $jd), ['decision' => 'approved'])->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['action' => 'jd.submitted_for_review', 'entity_id' => (string) $jd->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'jd.sent_for_client_approval', 'entity_id' => (string) $jd->id]);
    }

    public function test_management_records_external_client_approval_as_a_separate_event(): void
    {
        [$requirement, $recruiter, $manager, $management] = $this->sentForClientApproval();

        $this->actingAs($management)->post(route('job-descriptions.client-decision', $this->jd), [
            'decision' => 'approved',
            'client_spoc_reference' => 'Client SPOC email reference',
            'comments' => 'Approved by client over email.',
        ])->assertRedirect();

        $this->assertSame('Approved', $this->jd->fresh()->status);
        $this->assertSame($management->id, $this->jd->fresh()->approval_recorded_by);
        $this->assertSame('Client SPOC email reference', $this->jd->fresh()->approved_by_reference);
        $this->assertDatabaseHas('job_description_client_decisions', [
            'job_description_version_id' => $this->jd->id,
            'client_organization_id' => $requirement->client_organization_id,
            'recorded_by' => $management->id,
            'decision' => 'approved',
            'client_spoc_reference' => 'Client SPOC email reference',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'jd.client_approved', 'entity_id' => (string) $this->jd->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'jd.approved', 'entity_id' => (string) $this->jd->id]);
    }

    public function test_client_changes_request_preserves_previous_version_and_creates_a_new_draft(): void
    {
        [$requirement, $recruiter, $manager, $management] = $this->sentForClientApproval();
        $this->actingAs($management)->post(route('job-descriptions.client-decision', $this->jd), [
            'decision' => 'changes_requested',
            'client_spoc_reference' => 'Client SPOC call note',
            'comments' => 'Please add the required certification.',
        ])->assertRedirect();

        $this->assertSame('Changes Requested', $this->jd->fresh()->status);
        $newVersion = $requirement->jobDescriptionVersions()->orderByDesc('version')->firstOrFail();
        $this->assertSame(2, $newVersion->version);
        $this->assertSame('Draft', $newVersion->status);
        $this->assertSame($this->jd->title, $newVersion->title);
        $this->assertSame('client_changes', $newVersion->source);
        $this->assertDatabaseHas('job_description_client_decisions', [
            'job_description_version_id' => $this->jd->id,
            'decision' => 'changes_requested',
            'comments' => 'Please add the required certification.',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'jd.changes_requested', 'entity_id' => (string) $this->jd->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'jd.version_created', 'entity_id' => (string) $newVersion->id]);
    }

    public function test_previous_approved_version_is_preserved_and_superseded_only_after_new_approval(): void
    {
        [$requirement, $recruiter, $manager, $management] = $this->requirementAndRecruiter();
        $first = $this->createJd($requirement, $recruiter);
        $this->completeApproval($first, $recruiter, $manager, $management);
        $approvedContent = $first->fresh()->only(JobDescriptionVersion::CONTENT_FIELDS);

        $this->actingAs($recruiter)->post(route('job-descriptions.versions.store', $first))->assertRedirect();
        $second = $requirement->jobDescriptionVersions()->orderByDesc('version')->firstOrFail();
        $this->assertSame('Draft', $second->status);
        $this->assertSame('Approved', $first->fresh()->status);
        $this->assertSame($approvedContent, $first->fresh()->only(JobDescriptionVersion::CONTENT_FIELDS));
        $this->assertNull($second->approved_at);

        $this->actingAs($recruiter)->patch(route('job-descriptions.update', $second), array_replace($this->jdContent(), ['title' => 'Updated approved draft']))->assertRedirect();
        $this->completeApproval($second, $recruiter, $manager, $management);

        $this->assertSame('Superseded', $first->fresh()->status);
        $this->assertSame($approvedContent, $first->fresh()->only(JobDescriptionVersion::CONTENT_FIELDS));
        $this->assertSame('Approved', $second->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'jd.superseded', 'entity_id' => (string) $first->id]);
    }

    public function test_requirement_cannot_be_activated_without_an_approved_jd_version(): void
    {
        [$requirement, $recruiter, $manager] = $this->requirementAndRecruiter();
        $requirement->forceFill(['status' => 'Approved'])->save();
        $this->actingAs($manager)->put(route('requirements.assignments.update', $requirement), ['recruiter_ids' => [$recruiter->id]]);

        $this->actingAs($manager)->post(route('requirements.activate', $requirement))->assertForbidden();
        $this->assertSame('Approved', $requirement->fresh()->status);
    }

    public function test_ai_output_is_saved_as_an_unapproved_draft_and_provider_failure_keeps_manual_flow_available(): void
    {
        [$requirement, $recruiter] = $this->requirementAndRecruiter();
        $this->app->instance(JdDraftingProvider::class, new class implements JdDraftingProvider
        {
            public function generate(array $requirement): array
            {
                return ['title' => 'AI-assisted title', 'summary' => 'Human review is required.'];
            }
        });

        $this->actingAs($recruiter)->post(route('job-descriptions.generate', $requirement))->assertRedirect();
        $jd = JobDescriptionVersion::query()->firstOrFail();
        $this->assertSame('AI Draft', $jd->status);
        $this->assertSame('ai_assisted', $jd->source);
        $this->assertNull($jd->approved_at);
        $this->get(route('job-descriptions.show', $jd))->assertOk()->assertSee('AI-assisted draft — human review required.');
        $this->assertDatabaseHas('audit_logs', ['action' => 'jd.ai_draft_generated', 'entity_id' => (string) $jd->id]);

        [$manualRequirement, $manualRecruiter] = $this->requirementAndRecruiter('Manual fallback');
        $this->app->instance(JdDraftingProvider::class, new UnavailableJdDraftingProvider);
        $this->actingAs($manualRecruiter)->post(route('job-descriptions.generate', $manualRequirement))
            ->assertRedirect()->assertSessionHasErrors('ai');
        $this->post(route('job-descriptions.store', $manualRequirement), $this->jdContent())->assertRedirect();
        $this->assertDatabaseHas('job_description_versions', ['requirement_id' => $manualRequirement->id, 'status' => 'Draft']);
    }

    public function test_server_side_ai_secret_is_not_rendered_to_the_browser(): void
    {
        [$requirement, $recruiter] = $this->requirementAndRecruiter();
        config(['jd.drafting.api_key' => 'server-only-test-key']);
        config(['ai.providers.openai.api_key' => 'ai-server-only-test-key', 'ai.providers.anthropic.api_key' => 'claude-server-only-test-key']);
        $this->actingAs($recruiter)->get(route('job-descriptions.index', $requirement))
            ->assertOk()->assertDontSee('server-only-test-key')->assertDontSee('ai-server-only-test-key')->assertDontSee('claude-server-only-test-key');
    }

    public function test_openai_adapter_uses_server_side_secret_and_structured_json_schema(): void
    {
        Http::fake([
            'https://drafting.example.test/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode(['title' => 'Sourced title'])]]],
            ]),
        ]);
        $provider = new OpenAiProvider('server-only-test-key', 'test-model', 'https://drafting.example.test/v1/chat/completions', 45);

        $result = $provider->generateStructured('Draft only.', 'Requirement facts: Engineer, PHP.', [
            'type' => 'object', 'additionalProperties' => false, 'required' => ['title'],
            'properties' => ['title' => ['type' => 'string']],
        ]);

        $this->assertSame('Sourced title', $result['title']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer server-only-test-key')
            && str_contains($request['messages'][1]['content'], 'Requirement facts')
            && $request['response_format']['type'] === 'json_schema'
            && $request['response_format']['json_schema']['strict'] === true);
    }

    private function sentForClientApproval(): array
    {
        [$requirement, $recruiter, $manager, $management] = $this->requirementAndRecruiter();
        $this->jd = $this->createJd($requirement, $recruiter);
        $this->completeReview($this->jd, $recruiter, $manager);

        return [$requirement, $recruiter, $manager, $management];
    }

    private function completeReview(JobDescriptionVersion $jd, User $recruiter, User $manager): void
    {
        $this->actingAs($recruiter)->post(route('job-descriptions.review', $jd))->assertRedirect();
        $this->actingAs($manager)->post(route('job-descriptions.send-for-approval', $jd))->assertRedirect();
    }

    private function completeApproval(JobDescriptionVersion $jd, User $recruiter, User $manager, User $management): void
    {
        $this->completeReview($jd, $recruiter, $manager);
        $this->actingAs($management)->post(route('job-descriptions.client-decision', $jd), [
            'decision' => 'approved',
            'client_spoc_reference' => 'Client email approval record',
        ])->assertRedirect();
    }

    private function createJd(Requirement $requirement, User $recruiter): JobDescriptionVersion
    {
        $this->actingAs($recruiter)->post(route('job-descriptions.store', $requirement), $this->jdContent())->assertRedirect();

        return $requirement->jobDescriptionVersions()->firstOrFail();
    }

    private function requirementAndRecruiter(string $label = 'Test client'): array
    {
        $recruiter = $this->userWithRole('recruiter');
        $manager = $this->userWithRole('recruitment-manager');
        $management = $this->userWithRole('management');
        $client = ClientOrganization::query()->create(['name' => $label]);
        $requirement = Requirement::query()->create([
            'reference' => 'REQ-'.Str::upper(Str::random(20)),
            'client_organization_id' => $client->id,
            'title' => 'Platform Engineer Intake',
            'job_title' => 'Platform Engineer',
            'openings' => 1,
            'employment_type' => 'Full-time',
            'location' => 'Chennai',
            'work_mode' => 'Hybrid',
            'salary_ctc' => 'INR 18–24 LPA',
            'min_experience_years' => 4,
            'max_experience_years' => 8,
            'required_skills' => 'PHP, Laravel',
            'qualification' => 'Relevant experience',
            'original_target_date' => now()->addDays(40)->toDateString(),
            'target_date' => now()->addDays(40)->toDateString(),
            'priority' => 'Normal',
            'status' => 'Approved',
            'recruitment_manager_id' => $manager->id,
            'intake_source' => 'Client RFP',
            'created_by' => $management->id,
        ]);
        RequirementAssignment::query()->create([
            'requirement_id' => $requirement->id,
            'recruiter_id' => $recruiter->id,
            'assigned_by' => $manager->id,
            'assigned_at' => now(),
        ]);

        return [$requirement, $recruiter, $manager, $management];
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $user->roles()->attach(Role::query()->where('slug', $slug)->value('id'));

        return $user;
    }

    private function jdContent(): array
    {
        return [
            'title' => 'Platform Engineer',
            'summary' => 'Build reliable client systems.',
            'responsibilities' => 'Design, implement and maintain applications.',
            'required_skills' => 'PHP, Laravel, SQL',
            'preferred_skills' => 'Cloud operations',
            'qualification' => 'Relevant experience or qualification',
            'experience_description' => '4 to 8 years',
            'location' => 'Chennai',
            'work_mode' => 'Hybrid',
            'employment_type' => 'Full-time',
            'additional_information' => 'No additional requirements supplied.',
        ];
    }
}
