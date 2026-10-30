<?php

namespace App\Providers;

use App\AI\Contracts\ResumeTextExtractor;
use App\AI\Services\PrivateDocumentResumeTextExtractor;
use App\Contracts\JdDraftingProvider;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\CandidateRecruiterAccess;
use App\Models\Invitation;
use App\Models\JobDescriptionVersion;
use App\Models\Permission;
use App\Models\PermissionChangeRequest;
use App\Models\Requirement;
use App\Models\Role;
use App\Models\User;
use App\Policies\AssignmentPolicy;
use App\Policies\CandidatePolicy;
use App\Policies\CandidateRecordPolicy;
use App\Policies\InvitationPolicy;
use App\Policies\JobDescriptionPolicy;
use App\Policies\PermissionChangeRequestPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RecruitmentPolicy;
use App\Policies\RequirementPolicy;
use App\Policies\RequirementRecordPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Services\AgenticRagJdDraftingProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(JdDraftingProvider::class, function (): JdDraftingProvider {
            return $this->app->make(AgenticRagJdDraftingProvider::class);
        });
        $this->app->singleton(ResumeTextExtractor::class, PrivateDocumentResumeTextExtractor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Invitation::class, InvitationPolicy::class);
        Gate::policy(Candidate::class, CandidateRecordPolicy::class);
        Gate::policy(CandidateDocument::class, CandidateRecordPolicy::class);
        Gate::policy(CandidateRecruiterAccess::class, CandidateRecordPolicy::class);
        Gate::policy(PermissionChangeRequest::class, PermissionChangeRequestPolicy::class);
        Gate::policy(Requirement::class, RequirementRecordPolicy::class);
        Gate::policy(JobDescriptionVersion::class, JobDescriptionPolicy::class);

        Gate::define('recruitment.view_all', [RecruitmentPolicy::class, 'viewAll']);
        Gate::define('recruitment.intervene', [RecruitmentPolicy::class, 'intervene']);
        Gate::define('resources.allocate', [RecruitmentPolicy::class, 'allocateResources']);
        Gate::define('recruitment.escalate', [RecruitmentPolicy::class, 'escalate']);
        Gate::define('requirements.view_assigned', [RequirementPolicy::class, 'viewAssigned']);
        Gate::define('requirements.prioritize', [RequirementPolicy::class, 'prioritize']);
        Gate::define('candidates.screen', [CandidatePolicy::class, 'screen']);
        Gate::define('candidates.submit', [CandidatePolicy::class, 'submit']);
        Gate::define('recruiters.assign', [AssignmentPolicy::class, 'assignRecruiters']);
        Gate::define('recruiters.manage_workload', [AssignmentPolicy::class, 'manageWorkload']);
        Gate::define('system.manage', fn (User $user): bool => $user->hasPermission('system.manage'));
        Gate::define('admin.support', fn (User $user): bool => $user->hasPermission('admin.support'));
    }
}
