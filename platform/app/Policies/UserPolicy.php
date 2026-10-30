<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('users.view');
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->is($user) || $actor->hasPermission('users.view');
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->is($user) || $actor->hasPermission('users.manage');
    }

    public function delete(User $actor, User $user): bool
    {
        return ! $actor->is($user) && $actor->hasPermission('users.delete_permanently');
    }
}
