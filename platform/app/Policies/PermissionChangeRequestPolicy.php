<?php

namespace App\Policies;

use App\Models\PermissionChangeRequest;
use App\Models\User;

class PermissionChangeRequestPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('permissions.manage') || $actor->hasPermission('permissions.approve');
    }

    public function view(User $actor, PermissionChangeRequest $request): bool
    {
        return $request->requester_id === $actor->id || $actor->hasPermission('permissions.approve');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission('permissions.manage');
    }

    public function approve(User $actor, PermissionChangeRequest $request): bool
    {
        return $request->status === PermissionChangeRequest::STATUS_PENDING
            && $request->requester_id !== $actor->id
            && $actor->hasPermission('permissions.approve');
    }

    public function reject(User $actor, PermissionChangeRequest $request): bool
    {
        return $this->approve($actor, $request);
    }

    public function cancel(User $actor, PermissionChangeRequest $request): bool
    {
        return $request->status === PermissionChangeRequest::STATUS_PENDING
            && $request->requester_id === $actor->id;
    }
}
