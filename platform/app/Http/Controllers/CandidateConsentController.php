<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CandidateConsent;
use App\Models\Requirement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CandidateConsentController extends Controller
{
    public function store(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->authorize('recordConsent', $candidate);
        $validated = $request->validate([
            'requirement_id' => ['required', 'integer', 'exists:requirements,id'],
            'consented_at' => ['required', 'date', 'before_or_equal:now'],
            'consent_method' => ['required', 'in:written,email,phone,in_person,other'],
            'consent_text_reference' => ['required', 'string', 'min:3', 'max:255'],
            'candidate_permission_confirmed' => ['required', 'accepted'],
        ]);

        $requirement = Requirement::query()->findOrFail($validated['requirement_id']);
        $this->authorize('view', $requirement);

        if (! in_array($requirement->status, ['Approved', 'Active'], true)) {
            throw ValidationException::withMessages(['requirement_id' => 'Consent can be recorded only for an approved or active Requirement.']);
        }
        if (! $candidate->requirements()->whereKey($requirement->getKey())->exists()) {
            throw ValidationException::withMessages(['requirement_id' => 'Link this Candidate to the Requirement before recording consent.']);
        }

        $result = DB::transaction(function () use ($request, $candidate, $requirement, $validated): array {
            Candidate::query()->lockForUpdate()->findOrFail($candidate->getKey());
            $existing = CandidateConsent::query()
                ->where('candidate_id', $candidate->getKey())
                ->where('client_organization_id', $requirement->client_organization_id)
                ->where('requirement_id', $requirement->getKey())
                ->where('status', 'valid')
                ->whereNull('withdrawn_at')
                ->latest('consented_at')
                ->first();

            if ($existing) {
                return ['created' => false, 'consent' => $existing];
            }

            $consent = CandidateConsent::query()->create([
                'candidate_id' => $candidate->getKey(),
                'client_organization_id' => $requirement->client_organization_id,
                'requirement_id' => $requirement->getKey(),
                'status' => 'valid',
                'consented_at' => $validated['consented_at'],
                'consent_method' => $validated['consent_method'],
                'consent_text_reference' => $validated['consent_text_reference'],
                'recorded_by' => $request->user()->getKey(),
            ]);

            AuditLog::record('candidate.consent_recorded', [
                'entity_type' => Candidate::class,
                'entity_id' => (string) $candidate->getKey(),
                'new_state' => ['consent_id' => $consent->getKey(), 'status' => 'valid'],
                'context' => [
                    'client_organization_id' => $requirement->client_organization_id,
                    'requirement_id' => $requirement->getKey(),
                    'consent_method' => $consent->consent_method,
                    'consent_text_reference' => $consent->consent_text_reference,
                ],
            ]);

            return ['created' => true, 'consent' => $consent];
        });

        return back()->with('status', $result['created']
            ? 'Candidate consent recorded for this Client and Requirement only.'
            : 'Valid consent already exists for this Client and Requirement.');
    }

    public function withdraw(Request $request, CandidateConsent $consent): RedirectResponse
    {
        $candidate = $consent->candidate;
        $this->authorize('recordConsent', $candidate);
        $requirement = $consent->requirement;
        $this->authorize('view', $requirement);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:2000']]);

        DB::transaction(function () use ($request, $consent, $candidate, $validated): void {
            $locked = CandidateConsent::query()->lockForUpdate()->findOrFail($consent->getKey());
            if ($locked->status !== 'valid' || $locked->withdrawn_at !== null) {
                return;
            }

            $locked->forceFill([
                'status' => 'withdrawn',
                'withdrawn_at' => now(),
                'withdrawn_by' => $request->user()->getKey(),
                'withdrawal_reason' => $validated['reason'],
            ])->save();

            AuditLog::record('candidate.consent_withdrawn', [
                'entity_type' => Candidate::class,
                'entity_id' => (string) $candidate->getKey(),
                'previous_state' => ['consent_id' => $locked->getKey(), 'status' => 'valid'],
                'new_state' => ['consent_id' => $locked->getKey(), 'status' => 'withdrawn'],
                'context' => [
                    'client_organization_id' => $locked->client_organization_id,
                    'requirement_id' => $locked->requirement_id,
                    'reason' => $validated['reason'],
                ],
            ]);
        });

        return back()->with('status', 'Consent withdrawal recorded. The historical consent was retained.');
    }
}
