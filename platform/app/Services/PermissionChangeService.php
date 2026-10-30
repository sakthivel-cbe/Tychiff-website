<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\PermissionChangeRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PermissionChangeService
{
    public function requestChange(User $requester, int $roleId, int $permissionId, bool $requestedState, string $reason): PermissionChangeRequest
    {
        Gate::forUser($requester)->authorize('create', PermissionChangeRequest::class);

        return DB::transaction(function () use ($requester, $roleId, $permissionId, $requestedState, $reason): PermissionChangeRequest {
            $role = Role::query()->lockForUpdate()->findOrFail($roleId);
            $permission = Permission::query()->findOrFail($permissionId);
            $currentState = $role->permissions()->whereKey($permission->getKey())->exists();

            if ($currentState === $requestedState) {
                throw ValidationException::withMessages([
                    'requested_state' => 'The requested state already matches the active permission state.',
                ]);
            }

            $pendingExists = PermissionChangeRequest::query()
                ->where('role_id', $role->id)
                ->where('permission_id', $permission->id)
                ->where('status', PermissionChangeRequest::STATUS_PENDING)
                ->exists();

            if ($pendingExists) {
                throw ValidationException::withMessages([
                    'permission_id' => 'A pending request already exists for this role and permission.',
                ]);
            }

            $changeRequest = PermissionChangeRequest::create([
                'requester_id' => $requester->id,
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'reason' => $reason,
                'current_state' => $currentState,
                'requested_state' => $requestedState,
                'status' => PermissionChangeRequest::STATUS_PENDING,
                'requested_at' => now(),
            ]);

            $this->audit($requester, 'permissions.change_requested', $changeRequest, [
                'role_id' => $role->id,
                'permission' => $permission->name,
                'active' => $currentState,
            ], [
                'request_id' => $changeRequest->id,
                'requested_state' => $requestedState,
                'status' => PermissionChangeRequest::STATUS_PENDING,
            ]);

            return $changeRequest;
        });
    }

    public function approve(User $reviewer, PermissionChangeRequest $changeRequest, string $decisionReason): PermissionChangeRequest
    {
        return DB::transaction(function () use ($reviewer, $changeRequest, $decisionReason): PermissionChangeRequest {
            $lockedRequest = PermissionChangeRequest::query()->lockForUpdate()->findOrFail($changeRequest->id);
            Gate::forUser($reviewer)->authorize('approve', $lockedRequest);

            $role = Role::query()->lockForUpdate()->findOrFail($lockedRequest->role_id);
            $permission = Permission::query()->findOrFail($lockedRequest->permission_id);
            $active = $role->permissions()->whereKey($permission->getKey())->exists();

            if ($active !== $lockedRequest->current_state) {
                throw new ConflictHttpException('The active permission state changed after this request was submitted.');
            }

            if ($lockedRequest->requested_state) {
                $role->permissions()->attach($permission->id, [
                    'approved_by' => $reviewer->id,
                    'approved_at' => now(),
                ]);
            } else {
                $role->permissions()->detach($permission->id);
            }

            $lockedRequest->forceFill([
                'status' => PermissionChangeRequest::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'decision_reason' => $decisionReason,
            ])->save();

            $this->audit($reviewer, 'permissions.change_approved', $lockedRequest, [
                'role_id' => $role->id,
                'permission' => $permission->name,
                'active' => $active,
                'status' => PermissionChangeRequest::STATUS_PENDING,
            ], [
                'active' => $lockedRequest->requested_state,
                'status' => $lockedRequest->status,
                'request_id' => $lockedRequest->id,
            ]);

            return $lockedRequest->refresh();
        });
    }

    public function reject(User $reviewer, PermissionChangeRequest $changeRequest, string $decisionReason): PermissionChangeRequest
    {
        return DB::transaction(function () use ($reviewer, $changeRequest, $decisionReason): PermissionChangeRequest {
            $lockedRequest = PermissionChangeRequest::query()->lockForUpdate()->findOrFail($changeRequest->id);
            Gate::forUser($reviewer)->authorize('reject', $lockedRequest);

            $lockedRequest->forceFill([
                'status' => PermissionChangeRequest::STATUS_REJECTED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'decision_reason' => $decisionReason,
            ])->save();

            $this->audit($reviewer, 'permissions.change_rejected', $lockedRequest, [
                'status' => PermissionChangeRequest::STATUS_PENDING,
                'requested_state' => $lockedRequest->requested_state,
            ], [
                'status' => $lockedRequest->status,
                'active_state_unchanged' => true,
                'request_id' => $lockedRequest->id,
            ]);

            return $lockedRequest->refresh();
        });
    }

    public function cancel(User $requester, PermissionChangeRequest $changeRequest): PermissionChangeRequest
    {
        return DB::transaction(function () use ($requester, $changeRequest): PermissionChangeRequest {
            $lockedRequest = PermissionChangeRequest::query()->lockForUpdate()->findOrFail($changeRequest->id);
            Gate::forUser($requester)->authorize('cancel', $lockedRequest);

            $lockedRequest->forceFill([
                'status' => PermissionChangeRequest::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ])->save();

            $this->audit($requester, 'permissions.change_cancelled', $lockedRequest, [
                'status' => PermissionChangeRequest::STATUS_PENDING,
                'requested_state' => $lockedRequest->requested_state,
            ], [
                'status' => $lockedRequest->status,
                'active_state_unchanged' => true,
                'request_id' => $lockedRequest->id,
            ]);

            return $lockedRequest->refresh();
        });
    }

    private function audit(User $actor, string $action, PermissionChangeRequest $changeRequest, array $previous, array $next): void
    {
        AuditLog::record($action, [
            'user_id' => $actor->id,
            'entity_type' => PermissionChangeRequest::class,
            'entity_id' => (string) $changeRequest->id,
            'previous_state' => $previous,
            'new_state' => $next,
        ]);
    }
}
