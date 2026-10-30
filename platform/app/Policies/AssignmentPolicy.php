<?php

namespace App\Policies;

use App\Models\User;

/** Permission boundary only; no assignment workflow or endpoint is implemented here. */
class AssignmentPolicy
{
    public function assignRecruiters(User $user): bool
    {
        return $user->hasPermission('recruiters.assign');
    }

    public function manageWorkload(User $user): bool
    {
        return $user->hasPermission('recruiters.manage_workload');
    }
}
