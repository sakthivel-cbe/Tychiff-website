<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ClientOrganization;
use App\Models\Requirement;
use App\Models\RequirementTargetDateChange;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RequirementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Requirement::class);

        $query = Requirement::query()->with(['clientOrganization', 'recruitmentManager', 'activeAssignments.recruiter']);

        if (! $request->user()->hasPermission('recruitment.view_all')) {
            $query->whereHas('activeAssignments', fn (Builder $assignments) => $assignments->where('recruiter_id', $request->user()->getKey()));
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery->where('reference', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')
                    ->orWhere('job_title', 'like', '%'.$search.'%')
                    ->orWhereHas('clientOrganization', fn (Builder $client) => $client->where('name', 'like', '%'.$search.'%'));
            });
        }

        if (in_array($request->query('status'), Requirement::STATUSES, true)) {
            $query->where('status', $request->query('status'));
        }

        return view('requirements.index', [
            'requirements' => $query->latest()->paginate(15)->withQueryString(),
            'canCreate' => $request->user()->can('create', Requirement::class),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Requirement::class);

        return view('requirements.create', [
            'clients' => ClientOrganization::query()->orderBy('name')->get(),
            'spocs' => User::query()->where('status', 'active')->whereHas('roles', fn (Builder $roles) => $roles->whereIn('slug', ['management', 'recruitment-manager']))->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Requirement::class);

        $validated = $request->validate([
            'client_organization_id' => ['nullable', 'integer', 'exists:client_organizations,id', 'required_without:client_organization_name'],
            'client_organization_name' => ['nullable', 'required_without:client_organization_id', 'string', 'max:180'],
            'title' => ['required', 'string', 'max:180'],
            'job_title' => ['required', 'string', 'max:180'],
            'openings' => ['required', 'integer', 'min:1', 'max:65535'],
            'employment_type' => ['required', 'string', 'max:40'],
            'location' => ['required', 'string', 'max:180'],
            'work_mode' => ['required', 'in:On-site,Hybrid,Remote'],
            'salary_ctc' => ['nullable', 'string', 'max:180'],
            'min_experience_years' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'max_experience_years' => ['nullable', 'numeric', 'gte:min_experience_years', 'max:999.99'],
            'required_skills' => ['nullable', 'string', 'max:10000'],
            'qualification' => ['nullable', 'string', 'max:10000'],
            'job_description' => ['nullable', 'string', 'max:50000'],
            'target_date' => ['nullable', 'date'],
            'primary_spoc_id' => ['nullable', 'integer', 'exists:users,id'],
            'intake_source' => ['required', 'in:Management discussion,Client RFP,Internal intake'],
            'source_reference' => ['nullable', 'string', 'max:2000'],
        ]);

        if (isset($validated['primary_spoc_id']) && ! User::query()
            ->whereKey($validated['primary_spoc_id'])
            ->where('status', 'active')
            ->whereHas('roles', fn (Builder $roles) => $roles->whereIn('slug', ['management', 'recruitment-manager']))
            ->exists()) {
            throw ValidationException::withMessages(['primary_spoc_id' => 'Choose an active Management or Recruitment Manager user.']);
        }

        $requirement = DB::transaction(function () use ($validated, $request): Requirement {
            $client = isset($validated['client_organization_id'])
                ? ClientOrganization::query()->findOrFail($validated['client_organization_id'])
                : ClientOrganization::query()->firstOrCreate(['name' => trim($validated['client_organization_name'])]);
            $targetDate = $validated['target_date'] ?? null;

            $requirement = Requirement::create([
                ...$validated,
                'reference' => 'REQ-'.Str::upper((string) Str::ulid()),
                'client_organization_id' => $client->getKey(),
                'original_target_date' => $targetDate,
                'target_date' => $targetDate,
                'priority' => 'Normal',
                'status' => 'Pending Review',
                'created_by' => $request->user()->getKey(),
            ]);

            if ($targetDate !== null) {
                RequirementTargetDateChange::create([
                    'requirement_id' => $requirement->getKey(),
                    'previous_target_date' => null,
                    'new_target_date' => $targetDate,
                    'reason' => 'Original client-provided target date recorded at intake.',
                    'changed_by' => $request->user()->getKey(),
                    'changed_at' => now(),
                ]);
            }

            AuditLog::record('requirement.created', [
                'entity_type' => Requirement::class,
                'entity_id' => (string) $requirement->getKey(),
                'new_state' => ['reference' => $requirement->reference, 'status' => $requirement->status, 'priority' => $requirement->priority],
                'context' => ['intake_source' => $requirement->intake_source],
            ]);

            return $requirement;
        });

        return redirect()->route('requirements.show', $requirement)->with('status', 'Requirement received and sent for Recruitment Manager review.');
    }

    public function show(Requirement $requirement): View
    {
        $this->authorize('view', $requirement);
        $requirement->load([
            'clientOrganization', 'creator', 'recruitmentManager', 'primarySpoc',
            'assignments.recruiter', 'assignments.assignedBy', 'targetDateChanges.changedBy',
        ]);

        return view('requirements.show', [
            'requirement' => $requirement,
            'recruiters' => User::query()->where('status', 'active')
                ->whereHas('roles', fn (Builder $roles) => $roles->where('slug', 'recruiter'))
                ->orderBy('name')->get(),
            'spocs' => User::query()->where('status', 'active')
                ->whereHas('roles', fn (Builder $roles) => $roles->whereIn('slug', ['management', 'recruitment-manager']))
                ->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Requirement $requirement): RedirectResponse
    {
        $this->authorize('update', $requirement);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'job_title' => ['required', 'string', 'max:180'],
            'openings' => ['required', 'integer', 'min:1', 'max:65535'],
            'employment_type' => ['required', 'string', 'max:40'],
            'location' => ['required', 'string', 'max:180'],
            'work_mode' => ['required', 'in:On-site,Hybrid,Remote'],
            'salary_ctc' => ['nullable', 'string', 'max:180'],
            'min_experience_years' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'max_experience_years' => ['nullable', 'numeric', 'gte:min_experience_years', 'max:999.99'],
            'required_skills' => ['nullable', 'string', 'max:10000'],
            'qualification' => ['nullable', 'string', 'max:10000'],
            'job_description' => ['nullable', 'string', 'max:50000'],
            'primary_spoc_id' => ['nullable', 'integer', 'exists:users,id'],
            'source_reference' => ['nullable', 'string', 'max:2000'],
        ]);

        if (isset($validated['primary_spoc_id']) && ! User::query()
            ->whereKey($validated['primary_spoc_id'])
            ->where('status', 'active')
            ->whereHas('roles', fn (Builder $roles) => $roles->whereIn('slug', ['management', 'recruitment-manager']))
            ->exists()) {
            throw ValidationException::withMessages(['primary_spoc_id' => 'Choose an active Management or Recruitment Manager user.']);
        }

        $before = $requirement->only(array_keys($validated));
        $requirement->fill($validated)->save();

        AuditLog::record('requirement.updated', [
            'entity_type' => Requirement::class,
            'entity_id' => (string) $requirement->getKey(),
            'previous_state' => $before,
            'new_state' => $requirement->only(array_keys($validated)),
        ]);

        return back()->with('status', 'Requirement details updated.');
    }

    public function approve(Request $request, Requirement $requirement): RedirectResponse
    {
        $this->authorize('review', $requirement);
        $requirement->forceFill([
            'status' => 'Approved',
            'recruitment_manager_id' => $request->user()->getKey(),
        ])->save();

        AuditLog::record('requirement.approved', [
            'entity_type' => Requirement::class,
            'entity_id' => (string) $requirement->getKey(),
            'previous_state' => ['status' => 'Pending Review'],
            'new_state' => ['status' => 'Approved', 'recruitment_manager_id' => $request->user()->getKey()],
        ]);

        return back()->with('status', 'Requirement approved. Assign one or more Recruiters before activating it.');
    }

    public function activate(Request $request, Requirement $requirement): RedirectResponse
    {
        $this->authorize('activate', $requirement);
        abort_if(! $requirement->activeAssignments()->exists(), 422, 'Assign at least one Recruiter before activation.');

        $requirement->forceFill(['status' => 'Active'])->save();
        AuditLog::record('requirement.activated', [
            'entity_type' => Requirement::class,
            'entity_id' => (string) $requirement->getKey(),
            'previous_state' => ['status' => 'Approved'],
            'new_state' => ['status' => 'Active'],
        ]);

        return back()->with('status', 'Requirement activated. Recruitment work may begin.');
    }

    public function assign(Request $request, Requirement $requirement): RedirectResponse
    {
        $this->authorize('assign', $requirement);
        $validated = $request->validate([
            'recruiter_ids' => ['required', 'array', 'min:1', 'max:30'],
            'recruiter_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $selectedIds = collect($validated['recruiter_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $eligibleIds = User::query()->whereKey($selectedIds)->where('status', 'active')
            ->whereHas('roles', fn (Builder $roles) => $roles->where('slug', 'recruiter'))
            ->pluck('id')->map(fn ($id) => (int) $id);

        if ($eligibleIds->count() !== $selectedIds->count()) {
            throw ValidationException::withMessages(['recruiter_ids' => 'Select only active users with the Recruiter role.']);
        }

        DB::transaction(function () use ($requirement, $eligibleIds, $validated, $request): void {
            $locked = Requirement::query()->lockForUpdate()->findOrFail($requirement->getKey());
            $active = $locked->activeAssignments()->lockForUpdate()->get();
            $currentIds = $active->pluck('recruiter_id')->map(fn ($id) => (int) $id);
            $changed = $currentIds->diff($eligibleIds)->isNotEmpty() || $eligibleIds->diff($currentIds)->isNotEmpty();

            if ($currentIds->isNotEmpty() && $changed && trim((string) ($validated['reason'] ?? '')) === '') {
                throw ValidationException::withMessages(['reason' => 'Provide a reason when changing the current Recruiter assignment.']);
            }

            $now = now();
            foreach ($active as $assignment) {
                if (! $eligibleIds->contains((int) $assignment->recruiter_id)) {
                    $assignment->forceFill(['ended_at' => $now, 'reason' => $validated['reason'] ?? null])->save();
                    AuditLog::record('requirement.recruiter_reassigned', [
                        'entity_type' => Requirement::class,
                        'entity_id' => (string) $locked->getKey(),
                        'previous_state' => ['recruiter_id' => $assignment->recruiter_id, 'assigned' => true],
                        'new_state' => ['recruiter_id' => $assignment->recruiter_id, 'assigned' => false],
                        'context' => ['reason' => $validated['reason'] ?? null],
                    ]);
                }
            }

            foreach ($eligibleIds as $recruiterId) {
                if ($currentIds->contains($recruiterId)) {
                    continue;
                }

                $assignment = $locked->assignments()->create([
                    'recruiter_id' => $recruiterId,
                    'assigned_by' => $request->user()->getKey(),
                    'assigned_at' => $now,
                    'reason' => $validated['reason'] ?? null,
                ]);

                AuditLog::record($currentIds->isNotEmpty() ? 'requirement.recruiter_reassigned' : 'requirement.recruiter_assigned', [
                    'entity_type' => Requirement::class,
                    'entity_id' => (string) $locked->getKey(),
                    'new_state' => ['assignment_id' => $assignment->getKey(), 'recruiter_id' => $recruiterId, 'assigned' => true],
                    'context' => ['reason' => $validated['reason'] ?? null],
                ]);
            }
        });

        return back()->with('status', 'Recruiter assignment updated.');
    }

    public function prioritize(Request $request, Requirement $requirement): RedirectResponse
    {
        $this->authorize('prioritize', $requirement);
        $validated = $request->validate([
            'priority' => ['required', 'in:Critical,High,Normal,Low'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        if ($validated['priority'] === $requirement->priority) {
            return back()->with('status', 'The requirement already has that priority.');
        }

        $previous = $requirement->priority;
        $requirement->forceFill(['priority' => $validated['priority']])->save();
        AuditLog::record('requirement.priority_changed', [
            'entity_type' => Requirement::class,
            'entity_id' => (string) $requirement->getKey(),
            'previous_state' => ['priority' => $previous],
            'new_state' => ['priority' => $requirement->priority],
            'context' => ['reason' => $validated['reason']],
        ]);

        return back()->with('status', 'Requirement priority updated.');
    }

    public function reviseTargetDate(Request $request, Requirement $requirement): RedirectResponse
    {
        $this->authorize('reviseTargetDate', $requirement);
        $validated = $request->validate([
            'target_date' => ['nullable', 'date', 'after_or_equal:today'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'client_confirmation_reference' => ['nullable', 'string', 'max:500'],
        ]);
        $previous = $requirement->target_date?->toDateString();
        $next = $validated['target_date'] ?? null;

        if ($previous === $next) {
            return back()->with('status', 'The target date is unchanged.');
        }

        DB::transaction(function () use ($requirement, $request, $validated, $previous, $next): void {
            $requirement->forceFill(['target_date' => $next])->save();
            RequirementTargetDateChange::create([
                'requirement_id' => $requirement->getKey(),
                'previous_target_date' => $previous,
                'new_target_date' => $next,
                'reason' => $validated['reason'],
                'client_confirmation_reference' => $validated['client_confirmation_reference'] ?? null,
                'changed_by' => $request->user()->getKey(),
                'changed_at' => now(),
            ]);
            AuditLog::record('requirement.target_date_changed', [
                'entity_type' => Requirement::class,
                'entity_id' => (string) $requirement->getKey(),
                'previous_state' => ['target_date' => $previous],
                'new_state' => ['target_date' => $next],
                'context' => [
                    'reason' => $validated['reason'],
                    'client_confirmation_reference' => $validated['client_confirmation_reference'] ?? null,
                ],
            ]);
        });

        return back()->with('status', 'Target date history recorded.');
    }

    public function reportRisk(Request $request, Requirement $requirement): RedirectResponse
    {
        $this->authorize('reportRisk', $requirement);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:2000']]);
        $requirement->forceFill(['status' => 'At Risk'])->save();

        AuditLog::record('requirement.at_risk', [
            'entity_type' => Requirement::class,
            'entity_id' => (string) $requirement->getKey(),
            'previous_state' => ['status' => 'Active'],
            'new_state' => ['status' => 'At Risk'],
            'context' => ['reason' => $validated['reason']],
        ]);

        return back()->with('status', 'At-risk status reported to the Recruitment Manager.');
    }

    public function changeStatus(Request $request, Requirement $requirement): RedirectResponse
    {
        $this->authorize('changeStatus', $requirement);
        $validated = $request->validate([
            'status' => ['required', 'in:Active,On Hold,Closed'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $isManager = $request->user()->hasRole('recruitment-manager');
        $allowed = match ($requirement->status) {
            'Active' => ['On Hold', 'Closed'],
            'At Risk', 'On Hold' => ['Active', 'Closed'],
            'Approved' => ['Closed'],
            default => [],
        };

        if (! $isManager) {
            $allowed = array_values(array_intersect($allowed, ['On Hold', 'Closed']));
        }

        if (! in_array($validated['status'], $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'That status change is not allowed from the current state.']);
        }

        if ($validated['status'] === 'Active' && ! $requirement->activeAssignments()->exists()) {
            throw ValidationException::withMessages(['status' => 'Assign at least one Recruiter before returning this requirement to Active.']);
        }

        $previous = $requirement->status;
        $requirement->forceFill(['status' => $validated['status']])->save();
        AuditLog::record('requirement.status_changed', [
            'entity_type' => Requirement::class,
            'entity_id' => (string) $requirement->getKey(),
            'previous_state' => ['status' => $previous],
            'new_state' => ['status' => $requirement->status],
            'context' => ['reason' => $validated['reason']],
        ]);

        return back()->with('status', 'Requirement status updated.');
    }
}
