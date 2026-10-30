<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CandidateConsent;
use App\Models\CandidateDocument;
use App\Models\CandidateRecruiterAccess;
use App\Models\ClientOrganization;
use App\Models\Requirement;
use App\Models\RequirementAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CandidateFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_authorized_recruiter_can_create_one_canonical_candidate_profile(): void
    {
        $recruiter = $this->userWithRole('recruiter');
        $this->actingAs($recruiter)->post(route('candidates.store'), $this->candidateData())->assertRedirect();

        $candidate = Candidate::query()->firstOrFail();
        $this->assertSame('New', $candidate->status);
        $this->assertNull($candidate->user_id);
        $this->assertSame('candidate@example.test', $candidate->email_normalized);
        $this->assertSame('15551234567', $candidate->mobile_normalized);
        $this->assertTrue($candidate->hasActiveAccess($recruiter));
        $this->assertDatabaseHas('audit_logs', ['action' => 'candidate.created', 'entity_id' => (string) $candidate->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'candidate.access_granted', 'entity_id' => (string) $candidate->id]);
    }

    public function test_admin_and_super_admin_cannot_access_candidate_records(): void
    {
        $candidate = $this->createCandidate();
        foreach (['admin', 'super-admin'] as $role) {
            $user = $this->userWithRole($role);
            $this->actingAs($user)->get(route('candidates.index'))->assertForbidden();
            $this->get(route('candidates.create'))->assertForbidden();
            $this->post(route('candidates.store'), $this->candidateData())->assertForbidden();
            $this->get(route('candidates.show', $candidate))->assertForbidden();
        }
    }

    public function test_recruiter_without_explicit_access_cannot_view_candidate_or_documents(): void
    {
        $candidate = $this->createCandidate();
        $outsider = $this->userWithRole('recruiter');

        $this->actingAs($outsider)->get(route('candidates.show', $candidate))->assertForbidden();
        $this->get(route('candidates.index'))->assertDontSee($candidate->reference);
        $this->post(route('candidates.notes.store', $candidate), ['note' => 'Unauthorized note'])->assertForbidden();
        $this->post(route('candidates.consents.store', $candidate), $this->consentData($this->requirementFor($outsider)))->assertForbidden();
    }

    public function test_recruitment_manager_grants_and_revokes_shared_access_for_multiple_recruiters(): void
    {
        $candidate = $this->createCandidate();
        $manager = $this->userWithRole('recruitment-manager');
        $first = $this->userWithRole('recruiter');
        $second = $this->userWithRole('recruiter');

        $this->actingAs($manager)->post(route('candidates.access.grant', $candidate), [
            'recruiter_id' => $first->id,
            'reason' => 'Shared delivery coverage.',
        ])->assertRedirect();
        $this->actingAs($manager)->post(route('candidates.access.grant', $candidate), ['recruiter_id' => $second->id])->assertRedirect();

        $this->assertTrue($candidate->fresh()->hasActiveAccess($first));
        $this->assertTrue($candidate->fresh()->hasActiveAccess($second));
        $this->actingAs($first)->get(route('candidates.show', $candidate))->assertOk();
        $this->actingAs($second)->get(route('candidates.show', $candidate))->assertOk();

        $access = CandidateRecruiterAccess::query()->where('candidate_id', $candidate->id)->where('recruiter_id', $first->id)->firstOrFail();
        $this->actingAs($manager)->delete(route('candidates.access.revoke', [$candidate, $access]), ['reason' => 'Recruiter assignment ended.'])->assertRedirect();

        $this->assertFalse($candidate->fresh()->hasActiveAccess($first));
        $this->assertTrue($candidate->fresh()->hasActiveAccess($second));
        $this->actingAs($first)->get(route('candidates.show', $candidate))->assertForbidden();
        $this->assertDatabaseHas('candidate_recruiter_access', ['id' => $access->id, 'status' => 'revoked']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'candidate.access_revoked', 'entity_id' => (string) $candidate->id]);
    }

    public function test_recruiter_cannot_grant_candidate_access_to_another_recruiter(): void
    {
        $candidate = $this->createCandidate();
        $other = $this->userWithRole('recruiter');
        $creator = User::query()->findOrFail($candidate->created_by);

        $this->actingAs($creator)->post(route('candidates.access.grant', $candidate), ['recruiter_id' => $other->id])->assertForbidden();
        $this->assertFalse($candidate->fresh()->hasActiveAccess($other));
    }

    public function test_normalized_duplicate_detection_never_creates_a_second_profile_or_exposes_match_details(): void
    {
        $recruiter = $this->userWithRole('recruiter');
        $this->actingAs($recruiter)->post(route('candidates.store'), $this->candidateData())->assertRedirect();
        $this->from(route('candidates.create'))->post(route('candidates.store'), array_replace($this->candidateData(), [
            'full_name' => 'Different Intake Name',
            'email' => ' CANDIDATE@EXAMPLE.TEST ',
            'mobile' => '+1 555-123-4567',
        ]))->assertRedirect(route('candidates.create'))->assertSessionHasErrors('contact');

        $this->assertSame(1, Candidate::query()->count());
        $error = session('errors')->getBag('default')->first('contact');
        $this->assertStringNotContainsString('Canonical Candidate', $error);
        $this->assertStringNotContainsString('candidate@example.test', $error);
        $this->assertDatabaseHas('audit_logs', ['action' => 'candidate.duplicate_detected', 'entity_id' => null]);
        $duplicateEvent = AuditLog::query()->where('action', 'candidate.duplicate_detected')->firstOrFail();
        $this->assertArrayNotHasKey('candidate_id', $duplicateEvent->context);
        $this->assertContains('email', $duplicateEvent->context['matched_fields']);
        $this->assertContains('mobile', $duplicateEvent->context['matched_fields']);
    }

    public function test_candidate_can_be_linked_to_multiple_requirements_without_submission(): void
    {
        $candidate = $this->createCandidate();
        $recruiter = User::query()->findOrFail($candidate->created_by);
        $first = $this->requirementFor($recruiter, 'Client A', 'Requirement One');
        $second = $this->requirementFor($recruiter, 'Client B', 'Requirement Two');

        foreach ([$first, $second] as $requirement) {
            $this->actingAs($recruiter)->post(route('candidates.requirements.link', $candidate), [
                'requirement_id' => $requirement->id,
                'reason' => 'Candidate expressed interest in this role.',
            ])->assertRedirect();
        }

        $this->assertSame(2, $candidate->requirements()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'candidate.requirement_linked', 'entity_id' => (string) $candidate->id]);
        $this->assertFalse(collect(app('router')->getRoutes())->contains(fn ($route) => str_contains($route->uri(), 'candidate-submission')));
    }

    public function test_candidate_consent_is_scoped_to_client_and_requirement_and_same_scope_is_reused(): void
    {
        $candidate = $this->createCandidate();
        $recruiter = User::query()->findOrFail($candidate->created_by);
        $clientA = 'Consent Client A';
        $clientB = 'Consent Client B';
        $requirements = [
            $this->requirementFor($recruiter, $clientA, 'Client A Role One'),
            $this->requirementFor($recruiter, $clientA, 'Client A Role Two'),
            $this->requirementFor($recruiter, $clientB, 'Client B Role'),
        ];

        foreach ($requirements as $requirement) {
            $this->actingAs($recruiter)->post(route('candidates.requirements.link', $candidate), ['requirement_id' => $requirement->id])->assertRedirect();
        }

        $this->actingAs($recruiter)->post(route('candidates.consents.store', $candidate), $this->consentData($requirements[0]))->assertRedirect();
        $this->post(route('candidates.consents.store', $candidate), $this->consentData($requirements[0]))->assertRedirect();
        $this->post(route('candidates.consents.store', $candidate), $this->consentData($requirements[1]))->assertRedirect();
        $this->post(route('candidates.consents.store', $candidate), $this->consentData($requirements[2]))->assertRedirect();

        $this->assertSame(3, CandidateConsent::query()->where('candidate_id', $candidate->id)->count());
        $this->assertTrue($candidate->hasValidConsentFor($requirements[0]->client_organization_id, $requirements[0]->id));
        $this->assertTrue($candidate->hasValidConsentFor($requirements[1]->client_organization_id, $requirements[1]->id));
        $this->assertTrue($candidate->hasValidConsentFor($requirements[2]->client_organization_id, $requirements[2]->id));
        $this->assertDatabaseHas('audit_logs', ['action' => 'candidate.consent_recorded', 'entity_id' => (string) $candidate->id]);
    }

    public function test_consent_withdrawal_preserves_history_and_blocks_valid_scope_until_new_consent(): void
    {
        $candidate = $this->createCandidate();
        $recruiter = User::query()->findOrFail($candidate->created_by);
        $requirement = $this->requirementFor($recruiter);
        $this->actingAs($recruiter)->post(route('candidates.requirements.link', $candidate), ['requirement_id' => $requirement->id]);
        $this->post(route('candidates.consents.store', $candidate), $this->consentData($requirement));
        $consent = CandidateConsent::query()->firstOrFail();

        $this->post(route('candidate-consents.withdraw', $consent), ['reason' => 'Candidate withdrew permission by email.'])->assertRedirect();
        $this->assertSame('withdrawn', $consent->fresh()->status);
        $this->assertFalse($candidate->fresh()->hasValidConsentFor($requirement->client_organization_id, $requirement->id));
        $this->assertSame(1, CandidateConsent::query()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'candidate.consent_withdrawn', 'entity_id' => (string) $candidate->id]);

        $this->post(route('candidates.consents.store', $candidate), $this->consentData($requirement))->assertRedirect();
        $this->assertSame(2, CandidateConsent::query()->count());
        $this->assertTrue($candidate->fresh()->hasValidConsentFor($requirement->client_organization_id, $requirement->id));
    }

    public function test_consent_cannot_be_modified_by_a_recruiter_without_candidate_access(): void
    {
        $candidate = $this->createCandidate();
        $outsider = $this->userWithRole('recruiter');
        $this->actingAs($outsider)->post(route('candidates.consents.store', $candidate), [])->assertForbidden();
    }

    public function test_resume_is_private_and_download_requires_candidate_access(): void
    {
        Storage::fake('private_documents');
        $candidate = $this->createCandidate();
        $recruiter = User::query()->findOrFail($candidate->created_by);
        $outsider = $this->userWithRole('recruiter');
        $file = UploadedFile::fake()->createWithContent('candidate-resume.pdf', "%PDF-1.4\nTYCHIFF private resume\n%%EOF");

        $this->actingAs($recruiter)->post(route('candidates.documents.store', $candidate), [
            'document_type' => 'resume',
            'document' => $file,
        ])->assertRedirect();

        $document = CandidateDocument::query()->firstOrFail();
        Storage::disk('private_documents')->assertExists($document->storage_path);
        $this->assertStringStartsWith('candidates/'.$candidate->reference.'/', $document->storage_path);
        $this->assertFalse(str_starts_with($document->storage_path, 'public/'));
        $this->actingAs($outsider)->get(route('candidate-documents.download', $document))->assertForbidden();
        $this->actingAs($recruiter)->get(route('candidate-documents.download', $document))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'candidate.document_uploaded', 'entity_id' => (string) $candidate->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'candidate.document_viewed', 'entity_id' => (string) $candidate->id]);
    }

    public function test_candidate_notes_are_private_and_audited(): void
    {
        $candidate = $this->createCandidate();
        $creator = User::query()->findOrFail($candidate->created_by);
        $outsider = $this->userWithRole('recruiter');

        $this->actingAs($outsider)->post(route('candidates.notes.store', $candidate), ['note' => 'Private note'])->assertForbidden();
        $this->actingAs($creator)->post(route('candidates.notes.store', $candidate), ['note' => 'Candidate prefers hybrid roles.'])->assertRedirect();
        $this->assertDatabaseHas('candidate_notes', ['candidate_id' => $candidate->id, 'created_by' => $creator->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'candidate.note_added', 'entity_id' => (string) $candidate->id]);
        $this->get(route('candidates.show', $candidate))->assertOk()->assertSee('Candidate prefers hybrid roles.');
    }

    public function test_candidate_submission_and_matching_routes_do_not_exist(): void
    {
        $uris = collect(app('router')->getRoutes())->map(fn ($route) => $route->uri());
        $this->assertFalse($uris->contains(fn (string $uri) => str_contains($uri, 'candidate-submission')));
        $this->assertFalse($uris->contains(fn (string $uri) => str_contains($uri, 'candidate-matching')));
        $this->assertFalse($uris->contains(fn (string $uri) => str_contains($uri, 'candidate-scoring')));
        $this->assertFalse($uris->contains(fn (string $uri) => str_contains($uri, 'interview')));
        $this->assertFalse($uris->contains(fn (string $uri) => str_contains($uri, 'offer')));
    }

    private function createCandidate(): Candidate
    {
        $recruiter = $this->userWithRole('recruiter');
        $this->actingAs($recruiter)->post(route('candidates.store'), $this->candidateData())->assertRedirect();

        return Candidate::query()->firstOrFail();
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $user->roles()->attach(Role::query()->where('slug', $slug)->value('id'));

        return $user;
    }

    private function requirementFor(User $recruiter, string $clientName = 'Candidate Consent Client', string $title = 'Candidate Consent Requirement'): Requirement
    {
        $manager = $this->userWithRole('recruitment-manager');
        $management = $this->userWithRole('management');
        $client = ClientOrganization::query()->create(['name' => $clientName.' '.Str::upper(Str::random(5))]);
        $requirement = Requirement::query()->create([
            'reference' => 'REQ-'.Str::upper(Str::random(20)),
            'client_organization_id' => $client->id,
            'title' => $title,
            'job_title' => 'Candidate role',
            'openings' => 1,
            'employment_type' => 'Full-time',
            'location' => 'Remote',
            'work_mode' => 'Remote',
            'priority' => 'Normal',
            'status' => 'Approved',
            'intake_source' => 'Client RFP',
            'created_by' => $management->id,
            'recruitment_manager_id' => $manager->id,
        ]);
        RequirementAssignment::query()->create([
            'requirement_id' => $requirement->id,
            'recruiter_id' => $recruiter->id,
            'assigned_by' => $manager->id,
            'assigned_at' => now(),
        ]);

        return $requirement;
    }

    private function candidateData(): array
    {
        return [
            'full_name' => 'Canonical Candidate',
            'email' => 'candidate@example.test',
            'mobile' => '+1 (555) 123-4567',
            'alternate_contact' => 'alternate@example.test',
            'location' => 'Remote',
            'current_designation' => 'Senior Engineer',
            'total_experience_years' => 8,
            'relevant_experience_years' => 6,
            'primary_skills' => 'PHP, Laravel',
            'secondary_skills' => 'MySQL, Redis',
            'highest_qualification' => 'Bachelor degree',
            'current_employer' => 'Example Employer',
            'current_ctc' => 'USD 120k',
            'expected_ctc' => 'USD 140k',
            'notice_period_days' => 30,
            'preferred_location' => 'Remote',
            'preferred_work_mode' => 'Remote',
            'availability' => 'Available in 30 days',
            'profile_summary' => 'Experienced software engineer.',
            'source' => 'Referral',
            'source_details' => 'Professional referral',
        ];
    }

    private function consentData(Requirement $requirement): array
    {
        return [
            'requirement_id' => $requirement->id,
            'consented_at' => now()->subMinute()->format('Y-m-d\TH:i'),
            'consent_method' => 'email',
            'consent_text_reference' => 'Approved sharing notice ref C-2026-01',
            'candidate_permission_confirmed' => '1',
        ];
    }
}
