<?php

namespace App\Policies;

use App\Models\Requirement;
use App\Models\User;

class RequirementRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('recruitment.view_all')
            || ($user->hasRole('recruiter') && $user->hasPermission('requirements.view_assigned'));
    }

    public function view(User $user, Requirement $requirement): bool
    {
        if ($user->hasPermission('recruitment.view_all')) {
            return true;
        }

        return $user->hasRole('recruiter')
            && $user->hasPermission('requirements.view_assigned')
            && $requirement->isAssignedTo($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('management') && $user->hasPermission('recruitment.view_all');
    }

    public function update(User $user, Requirement $requirement): bool
    {
        return $user->hasRole('management') && $user->hasPermission('recruitment.view_all')
            && $requirement->status === 'Pending Review';
    }

    public function review(User $user, Requirement $requirement): bool
    {
        return $user->hasRole('recruitment-manager') && $user->hasPermission('recruitment.view_all')
            && $requirement->status === 'Pending Review';
    }

    public function activate(User $user, Requirement $requirement): bool
    {
        return $user->hasRole('recruitment-manager') && $user->hasPermission('recruiters.assign')
            && $requirement->status === 'Approved' && $requirement->hasApprovedJobDescription();
    }

    public function assign(User $user, Requirement $requirement): bool
    {
        return $user->hasRole('recruitment-manager') && $user->hasPermission('recruiters.assign')
            && in_array($requirement->status, ['Approved', 'Active', 'At Risk', 'On Hold'], true);
    }

    public function prioritize(User $user, Requirement $requirement): bool
    {
        return $user->hasRole('recruitment-manager') && $user->hasPermission('requirements.prioritize')
            && $requirement->status !== 'Closed';
    }

    public function reviseTargetDate(User $user, Requirement $requirement): bool
    {
        return $user->hasRole('recruitment-manager') && $user->hasPermission('recruitment.view_all')
            && $requirement->status !== 'Closed';
    }

    public function changeStatus(User $user, Requirement $requirement): bool
    {
        return ($user->hasRole('recruitment-manager') && $user->hasPermission('recruitment.view_all'))
            || ($user->hasRole('management') && $user->hasPermission('recruitment.intervene'));
    }

    public function reportRisk(User $user, Requirement $requirement): bool
    {
        return $user->hasRole('recruiter') && $user->hasPermission('requirements.view_assigned')
            && $requirement->isAssignedTo($user) && $requirement->status === 'Active';
    }
}
