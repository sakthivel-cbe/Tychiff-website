<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\PermissionChangeRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionChangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionGovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_stays_pending_and_does_not_activate_a_permission(): void
    {
        $this->seed();
        $requester = $this->userWithRole('super-admin');
        $role = Role::where('slug', 'recruiter')->firstOrFail();
        $permission = Permission::where('name', 'requirements.prioritize')->firstOrFail();

        $response = $this->actingAs($requester)->postJson('/admin/permission-change-requests', [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
            'requested_state' => true,
            'reason' => 'Operational coverage requires approved prioritization access.',
        ])->assertCreated()->assertJsonPath('status', PermissionChangeRequest::STATUS_PENDING);

        $change = PermissionChangeRequest::findOrFail($response->json('id'));
        $this->assertFalse($role->permissions()->whereKey($permission->id)->exists());
        $this->assertSame($requester->id, $change->requester_id);
        $this->assertFalse($change->current_state);
        $this->assertTrue($change->requested_state);
        $this->assertNotNull($change->requested_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'permissions.change_requested',
            'entity_id' => (string) $change->id,
        ]);
    }

    public function test_only_a_different_user_with_approval_permission_can_approve_and_activate(): void
    {
        $this->seed();
        $requester = $this->userWithRole('super-admin');
        $reviewer = $this->userWithRole('super-admin');
        $role = Role::where('slug', 'recruiter')->firstOrFail();
        $permission = Permission::where('name', 'requirements.prioritize')->firstOrFail();
        $change = $this->createGrantRequest($requester, $role, $permission);

        $this->actingAs($requester)->postJson("/admin/permission-change-requests/$change->id/approve", [
            'decision_reason' => 'Approved by a separate Super Admin.',
        ])->assertForbidden();
        $this->assertSame(PermissionChangeRequest::STATUS_PENDING, $change->fresh()->status);
        $this->assertFalse($role->permissions()->whereKey($permission->id)->exists());

        $this->actingAs($reviewer)->postJson("/admin/permission-change-requests/$change->id/approve", [
            'decision_reason' => 'Approved after independent review.',
        ])->assertOk()->assertJsonPath('status', PermissionChangeRequest::STATUS_APPROVED);

        $this->assertTrue($role->fresh()->permissions()->whereKey($permission->id)->exists());
        $this->assertSame($reviewer->id, $change->fresh()->reviewed_by);
        $this->assertNotNull($change->fresh()->reviewed_at);
        $this->assertSame('Approved after independent review.', $change->fresh()->decision_reason);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
            'approved_by' => $reviewer->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'permissions.change_approved',
            'entity_id' => (string) $change->id,
        ]);
    }

    public function test_rejection_requires_approval_permission_and_leaves_active_permissions_unchanged(): void
    {
        $this->seed();
        $requester = $this->userWithRole('super-admin');
        $recruiter = $this->userWithRole('recruiter');
        $reviewer = $this->userWithRole('super-admin');
        $role = Role::where('slug', 'recruiter')->firstOrFail();
        $permission = Permission::where('name', 'requirements.prioritize')->firstOrFail();
        $change = $this->createGrantRequest($requester, $role, $permission);

        $this->actingAs($recruiter)->postJson("/admin/permission-change-requests/$change->id/reject", [
            'decision_reason' => 'Not authorized to reject.',
        ])->assertForbidden();

        $this->actingAs($reviewer)->postJson("/admin/permission-change-requests/$change->id/reject", [
            'decision_reason' => 'The requested access is not approved.',
        ])->assertOk()->assertJsonPath('status', PermissionChangeRequest::STATUS_REJECTED);

        $this->assertFalse($role->fresh()->permissions()->whereKey($permission->id)->exists());
        $this->assertSame($reviewer->id, $change->fresh()->reviewed_by);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'permissions.change_rejected',
            'entity_id' => (string) $change->id,
        ]);
    }

    public function test_approved_revocation_deactivates_only_the_requested_permission(): void
    {
        $this->seed();
        $requester = $this->userWithRole('super-admin');
        $reviewer = $this->userWithRole('super-admin');
        $role = Role::where('slug', 'recruiter')->firstOrFail();
        $permission = Permission::where('name', 'candidates.submit')->firstOrFail();
        $this->assertTrue($role->permissions()->whereKey($permission->id)->exists());

        $change = app(PermissionChangeService::class)->requestChange(
            $requester,
            $role->id,
            $permission->id,
            false,
            'Request removal after the role scope changed.',
        );

        $this->assertTrue($role->permissions()->whereKey($permission->id)->exists());
        $this->actingAs($reviewer)->postJson("/admin/permission-change-requests/$change->id/approve", [
            'decision_reason' => 'Revocation approved after independent review.',
        ])->assertOk()->assertJsonPath('status', PermissionChangeRequest::STATUS_APPROVED);

        $this->assertFalse($role->fresh()->permissions()->whereKey($permission->id)->exists());
    }

    public function test_users_without_permission_management_cannot_submit_change_requests(): void
    {
        $this->seed();
        $recruiter = $this->userWithRole('recruiter');
        $role = Role::where('slug', 'recruiter')->firstOrFail();
        $permission = Permission::where('name', 'requirements.prioritize')->firstOrFail();

        $this->actingAs($recruiter)->postJson('/admin/permission-change-requests', [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
            'requested_state' => true,
            'reason' => 'Request permission without authority.',
        ])->assertForbidden();

        $this->assertDatabaseCount('permission_change_requests', 0);
    }

    public function test_requester_can_cancel_pending_request_without_changing_permission_state(): void
    {
        $this->seed();
        $requester = $this->userWithRole('super-admin');
        $role = Role::where('slug', 'recruiter')->firstOrFail();
        $permission = Permission::where('name', 'requirements.prioritize')->firstOrFail();
        $change = $this->createGrantRequest($requester, $role, $permission);

        $this->actingAs($requester)->postJson("/admin/permission-change-requests/$change->id/cancel")
            ->assertOk()->assertJsonPath('status', PermissionChangeRequest::STATUS_CANCELLED);

        $this->assertFalse($role->fresh()->permissions()->whereKey($permission->id)->exists());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'permissions.change_cancelled',
            'entity_id' => (string) $change->id,
        ]);
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->roles()->attach(Role::where('slug', $slug)->value('id'));

        return $user;
    }

    private function createGrantRequest(User $requester, Role $role, Permission $permission): PermissionChangeRequest
    {
        return app(PermissionChangeService::class)->requestChange(
            $requester,
            $role->id,
            $permission->id,
            true,
            'Request change for authorization test coverage.',
        );
    }
}
