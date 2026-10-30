<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('roles.view');
    }

    public function update(User $actor, Role $role): bool
    {
        return $actor->hasPermission('permissions.manage');
    }

    public function assign(User $actor): bool
    {
        return $actor->hasPermission('roles.assign');
    }
}
