<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class InvitationPolicy
{
    private const STAFF_ROLE_SLUGS = [
        'management',
        'super-admin',
        'recruitment-manager',
        'recruiter',
        'admin',
    ];

    public function create(User $actor): bool
    {
        return $actor->hasPermission('users.invite');
    }

    /** @param Collection<int, Role> $roles */
    public function assignRoles(User $actor, Collection $roles): bool
    {
        return $actor->hasPermission('roles.assign')
            && $roles->isNotEmpty()
            && $roles->every(fn (Role $role): bool => in_array($role->slug, self::STAFF_ROLE_SLUGS, true));
    }
}
