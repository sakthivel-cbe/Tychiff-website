<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CandidateRecruiterAccess;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CandidateAccessController extends Controller
{
    public function index(Candidate $candidate): View
    {
        $this->authorize('manageAccess', $candidate);

        return view('candidates.access', [
            'candidate' => $candidate,
            'accessRecords' => $candidate->accessRecords()->with(['recruiter', 'grantedBy', 'revokedBy'])->latest()->get(),
            'recruiters' => User::query()->where('status', 'active')
                ->whereHas('roles', fn (Builder $roles) => $roles->where('slug', 'recruiter'))
                ->orderBy('name')->get(),
        ]);
    }

    public function grant(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->authorize('grantAccess', $candidate);
        $validated = $request->validate([
            'recruiter_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $recruiter = User::query()->whereKey($validated['recruiter_id'])
            ->where('status', 'active')
            ->whereHas('roles', fn (Builder $roles) => $roles->where('slug', 'recruiter'))
            ->first();
        if (! $recruiter) {
            throw ValidationException::withMessages(['recruiter_id' => 'Choose an active user with the Recruiter role.']);
        }

        $result = DB::transaction(function () use ($request, $candidate, $recruiter, $validated): string {
            Candidate::query()->lockForUpdate()->findOrFail($candidate->getKey());
            $access = CandidateRecruiterAccess::query()->where('candidate_id', $candidate->getKey())
                ->where('recruiter_id', $recruiter->getKey())->lockForUpdate()->first();

            if ($access?->status === 'active') {
                return 'already_active';
            }

            $previous = $access?->only(['status', 'granted_at', 'revoked_at']);
            if ($access) {
                $access->forceFill([
                    'status' => 'active',
                    'granted_by' => $request->user()->getKey(),
                    'granted_at' => now(),
                    'grant_reason' => $validated['reason'] ?? null,
                    'revoked_by' => null,
                    'revoked_at' => null,
                    'revocation_reason' => null,
                ])->save();
            } else {
                $access = CandidateRecruiterAccess::query()->create([
                    'candidate_id' => $candidate->getKey(),
                    'recruiter_id' => $recruiter->getKey(),
                    'status' => 'active',
                    'granted_by' => $request->user()->getKey(),
                    'granted_at' => now(),
                    'grant_reason' => $validated['reason'] ?? null,
                ]);
            }

            AuditLog::record('candidate.access_granted', [
                'entity_type' => Candidate::class,
                'entity_id' => (string) $candidate->getKey(),
                'previous_state' => $previous,
                'new_state' => ['recruiter_id' => $recruiter->getKey(), 'status' => 'active'],
                'context' => ['access_id' => $access->getKey(), 'reason' => $validated['reason'] ?? null],
            ]);

            return 'granted';
        });

        return redirect()->route('candidates.access.index', $candidate)->with('status', $result === 'already_active'
            ? 'This Recruiter already has access.'
            : 'Candidate access granted.');
    }

    public function revoke(Request $request, Candidate $candidate, CandidateRecruiterAccess $access): RedirectResponse
    {
        abort_unless($access->candidate_id === $candidate->getKey(), 404);
        $this->authorize('revokeAccess', $access);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:2000']]);

        DB::transaction(function () use ($request, $candidate, $access, $validated): void {
            $locked = CandidateRecruiterAccess::query()->lockForUpdate()->findOrFail($access->getKey());
            if ($locked->status !== 'active') {
                return;
            }

            $previous = ['recruiter_id' => $locked->recruiter_id, 'status' => $locked->status];
            $locked->forceFill([
                'status' => 'revoked',
                'revoked_by' => $request->user()->getKey(),
                'revoked_at' => now(),
                'revocation_reason' => $validated['reason'],
            ])->save();

            AuditLog::record('candidate.access_revoked', [
                'entity_type' => Candidate::class,
                'entity_id' => (string) $candidate->getKey(),
                'previous_state' => $previous,
                'new_state' => ['recruiter_id' => $locked->recruiter_id, 'status' => 'revoked'],
                'context' => ['access_id' => $locked->getKey(), 'reason' => $validated['reason']],
            ]);
        });

        return back()->with('status', 'Candidate access revoked.');
    }
}
