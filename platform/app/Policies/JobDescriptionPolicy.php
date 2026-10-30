<?php

namespace App\Policies;

use App\Models\JobDescriptionVersion;
use App\Models\Requirement;
use App\Models\User;

class JobDescriptionPolicy
{
    public function view(User $user, JobDescriptionVersion $jd): bool
    {
        return $user->can('view', $jd->requirement);
    }

    public function createForRequirement(User $user, Requirement $requirement): bool
    {
        if (! in_array($requirement->status, ['Approved', 'Active'], true) || ! $user->can('view', $requirement)) {
            return false;
        }

        return ($user->hasRole('recruiter') && $user->hasPermission('requirements.view_assigned'))
            || ($user->hasRole('recruitment-manager') && $user->hasPermission('recruiters.assign'));
    }

    public function edit(User $user, JobDescriptionVersion $jd): bool
    {
        if (! in_array($jd->status, ['Draft', 'AI Draft', 'Recruiter Review'], true)
            || ! in_array($jd->requirement->status, ['Approved', 'Active'], true)
            || ! $user->can('view', $jd->requirement)) {
            return false;
        }

        return ($user->hasRole('recruiter') && $user->hasPermission('requirements.view_assigned'))
            || ($user->hasRole('recruitment-manager') && $user->hasPermission('recruiters.assign'));
    }

    public function submitForReview(User $user, JobDescriptionVersion $jd): bool
    {
        return $user->hasRole('recruiter') && $user->hasPermission('requirements.view_assigned')
            && $user->can('view', $jd->requirement)
            && in_array($jd->requirement->status, ['Approved', 'Active'], true)
            && in_array($jd->status, ['Draft', 'AI Draft'], true);
    }

    public function sendForClientApproval(User $user, JobDescriptionVersion $jd): bool
    {
        return $user->hasRole('recruitment-manager') && $user->hasPermission('recruiters.assign')
            && $user->can('view', $jd->requirement)
            && in_array($jd->requirement->status, ['Approved', 'Active'], true)
            && $jd->status === 'Recruiter Review';
    }

    public function returnToRecruiter(User $user, JobDescriptionVersion $jd): bool
    {
        return $user->hasRole('recruitment-manager') && $user->hasPermission('recruiters.assign')
            && $user->can('view', $jd->requirement)
            && in_array($jd->requirement->status, ['Approved', 'Active'], true)
            && $jd->status === 'Recruiter Review';
    }

    public function recordClientDecision(User $user, JobDescriptionVersion $jd): bool
    {
        return $user->hasRole('management') && $user->hasPermission('recruitment.intervene')
            && $user->can('view', $jd->requirement) && $jd->status === 'Sent for Client Approval';
    }

    public function createNextVersion(User $user, JobDescriptionVersion $jd): bool
    {
        return (($user->hasRole('recruitment-manager') && $user->hasPermission('recruiters.assign'))
                || ($user->hasRole('recruiter') && $user->hasPermission('requirements.view_assigned')))
            && $user->can('view', $jd->requirement)
            && in_array($jd->requirement->status, ['Approved', 'Active'], true)
            && in_array($jd->status, ['Approved', 'Superseded'], true);
    }

    public function generateDraft(User $user, Requirement $requirement): bool
    {
        return $this->createForRequirement($user, $requirement);
    }
}
