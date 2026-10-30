<?php

namespace App\Policies;

use App\Models\User;

/** Action-level permission gates; record-level authorization belongs to RequirementRecordPolicy. */
class RequirementPolicy
{
    public function viewAssigned(User $user): bool
    {
        return $user->hasPermission('requirements.view_assigned');
    }

    public function prioritize(User $user): bool
    {
        return $user->hasPermission('requirements.prioritize');
    }
}
