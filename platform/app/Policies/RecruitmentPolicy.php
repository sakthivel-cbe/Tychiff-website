<?php

namespace App\Policies;

use App\Models\User;

/** Action-level gates only; record-level checks belong with future recruitment models. */
class RecruitmentPolicy
{
    public function viewAll(User $user): bool
    {
        return $user->hasPermission('recruitment.view_all');
    }

    public function intervene(User $user): bool
    {
        return $user->hasPermission('recruitment.intervene');
    }

    public function allocateResources(User $user): bool
    {
        return $user->hasPermission('resources.allocate');
    }

    public function escalate(User $user): bool
    {
        return $user->hasPermission('recruitment.escalate');
    }
}
