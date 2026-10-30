<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CandidateRecruiterAccess;
use App\Models\ClientOrganization;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CandidateController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Candidate::class);
        $query = Candidate::query()->with('activeAccessRecords.recruiter');

        if (! $this->canViewOrganization($request->user())) {
            $query->whereHas('activeAccessRecords', fn (Builder $access) => $access->where('recruiter_id', $request->user()->getKey()));
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery->where('reference', 'like', '%'.$search.'%')
                    ->orWhere('full_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('mobile', 'like', '%'.$search.'%');
            });
        }

        if (in_array($request->query('status'), Candidate::STATUSES, true)) {
            $query->where('status', $request->query('status'));
        }

        return view('candidates.index', [
            'candidates' => $query->latest()->paginate(15)->withQueryString(),
            'canCreate' => $request->user()->can('create', Candidate::class),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Candidate::class);

        return view('candidates.create', ['candidate' => []]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Candidate::class);
        $candidateData = $this->normalizedData($this->validatedProfile($request));
        $matches = $this->findDuplicates($candidateData);
        if ($matches !== []) {
            $this->recordDuplicateDetection($request, $matches);
            throw $this->duplicateException();
        }

        try {
            $candidate = DB::transaction(function () use ($candidateData, $request): Candidate {
                $candidate = Candidate::query()->create([
                    ...$candidateData,
                    'reference' => 'CAN-'.Str::upper((string) Str::ulid()),
                    'status' => 'New',
                    'created_by' => $request->user()->getKey(),
                ]);

                CandidateRecruiterAccess::query()->create([
                    'candidate_id' => $candidate->getKey(),
                    'recruiter_id' => $request->user()->getKey(),
                    'status' => 'active',
                    'granted_by' => $request->user()->getKey(),
                    'granted_at' => now(),
                    'grant_reason' => 'Initial access for the creating Recruiter.',
                ]);

                $this->audit('candidate.created', $candidate, null, ['reference' => $candidate->reference, 'status' => 'New', 'source' => $candidate->source]);
                $this->audit('candidate.access_granted', $candidate, null, ['recruiter_id' => $request->user()->getKey(), 'status' => 'active']);

                return $candidate;
            });
        } catch (QueryException $exception) {
            if ($this->findDuplicates($candidateData) !== []) {
                $this->recordDuplicateDetection($request, $this->findDuplicates($candidateData));
                throw $this->duplicateException();
            }

            throw $exception;
        }

        return redirect()->route('candidates.show', $candidate)->with('status', 'Candidate profile created. Access is limited to authorized Recruiters.');
    }

    public function show(Request $request, Candidate $candidate): View
    {
        $this->authorize('view', $candidate);
        $candidate->load([
            'creator', 'documents' => fn ($query) => $query->where('status', 'active')->with('uploader')->latest('uploaded_at'),
            'accessRecords.recruiter', 'accessRecords.grantedBy', 'accessRecords.revokedBy',
            'consents.clientOrganization', 'consents.requirement', 'consents.recorder', 'consents.withdrawnBy',
            'notes.author', 'requirements',
        ]);

        $candidate->setRelation('consents', $candidate->consents->filter(
            fn ($consent) => $request->user()->can('view', $consent->requirement),
        )->values());
        $visibleRequirements = $candidate->requirements->filter(fn (Requirement $requirement) => $request->user()->can('view', $requirement))->values();
        $eligibleRequirements = $this->eligibleRequirements($request->user());
        $linkedIds = $visibleRequirements->pluck('id');
        $consentRequirements = $eligibleRequirements->filter(
            fn (Requirement $requirement) => $linkedIds->contains($requirement->getKey())
                && ! $candidate->hasValidConsentFor($requirement->client_organization_id, $requirement->getKey()),
        )->values();
        $clientOrganizations = ClientOrganization::query()
            ->whereIn('id', $eligibleRequirements->pluck('client_organization_id')->unique())
            ->orderBy('name')->get();
        $activities = AuditLog::query()->with('actor')
            ->where('entity_type', Candidate::class)
            ->where('entity_id', (string) $candidate->getKey())
            ->latest('created_at')->limit(100)->get()
            ->filter(function (AuditLog $activity) use ($request): bool {
                $requirementId = $activity->context['requirement_id'] ?? $activity->new_state['requirement_id'] ?? null;
                if ($requirementId === null) {
                    return true;
                }

                $requirement = Requirement::query()->find($requirementId);

                return $requirement !== null && $request->user()->can('view', $requirement);
            })->take(50)->values();

        $this->audit('candidate.viewed', $candidate, null, null, ['reference' => $candidate->reference]);

        return view('candidates.show', [
            'candidate' => $candidate,
            'visibleRequirements' => $visibleRequirements,
            'eligibleRequirements' => $eligibleRequirements,
            'consentRequirements' => $consentRequirements,
            'clientOrganizations' => $clientOrganizations,
            'activities' => $activities,
            'canManageAccess' => $request->user()->can('manageAccess', $candidate),
            'canUpdate' => $request->user()->can('update', $candidate),
        ]);
    }

    public function edit(Candidate $candidate): View
    {
        $this->authorize('update', $candidate);

        return view('candidates.edit', ['candidate' => $candidate]);
    }

    public function update(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->authorize('update', $candidate);
        $data = $this->normalizedData($this->validatedProfile($request, true));
        $matches = $this->findDuplicates($data, $candidate->getKey());
        if ($matches !== []) {
            $this->recordDuplicateDetection($request, $matches);
            throw $this->duplicateException();
        }

        $candidate->fill($data);
        $changedFields = array_keys($candidate->getDirty());
        $candidate->save();
        if ($changedFields !== []) {
            $this->audit('candidate.updated', $candidate, ['changed_fields' => $changedFields], ['changed_fields' => $changedFields]);
        }

        return redirect()->route('candidates.show', $candidate)->with('status', 'Candidate profile updated.');
    }

    public function changeStatus(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->authorize('changeStatus', $candidate);
        $validated = $request->validate([
            'status' => ['required', 'in:New,Active,On Hold,Not Available,Inactive'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $previous = $candidate->status;
        $candidate->forceFill(['status' => $validated['status']])->save();
        $this->audit('candidate.status_changed', $candidate, ['status' => $previous], ['status' => $candidate->status], ['reason' => $validated['reason']]);

        return back()->with('status', 'Candidate status updated.');
    }

    public function linkRequirement(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->authorize('view', $candidate);
        $validated = $request->validate([
            'requirement_id' => ['required', 'integer', 'exists:requirements,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $requirement = Requirement::query()->findOrFail($validated['requirement_id']);
        $this->authorize('linkRequirement', [$candidate, $requirement]);

        if ($candidate->requirements()->whereKey($requirement->getKey())->exists()) {
            return back()->with('status', 'Candidate is already associated with this Requirement.');
        }

        $candidate->requirements()->attach($requirement->getKey(), [
            'linked_by' => $request->user()->getKey(),
            'linked_at' => now(),
            'reason' => $validated['reason'] ?? null,
        ]);
        $this->audit('candidate.requirement_linked', $candidate, ['requirement_id' => $requirement->getKey()], null, ['reason' => $validated['reason'] ?? null]);

        return back()->with('status', 'Candidate linked to the Requirement internally. No client submission was made.');
    }

    private function validatedProfile(Request $request, bool $updating = false): array
    {
        $request->merge([
            'email' => is_string($request->input('email')) ? trim($request->input('email')) : $request->input('email'),
            'mobile' => is_string($request->input('mobile')) ? trim($request->input('mobile')) : $request->input('mobile'),
        ]);

        return $request->validate([
            'full_name' => ['required', 'string', 'max:180'],
            'email' => ['nullable', 'required_without:mobile', 'email', 'max:254'],
            'mobile' => [
                'nullable', 'required_without:email', 'string', 'max:40',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value !== null && $value !== '' && preg_match_all('/\d/', (string) $value) < 5) {
                        $fail('Enter a mobile number with at least five digits.');
                    }
                },
            ],
            'alternate_contact' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:180'],
            'current_designation' => ['nullable', 'string', 'max:180'],
            'total_experience_years' => ['nullable', 'numeric', 'min:0', 'max:80'],
            'relevant_experience_years' => ['nullable', 'numeric', 'min:0', 'lte:total_experience_years', 'max:80'],
            'primary_skills' => ['nullable', 'string', 'max:10000'],
            'secondary_skills' => ['nullable', 'string', 'max:10000'],
            'highest_qualification' => ['nullable', 'string', 'max:180'],
            'current_employer' => ['nullable', 'string', 'max:180'],
            'current_ctc' => ['nullable', 'string', 'max:100'],
            'expected_ctc' => ['nullable', 'string', 'max:100'],
            'notice_period_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'preferred_location' => ['nullable', 'string', 'max:180'],
            'preferred_work_mode' => ['nullable', 'in:On-site,Hybrid,Remote,Flexible'],
            'availability' => ['nullable', 'string', 'max:64'],
            'profile_summary' => ['nullable', 'string', 'max:30000'],
            'source' => ['required', 'in:'.implode(',', Candidate::SOURCES)],
            'source_details' => ['nullable', 'string', 'max:180'],
        ]);
    }

    private function normalizedData(array $data): array
    {
        $data['email'] = isset($data['email']) && trim($data['email']) !== '' ? trim($data['email']) : null;
        $data['email_normalized'] = $data['email'] !== null ? mb_strtolower($data['email']) : null;
        $data['mobile'] = isset($data['mobile']) && trim($data['mobile']) !== '' ? trim($data['mobile']) : null;
        $data['mobile_normalized'] = $data['mobile'] !== null ? preg_replace('/\D+/', '', $data['mobile']) : null;
        if ($data['mobile_normalized'] === '') {
            $data['mobile_normalized'] = null;
        }

        return $data;
    }

    private function findDuplicates(array $data, ?int $exceptId = null): array
    {
        $query = Candidate::query();
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $email = $data['email_normalized'] ?? null;
        $mobile = $data['mobile_normalized'] ?? null;
        if ($email === null && $mobile === null) {
            return [];
        }

        $query->where(function (Builder $match) use ($email, $mobile): void {
            if ($email !== null) {
                $match->orWhere('email_normalized', $email);
            }
            if ($mobile !== null) {
                $match->orWhere('mobile_normalized', $mobile);
            }
        });

        return $query->get(['id', 'email_normalized', 'mobile_normalized'])->map(function (Candidate $candidate) use ($email, $mobile): array {
            return [
                'email' => $email !== null && $candidate->email_normalized === $email,
                'mobile' => $mobile !== null && $candidate->mobile_normalized === $mobile,
            ];
        })->all();
    }

    private function recordDuplicateDetection(Request $request, array $matches): void
    {
        $fields = [];
        foreach ($matches as $match) {
            if ($match['email']) {
                $fields[] = 'email';
            }
            if ($match['mobile']) {
                $fields[] = 'mobile';
            }
        }

        AuditLog::record('candidate.duplicate_detected', [
            'entity_type' => Candidate::class,
            'entity_id' => null,
            'context' => ['matched_fields' => array_values(array_unique($fields))],
        ]);
    }

    private function duplicateException(): ValidationException
    {
        return ValidationException::withMessages([
            'contact' => 'A candidate with matching contact details may already exist. No second profile was created. Contact a Recruitment Manager to locate the existing profile and request access.',
        ]);
    }

    private function eligibleRequirements(User $user)
    {
        $query = Requirement::query()->whereIn('status', ['Approved', 'Active'])->orderBy('reference');
        if (! $this->canViewOrganization($user)) {
            $query->whereHas('activeAssignments', fn (Builder $assignments) => $assignments->where('recruiter_id', $user->getKey()));
        }

        return $query->with('clientOrganization')->get();
    }

    private function canViewOrganization(User $user): bool
    {
        return ($user->hasRole('recruitment-manager') || $user->hasRole('management'))
            && $user->hasPermission('recruitment.view_all');
    }

    private function audit(string $action, Candidate $candidate, ?array $previous, ?array $next, array $context = []): void
    {
        AuditLog::record($action, [
            'entity_type' => Candidate::class,
            'entity_id' => (string) $candidate->getKey(),
            'previous_state' => $previous,
            'new_state' => $next,
            'context' => $context,
        ]);
    }
}
