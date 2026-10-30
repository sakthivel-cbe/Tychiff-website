<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class FoundationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_roles_keep_operational_and_system_authority_separate(): void
    {
        $this->seed();

        $management = User::factory()->create();
        $recruitmentManager = User::factory()->create();
        $recruiter = User::factory()->create();
        $admin = User::factory()->create();
        $superAdmin = User::factory()->create();

        foreach ([
            [$management, 'management'],
            [$recruitmentManager, 'recruitment-manager'],
            [$recruiter, 'recruiter'],
            [$admin, 'admin'],
            [$superAdmin, 'super-admin'],
        ] as [$user, $slug]) {
            $user->roles()->attach(Role::where('slug', $slug)->value('id'));
        }

        $this->assertTrue($management->hasPermission('recruitment.view_all'));
        $this->assertTrue($management->hasPermission('resources.allocate'));
        $this->assertFalse($management->hasPermission('recruiters.assign'));
        $this->assertTrue($recruitmentManager->hasPermission('recruiters.assign'));
        $this->assertFalse($recruitmentManager->hasPermission('permissions.approve'));
        $this->assertTrue($recruiter->hasPermission('candidates.screen'));
        $this->assertFalse($recruiter->hasPermission('recruiters.assign'));
        $this->assertTrue($admin->hasPermission('admin.support'));
        $this->assertFalse($admin->hasPermission('candidates.screen'));
        $this->assertTrue($superAdmin->hasPermission('permissions.approve'));
        $this->assertTrue($superAdmin->hasPermission('users.delete_permanently'));
        $this->assertFalse($superAdmin->hasPermission('candidates.screen'));
    }

    public function test_user_can_hold_management_and_super_admin_as_separate_roles(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::whereIn('slug', ['management', 'super-admin'])->pluck('id'));

        $this->assertTrue($user->hasRole('management'));
        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertTrue($user->hasPermission('recruitment.view_all'));
        $this->assertTrue($user->hasPermission('permissions.approve'));
    }

    public function test_central_future_action_gates_enforce_the_existing_permission_matrix(): void
    {
        $this->seed();
        $management = $this->userWithRole('management');
        $superAdmin = $this->userWithRole('super-admin');
        $manager = $this->userWithRole('recruitment-manager');
        $recruiter = $this->userWithRole('recruiter');
        $admin = $this->userWithRole('admin');

        $this->assertTrue(Gate::forUser($management)->allows('recruitment.view_all'));
        $this->assertTrue(Gate::forUser($management)->allows('recruitment.intervene'));
        $this->assertTrue(Gate::forUser($management)->allows('resources.allocate'));
        $this->assertFalse(Gate::forUser($management)->allows('approve', Permission::first()));

        $this->assertTrue(Gate::forUser($manager)->allows('recruiters.assign'));
        $this->assertTrue(Gate::forUser($manager)->allows('requirements.prioritize'));
        $this->assertFalse(Gate::forUser($manager)->allows('approve', Permission::first()));

        $this->assertFalse(Gate::forUser($recruiter)->allows('recruiters.assign'));
        $this->assertFalse(Gate::forUser($recruiter)->allows('requirements.prioritize'));
        $this->assertTrue(Gate::forUser($recruiter)->allows('candidates.screen'));

        $this->assertFalse(Gate::forUser($admin)->allows('candidates.screen'));
        $this->assertFalse(Gate::forUser($admin)->allows('recruitment.intervene'));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('approve', Permission::first()));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('delete', User::factory()->create()));
    }

    public function test_future_gates_combine_permissions_from_multiple_roles(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::whereIn('slug', ['management', 'recruitment-manager'])->pluck('id'));

        $this->assertTrue(Gate::forUser($user)->allows('recruitment.intervene'));
        $this->assertTrue(Gate::forUser($user)->allows('recruiters.assign'));
        $this->assertTrue(Gate::forUser($user)->allows('requirements.prioritize'));
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', $slug)->value('id'));

        return $user;
    }
}
