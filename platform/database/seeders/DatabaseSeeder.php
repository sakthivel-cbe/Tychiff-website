<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $catalog = [
                'system.manage' => 'Manage foundational system configuration.',
                'permissions.manage' => 'Prepare changes to role and permission configuration.',
                'permissions.approve' => 'Approve role and permission configuration changes.',
                'roles.view' => 'View role and permission definitions.',
                'roles.assign' => 'Assign approved roles to users.',
                'users.view' => 'View internal user accounts.',
                'users.manage' => 'Manage internal user accounts.',
                'users.invite' => 'Invite internal staff.',
                'users.delete_permanently' => 'Permanently delete user accounts.',
                'recruitment.view_all' => 'View organization-wide recruitment operations.',
                'recruitment.intervene' => 'Intervene in recruitment operations when required.',
                'resources.allocate' => 'Allocate recruitment resources.',
                'recruiters.assign' => 'Assign and reassign Recruiters to requirements.',
                'recruiters.manage_workload' => 'Manage Recruiter workload and availability.',
                'requirements.prioritize' => 'Set operational requirement priority.',
                'recruitment.escalate' => 'Resolve normal recruitment escalations.',
                'requirements.view_assigned' => 'View assigned requirements and tasks.',
                'candidates.screen' => 'Screen candidates for assigned requirements.',
                'candidates.submit' => 'Submit candidates for client consideration.',
                'interviews.coordinate' => 'Coordinate candidate interviews.',
                'admin.support' => 'Provide limited administrative support.',
            ];

            foreach ($catalog as $name => $description) {
                Permission::updateOrCreate(['name' => $name], ['description' => $description]);
            }

            $definitions = [
                'management' => ['Management', 'Organization-wide business visibility and operational intervention.'],
                'super-admin' => ['Super Admin', 'Final system authority for security and permission configuration.'],
                'recruitment-manager' => ['Recruitment Manager', 'Normal operational supervisor for Recruiters and recruitment workload.'],
                'recruiter' => ['Recruiter', 'Executes assigned recruitment requirements and candidate coordination.'],
                'admin' => ['Admin', 'Limited administrative and support access.'],
            ];

            $roles = [];
            foreach ($definitions as $slug => [$name, $description]) {
                $roles[$slug] = Role::updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'description' => $description, 'is_system' => true],
                );
            }

            $grants = [
                'management' => ['recruitment.view_all', 'recruitment.intervene', 'resources.allocate'],
                'super-admin' => [
                    'system.manage', 'permissions.manage', 'permissions.approve', 'roles.view', 'roles.assign',
                    'users.view', 'users.manage', 'users.invite', 'users.delete_permanently',
                ],
                'recruitment-manager' => [
                    'recruitment.view_all', 'recruiters.assign', 'recruiters.manage_workload',
                    'requirements.prioritize', 'recruitment.escalate',
                ],
                'recruiter' => [
                    'requirements.view_assigned', 'candidates.screen', 'candidates.submit', 'interviews.coordinate',
                ],
                'admin' => ['admin.support'],
            ];

            foreach ($grants as $slug => $permissionNames) {
                $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
                $roles[$slug]->permissions()->syncWithoutDetaching($permissionIds->mapWithKeys(
                    fn (int $id): array => [$id => ['approved_at' => now()]],
                )->all());
            }
        });

        $this->call(PrivacyNoticeVersionSeeder::class);
    }
}
