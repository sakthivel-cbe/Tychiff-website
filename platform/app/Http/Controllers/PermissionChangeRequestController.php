<?php

namespace App\Http\Controllers;

use App\Models\PermissionChangeRequest;
use App\Services\PermissionChangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionChangeRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PermissionChangeRequest::class);

        $filters = $request->validate([
            'status' => ['sometimes', Rule::in([
                PermissionChangeRequest::STATUS_PENDING,
                PermissionChangeRequest::STATUS_APPROVED,
                PermissionChangeRequest::STATUS_REJECTED,
                PermissionChangeRequest::STATUS_CANCELLED,
            ])],
        ]);

        $query = PermissionChangeRequest::query()
            ->with(['role:id,name', 'permission:id,name', 'requester:id,name', 'reviewer:id,name'])
            ->orderByDesc('requested_at');

        if (! $request->user()->hasPermission('permissions.approve')) {
            $query->where('requester_id', $request->user()->id);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return response()->json(['data' => $query->get()->map(fn (PermissionChangeRequest $change): array => [
            ...$this->summary($change),
            'reason' => $change->reason,
            'decision_reason' => $change->decision_reason,
            'requester' => $change->requester?->name,
            'reviewer' => $change->reviewer?->name,
        ])]);
    }

    public function show(Request $request, PermissionChangeRequest $changeRequest): JsonResponse
    {
        $this->authorize('view', $changeRequest);

        return response()->json([
            ...$this->summary($changeRequest),
            'reason' => $changeRequest->reason,
            'decision_reason' => $changeRequest->decision_reason,
        ]);
    }

    public function store(Request $request, PermissionChangeService $service): JsonResponse
    {
        $input = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
            'requested_state' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $changeRequest = $service->requestChange(
            $request->user(),
            (int) $input['role_id'],
            (int) $input['permission_id'],
            (bool) $input['requested_state'],
            $input['reason'],
        );

        return response()->json($this->summary($changeRequest), 201);
    }

    public function approve(Request $request, PermissionChangeRequest $changeRequest, PermissionChangeService $service): JsonResponse
    {
        $input = $request->validate(['decision_reason' => ['required', 'string', 'min:5', 'max:2000']]);
        $updated = $service->approve($request->user(), $changeRequest, $input['decision_reason']);

        return response()->json($this->summary($updated));
    }

    public function reject(Request $request, PermissionChangeRequest $changeRequest, PermissionChangeService $service): JsonResponse
    {
        $input = $request->validate(['decision_reason' => ['required', 'string', 'min:5', 'max:2000']]);
        $updated = $service->reject($request->user(), $changeRequest, $input['decision_reason']);

        return response()->json($this->summary($updated));
    }

    public function cancel(Request $request, PermissionChangeRequest $changeRequest, PermissionChangeService $service): JsonResponse
    {
        $updated = $service->cancel($request->user(), $changeRequest);

        return response()->json($this->summary($updated));
    }

    private function summary(PermissionChangeRequest $request): array
    {
        return [
            'id' => $request->id,
            'role_id' => $request->role_id,
            'permission_id' => $request->permission_id,
            'current_state' => $request->current_state,
            'requested_state' => $request->requested_state,
            'status' => $request->status,
            'requested_at' => $request->requested_at->toIso8601String(),
            'reviewed_at' => $request->reviewed_at?->toIso8601String(),
            'cancelled_at' => $request->cancelled_at?->toIso8601String(),
        ];
    }
}
