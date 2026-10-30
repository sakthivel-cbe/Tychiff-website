<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

class PermissionPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('roles.view');
    }

    public function approve(User $actor, Permission $permission): bool
    {
        return $actor->hasPermission('permissions.approve');
    }
}
