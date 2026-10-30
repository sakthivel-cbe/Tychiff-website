<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\JobDescriptionVersion;
use App\Models\Requirement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequirementIntakeTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_can_receive_and_view_a_requirement(): void
    {
        $management = $this->userWithRole('management');
        $this->actingAs($management);

        $this->get(route('requirements.create'))->assertOk()->assertSee('Receive a requirement');

        $this->post(route('requirements.store'), $this->intakePayload())
            ->assertRedirect();

        $requirement = Requirement::query()->firstOrFail();
        $this->assertSame('Pending Review', $requirement->status);
        $this->assertSame('Normal', $requirement->priority);
        $this->assertNotEmpty($requirement->reference);
        $this->assertSame($requirement->target_date?->toDateString(), $requirement->original_target_date?->toDateString());

        $this->get(route('requirements.show', $requirement))
            ->assertOk()
            ->assertSee($requirement->reference)
            ->assertSee('Senior Laravel Engineer');
        $this->assertDatabaseHas('audit_logs', ['action' => 'requirement.created', 'entity_id' => (string) $requirement->id]);
    }

    public function test_recruiter_and_admin_cannot_create_requirements(): void
    {
        $this->seed();

        foreach (['recruiter', 'admin'] as $role) {
            $this->actingAs($this->userWithRole($role));
            $this->get(route('requirements.create'))->assertForbidden();
            $this->post(route('requirements.store'), $this->intakePayload())->assertForbidden();

            if ($role === 'admin') {
                $this->get(route('requirements.index'))->assertForbidden();
            } else {
                $this->get(route('requirements.index'))->assertOk();
            }
        }
    }

    public function test_recruitment_manager_can_review_assign_and_activate_only_after_approval_and_assignment(): void
    {
        $requirement = $this->createRequirement();
        $manager = $this->userWithRole('recruitment-manager');
        $recruiter = $this->userWithRole('recruiter');

        $this->actingAs($manager)
            ->post(route('requirements.approve', $requirement))->assertRedirect();
        $this->assertSame('Approved', $requirement->fresh()->status);
        $this->assertSame($manager->id, $requirement->fresh()->recruitment_manager_id);

        $this->actingAs($manager)
            ->post(route('requirements.activate', $requirement))->assertForbidden();

        $this->actingAs($manager)
            ->put(route('requirements.assignments.update', $requirement), ['recruiter_ids' => [$recruiter->id]])
            ->assertRedirect();
        $this->assertDatabaseHas('requirement_assignments', [
            'requirement_id' => $requirement->id,
            'recruiter_id' => $recruiter->id,
            'assigned_by' => $manager->id,
            'ended_at' => null,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'requirement.recruiter_assigned', 'entity_id' => (string) $requirement->id]);

        $this->actingAs($manager)->post(route('requirements.activate', $requirement))->assertForbidden();
        $this->createApprovedJd($requirement, $manager);
        $this->actingAs($manager)->post(route('requirements.activate', $requirement))->assertRedirect();
        $this->assertSame('Active', $requirement->fresh()->status);
    }

    public function test_pending_requirement_cannot_be_approved_or_activated_by_recruiter(): void
    {
        $requirement = $this->createRequirement();
        $this->actingAs($this->userWithRole('recruiter'));

        $this->post(route('requirements.approve', $requirement))->assertForbidden();
        $this->post(route('requirements.activate', $requirement))->assertForbidden();
        $this->get(route('requirements.show', $requirement))->assertForbidden();
    }

    public function test_recruiter_only_sees_requirements_currently_assigned_to_them(): void
    {
        $first = $this->createRequirement(['title' => 'Assigned to first']);
        $second = $this->createRequirement(['title' => 'Assigned to second']);
        $firstRecruiter = $this->userWithRole('recruiter');
        $secondRecruiter = $this->userWithRole('recruiter');
        $manager = $this->userWithRole('recruitment-manager');

        foreach ([[$first, $firstRecruiter], [$second, $secondRecruiter]] as [$requirement, $recruiter]) {
            $this->actingAs($manager)->post(route('requirements.approve', $requirement));
            $this->actingAs($manager)->put(route('requirements.assignments.update', $requirement), ['recruiter_ids' => [$recruiter->id]]);
        }

        $this->actingAs($firstRecruiter)
            ->get(route('requirements.index'))
            ->assertOk()
            ->assertSee('Assigned to first')
            ->assertDontSee('Assigned to second');
        $this->get(route('requirements.show', $first))->assertOk();
        $this->get(route('requirements.show', $second))->assertForbidden();
    }

    public function test_recruiter_cannot_assign_or_reassign_recruiters(): void
    {
        $requirement = $this->approvedRequirement();
        $recruiter = $this->userWithRole('recruiter');
        $manager = $this->userWithRole('recruitment-manager');

        $this->actingAs($manager)->put(route('requirements.assignments.update', $requirement), ['recruiter_ids' => [$recruiter->id]]);
        $this->actingAs($recruiter)
            ->put(route('requirements.assignments.update', $requirement), ['recruiter_ids' => [$recruiter->id]])
            ->assertForbidden();
    }

    public function test_recruitment_manager_reassignment_preserves_assignment_history_and_reason(): void
    {
        $requirement = $this->approvedRequirement();
        $manager = $this->userWithRole('recruitment-manager');
        $firstRecruiter = $this->userWithRole('recruiter');
        $nextRecruiter = $this->userWithRole('recruiter');

        $this->actingAs($manager)->put(route('requirements.assignments.update', $requirement), ['recruiter_ids' => [$firstRecruiter->id]]);
        $this->actingAs($manager)->put(route('requirements.assignments.update', $requirement), [
            'recruiter_ids' => [$nextRecruiter->id],
            'reason' => 'Workload balancing after weekly review.',
        ])->assertRedirect();

        $this->assertNotNull($requirement->assignments()->where('recruiter_id', $firstRecruiter->id)->firstOrFail()->ended_at);
        $this->assertSame(1, $requirement->activeAssignments()->where('recruiter_id', $nextRecruiter->id)->count());
        $this->assertSame(2, $requirement->assignments()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'requirement.recruiter_reassigned', 'entity_id' => (string) $requirement->id]);
    }

    public function test_recruiter_cannot_change_priority_or_client_target_date(): void
    {
        $requirement = $this->approvedRequirement();
        $recruiter = $this->userWithRole('recruiter');
        $manager = $this->userWithRole('recruitment-manager');
        $this->actingAs($manager)->put(route('requirements.assignments.update', $requirement), ['recruiter_ids' => [$recruiter->id]]);

        $this->actingAs($recruiter)
            ->post(route('requirements.priority', $requirement), ['priority' => 'Critical', 'reason' => 'Urgent'])
            ->assertForbidden();
        $this->post(route('requirements.target-date', $requirement), ['target_date' => now()->addDays(20)->toDateString(), 'reason' => 'Requested'])
            ->assertForbidden();
        $this->assertSame('Normal', $requirement->fresh()->priority);
    }

    public function test_recruitment_manager_can_change_priority_and_change_is_audited(): void
    {
        $requirement = $this->approvedRequirement();
        $manager = $this->userWithRole('recruitment-manager');

        $this->actingAs($manager)
            ->post(route('requirements.priority', $requirement), ['priority' => 'High', 'reason' => 'Client delivery deadline'])
            ->assertRedirect();

        $this->assertSame('High', $requirement->fresh()->priority);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'requirement.priority_changed',
            'entity_id' => (string) $requirement->id,
            'user_id' => $manager->id,
        ]);
        $event = AuditLog::query()->where('action', 'requirement.priority_changed')->firstOrFail();
        $this->assertSame('Normal', $event->previous_state['priority']);
        $this->assertSame('High', $event->new_state['priority']);
        $this->assertSame('Client delivery deadline', $event->context['reason']);
    }

    public function test_recruitment_manager_can_revise_target_with_reason_and_confirmation_history(): void
    {
        $requirement = $this->approvedRequirement();
        $manager = $this->userWithRole('recruitment-manager');
        $newDate = now()->addDays(30)->toDateString();

        $this->actingAs($manager)->post(route('requirements.target-date', $requirement), [
            'target_date' => $newDate,
            'reason' => 'Client requested a revised delivery date.',
            'client_confirmation_reference' => 'email-123',
        ])->assertRedirect();

        $this->assertSame($newDate, $requirement->fresh()->target_date->toDateString());
        $this->assertSame(2, $requirement->targetDateChanges()->count());
        $latestChange = $requirement->targetDateChanges()->latest('id')->firstOrFail();
        $this->assertSame($newDate, $latestChange->new_target_date->toDateString());
        $this->assertSame($manager->id, $latestChange->changed_by);
        $this->assertSame('email-123', $latestChange->client_confirmation_reference);
    }

    public function test_assigned_recruiter_can_report_at_risk_without_closing_requirement(): void
    {
        $requirement = $this->approvedRequirement();
        $manager = $this->userWithRole('recruitment-manager');
        $recruiter = $this->userWithRole('recruiter');
        $this->actingAs($manager)->put(route('requirements.assignments.update', $requirement), ['recruiter_ids' => [$recruiter->id]]);
        $this->createApprovedJd($requirement, $manager);
        $this->actingAs($manager)->post(route('requirements.activate', $requirement));

        $this->actingAs($recruiter)->post(route('requirements.risk', $requirement), ['reason' => 'Required skills are scarce in the current market.'])
            ->assertRedirect();

        $this->assertSame('At Risk', $requirement->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'requirement.at_risk', 'entity_id' => (string) $requirement->id]);
    }

    private function createRequirement(array $overrides = []): Requirement
    {
        $this->seed();
        $this->actingAs($this->userWithRole('management'))
            ->post(route('requirements.store'), array_replace($this->intakePayload(), $overrides))
            ->assertRedirect();

        return Requirement::query()->latest('id')->firstOrFail();
    }

    private function approvedRequirement(): Requirement
    {
        $requirement = $this->createRequirement();
        $this->actingAs($this->userWithRole('recruitment-manager'))
            ->post(route('requirements.approve', $requirement))->assertRedirect();

        return $requirement->fresh();
    }

    private function userWithRole(string $slug): User
    {
        $this->seed();
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $user->roles()->attach(Role::query()->where('slug', $slug)->value('id'));

        return $user;
    }

    private function createApprovedJd(Requirement $requirement, User $manager): JobDescriptionVersion
    {
        return $requirement->jobDescriptionVersions()->create([
            'version' => 1,
            'title' => $requirement->job_title,
            'source' => 'manual',
            'status' => 'Approved',
            'created_by' => $manager->getKey(),
            'reviewed_by' => $manager->getKey(),
            'reviewed_at' => now(),
            'approved_by_reference' => 'Test client SPOC',
            'approval_recorded_by' => $manager->getKey(),
            'approved_at' => now(),
        ]);
    }

    private function intakePayload(): array
    {
        return [
            'client_organization_name' => 'TYCHIFF Test Client',
            'title' => 'Backend engineering requirement',
            'job_title' => 'Senior Laravel Engineer',
            'openings' => 2,
            'employment_type' => 'Full-time',
            'location' => 'Chennai',
            'work_mode' => 'Hybrid',
            'salary_ctc' => 'INR 18–24 LPA',
            'min_experience_years' => 5,
            'max_experience_years' => 8,
            'required_skills' => 'PHP, Laravel, MySQL',
            'qualification' => 'Relevant professional experience',
            'job_description' => 'Build and maintain business applications.',
            'target_date' => now()->addDays(45)->toDateString(),
            'intake_source' => 'Client RFP',
            'source_reference' => 'RFP test reference',
        ];
    }
}
