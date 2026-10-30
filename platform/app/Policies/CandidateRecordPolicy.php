<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\CandidateRecruiterAccess;
use App\Models\Requirement;
use App\Models\User;

class CandidateRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canViewOrganization($user)
            || ($user->hasRole('recruiter') && $user->hasPermission('candidates.screen'));
    }

    public function view(User $user, Candidate $candidate): bool
    {
        return $this->canViewOrganization($user)
            || ($user->hasRole('recruiter') && $user->hasPermission('candidates.screen') && $candidate->hasActiveAccess($user));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('recruiter') && $user->hasPermission('candidates.screen');
    }

    public function update(User $user, Candidate $candidate): bool
    {
        return ($user->hasRole('recruitment-manager') && $user->hasPermission('recruitment.view_all'))
            || ($user->hasRole('recruiter') && $user->hasPermission('candidates.screen') && $candidate->hasActiveAccess($user));
    }

    public function manageAccess(User $user, Candidate $candidate): bool
    {
        return $this->view($user, $candidate) && $this->canManageAccess($user);
    }

    public function changeStatus(User $user, Candidate $candidate): bool
    {
        return $this->view($user, $candidate) && $this->canManageAccess($user);
    }

    public function recordConsent(User $user, Candidate $candidate): bool
    {
        return $this->view($user, $candidate);
    }

    public function addNote(User $user, Candidate $candidate): bool
    {
        return $this->view($user, $candidate);
    }

    public function uploadDocument(User $user, Candidate $candidate): bool
    {
        return $this->view($user, $candidate);
    }

    public function viewDocument(User $user, CandidateDocument $document): bool
    {
        return $document->status === 'active' && $this->view($user, $document->candidate);
    }

    public function grantAccess(User $user, Candidate $candidate): bool
    {
        return $this->manageAccess($user, $candidate);
    }

    public function revokeAccess(User $user, CandidateRecruiterAccess $access): bool
    {
        return $access->candidate !== null && $this->manageAccess($user, $access->candidate);
    }

    public function linkRequirement(User $user, Candidate $candidate, Requirement $requirement): bool
    {
        return $this->view($user, $candidate) && $user->can('view', $requirement)
            && in_array($requirement->status, ['Approved', 'Active'], true);
    }

    private function canViewOrganization(User $user): bool
    {
        return ($user->hasRole('recruitment-manager') || $user->hasRole('management'))
            && $user->hasPermission('recruitment.view_all');
    }

    private function canManageAccess(User $user): bool
    {
        return ($user->hasRole('recruitment-manager') && $user->hasPermission('recruiters.assign'))
            || ($user->hasRole('management') && $user->hasPermission('recruitment.intervene'));
    }
}
