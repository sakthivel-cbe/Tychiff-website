@extends('layouts.foundation')

@section('title', $requirement->reference)

@section('content')
<section aria-labelledby="page-title">
    <a class="back-link" href="{{ route('requirements.index') }}">← Requirements</a>
    <div class="heading-row requirement-heading">
        <div><p class="eyebrow">{{ $requirement->reference }} · {{ $requirement->clientOrganization->name }}</p><h1 id="page-title">{{ $requirement->title }}</h1><p class="lede">{{ $requirement->job_title }} · {{ $requirement->openings }} opening(s) · {{ $requirement->location }} · {{ $requirement->work_mode }}</p></div>
        <div class="state-stack"><span class="status-chip">{{ $requirement->status }}</span><span class="priority-chip priority-{{ \Illuminate\Support\Str::slug($requirement->priority) }}">{{ $requirement->priority }} priority</span></div>
    </div>

    <div class="detail-grid">
        <article class="panel detail-main">
            <h2>Requirement details</h2>
            <dl class="detail-list">
                <div><dt>Employment type</dt><dd>{{ $requirement->employment_type }}</dd></div>
                <div><dt>Salary / CTC</dt><dd>{{ $requirement->salary_ctc ?: 'Not provided' }}</dd></div>
                <div><dt>Experience range</dt><dd>{{ $requirement->min_experience_years ?? '—' }} to {{ $requirement->max_experience_years ?? '—' }} years</dd></div>
                <div><dt>Original client target</dt><dd>{{ $requirement->original_target_date?->format('d M Y') ?? 'Not provided' }}</dd></div>
                <div><dt>Current target</dt><dd>{{ $requirement->target_date?->format('d M Y') ?? 'Not set' }}</dd></div>
                <div><dt>Primary TYCHIFF SPOC</dt><dd>{{ $requirement->primarySpoc?->name ?? 'Not assigned' }}</dd></div>
                <div><dt>Recruitment Manager</dt><dd>{{ $requirement->recruitmentManager?->name ?? 'Awaiting review' }}</dd></div>
                <div><dt>Intake source</dt><dd>{{ $requirement->intake_source }}</dd></div>
                <div><dt>Source reference / notes</dt><dd class="preserve-lines">{{ $requirement->source_reference ?: 'Not provided' }}</dd></div>
                <div><dt>Required skills</dt><dd class="preserve-lines">{{ $requirement->required_skills ?: 'Not provided' }}</dd></div>
                <div><dt>Qualification</dt><dd class="preserve-lines">{{ $requirement->qualification ?: 'Not provided' }}</dd></div>
                <div><dt>Job description / details</dt><dd class="preserve-lines">{{ $requirement->job_description ?: 'Not provided' }}</dd></div>
                <div><dt>Created by</dt><dd>{{ $requirement->creator->name }} · {{ $requirement->created_at->format('d M Y H:i') }}</dd></div>
                <div><dt>Last updated</dt><dd>{{ $requirement->updated_at->format('d M Y H:i') }}</dd></div>
            </dl>
        </article>

        <aside class="side-stack">
            <section class="panel">
                <h2>Recruiter assignment</h2>
                @if ($requirement->activeAssignments->isEmpty())<p class="muted">No Recruiters assigned.</p>@else
                    <ul class="compact-list">@foreach ($requirement->activeAssignments as $assignment)<li><strong>{{ $assignment->recruiter->name }}</strong><span class="meta">Assigned {{ $assignment->assigned_at->format('d M Y H:i') }}</span></li>@endforeach</ul>
                @endif
                @if ($requirement->assignments->contains(fn ($assignment) => $assignment->ended_at !== null))
                    <details class="history-details"><summary>Previous assignments</summary><ul class="compact-list">@foreach ($requirement->assignments->whereNotNull('ended_at') as $assignment)<li><strong>{{ $assignment->recruiter->name }}</strong><span class="meta">{{ $assignment->assigned_at->format('d M Y H:i') }} – {{ $assignment->ended_at->format('d M Y H:i') }}</span><span>{{ $assignment->reason ?: 'No reason recorded' }}</span></li>@endforeach</ul></details>
                @endif
                @can('assign', $requirement)
                    <form class="form-stack compact-form" method="post" action="{{ route('requirements.assignments.update', $requirement) }}">
                        @csrf @method('put')
                        <fieldset class="choice-list"><legend>Assigned Recruiters</legend>
                            @forelse ($recruiters as $recruiter)<label class="checkbox-row"><input type="checkbox" name="recruiter_ids[]" value="{{ $recruiter->id }}" @checked($requirement->activeAssignments->contains('recruiter_id', $recruiter->id))><span class="checkbox-label">{{ $recruiter->name }}</span></label>@empty<p class="muted">No active Recruiter accounts are available.</p>@endforelse
                        </fieldset>
                        <label class="field"><span>Reason for assignment change (required for reassignment)</span><textarea name="reason" rows="2" maxlength="2000">{{ old('reason') }}</textarea></label>
                        <button class="button secondary" type="submit">Save assignment</button>
                    </form>
                @endcan
            </section>

            @can('review', $requirement)<section class="panel action-panel"><h2>Recruitment Manager review</h2><p class="muted">Review the intake before approval. Recruitment requires approval and activation.</p><form method="post" action="{{ route('requirements.approve', $requirement) }}">@csrf<button class="button" type="submit">Approve requirement</button></form></section>@endcan
            @can('activate', $requirement)<section class="panel action-panel"><h2>Start recruitment</h2><p class="muted">At least one Recruiter must be assigned before activation.</p><form method="post" action="{{ route('requirements.activate', $requirement) }}">@csrf<button class="button" type="submit">Activate requirement</button></form></section>@endcan

            @can('prioritize', $requirement)
                <section class="panel"><h2>Set priority</h2><form class="form-stack compact-form" method="post" action="{{ route('requirements.priority', $requirement) }}">@csrf
                    <label class="field"><span>Priority</span><select name="priority" required>@foreach (\App\Models\Requirement::PRIORITIES as $priority)<option value="{{ $priority }}" @selected($requirement->priority === $priority)>{{ $priority }}</option>@endforeach</select></label>
                    <label class="field"><span>Reason for change</span><textarea name="reason" rows="2" maxlength="2000" required>{{ old('reason') }}</textarea></label><button class="button secondary" type="submit">Update priority</button>
                </form></section>
            @endcan

            @can('reviseTargetDate', $requirement)
                <section class="panel"><h2>Revise target date</h2><form class="form-stack compact-form" method="post" action="{{ route('requirements.target-date', $requirement) }}">@csrf
                    <label class="field"><span>Agreed target date</span><input type="date" name="target_date" value="{{ $requirement->target_date?->toDateString() }}"></label>
                    <label class="field"><span>Reason</span><textarea name="reason" rows="2" maxlength="2000" required>{{ old('reason') }}</textarea></label>
                    <label class="field"><span>Client confirmation reference (if available)</span><input name="client_confirmation_reference" maxlength="500" value="{{ old('client_confirmation_reference') }}"></label><button class="button secondary" type="submit">Record agreed date</button>
                </form></section>
            @endcan

            @can('reportRisk', $requirement)
                <section class="panel action-panel"><h2>Report delivery risk</h2><p class="muted">This alerts the Recruitment Manager. It does not close the requirement or revise the client target.</p><form class="form-stack compact-form" method="post" action="{{ route('requirements.risk', $requirement) }}">@csrf
                    <label class="field"><span>Issue / at-risk reason</span><textarea name="reason" rows="3" required maxlength="2000">{{ old('reason') }}</textarea></label><button class="button secondary" type="submit">Report at risk</button>
                </form></section>
            @endcan

            @can('changeStatus', $requirement)
                @php
                    $nextStatuses = match ($requirement->status) {
                        'Active' => ['On Hold', 'Closed'],
                        'At Risk', 'On Hold' => auth()->user()->hasRole('recruitment-manager') ? ['Active', 'Closed'] : ['On Hold', 'Closed'],
                        'Approved' => ['Closed'],
                        default => [],
                    };
                @endphp
                @if (count($nextStatuses))<section class="panel"><h2>Change status</h2><form class="form-stack compact-form" method="post" action="{{ route('requirements.status', $requirement) }}">@csrf
                    <label class="field"><span>New status</span><select name="status" required>@foreach ($nextStatuses as $status)<option>{{ $status }}</option>@endforeach</select></label>
                    <label class="field"><span>Reason</span><textarea name="reason" rows="2" required maxlength="2000">{{ old('reason') }}</textarea></label><button class="button secondary" type="submit">Save status</button>
                </form></section>@endif
            @endcan
        </aside>
    </div>

    <section class="panel history-panel jd-entry">
        <div><p class="eyebrow">Job description</p><h2>JD preparation and approval</h2>
            <p class="muted">Prepare a versioned JD, complete Recruitment Manager review and record the client decision.</p></div>
        <a class="button secondary" href="{{ route('job-descriptions.index', $requirement) }}">Manage JD versions</a>
    </section>

    @if ($requirement->targetDateChanges->isNotEmpty())
        <section class="panel history-panel"><h2>Target-date history</h2><div class="table-wrap"><table><thead><tr><th>Previous</th><th>New</th><th>Reason</th><th>Confirmation</th><th>Changed by</th><th>When</th></tr></thead><tbody>@foreach ($requirement->targetDateChanges as $change)<tr><td>{{ $change->previous_target_date?->format('d M Y') ?? '—' }}</td><td>{{ $change->new_target_date?->format('d M Y') ?? 'Not set' }}</td><td>{{ $change->reason }}</td><td>{{ $change->client_confirmation_reference ?? '—' }}</td><td>{{ $change->changedBy->name }}</td><td>{{ $change->changed_at->format('d M Y H:i') }}</td></tr>@endforeach</tbody></table></div></section>
    @endif

    @can('update', $requirement)
        <details class="panel edit-details"><summary>Edit intake details</summary><form class="form-stack" method="post" action="{{ route('requirements.update', $requirement) }}">@csrf @method('patch')
            <div class="form-grid">
                <label class="field"><span>Requirement title</span><input name="title" maxlength="180" required value="{{ old('title', $requirement->title) }}"></label>
                <label class="field"><span>Job / position title</span><input name="job_title" maxlength="180" required value="{{ old('job_title', $requirement->job_title) }}"></label>
                <label class="field"><span>Number of openings</span><input type="number" min="1" name="openings" required value="{{ old('openings', $requirement->openings) }}"></label>
                <label class="field"><span>Employment / engagement type</span><input name="employment_type" maxlength="40" required value="{{ old('employment_type', $requirement->employment_type) }}"></label>
                <label class="field"><span>Location</span><input name="location" maxlength="180" required value="{{ old('location', $requirement->location) }}"></label>
                <label class="field"><span>Work mode</span><select name="work_mode">@foreach (['On-site', 'Hybrid', 'Remote'] as $mode)<option @selected($requirement->work_mode === $mode)>{{ $mode }}</option>@endforeach</select></label>
                <label class="field"><span>Salary / CTC</span><input name="salary_ctc" maxlength="180" value="{{ old('salary_ctc', $requirement->salary_ctc) }}"></label>
                <label class="field"><span>Minimum experience (years)</span><input type="number" min="0" step="0.25" name="min_experience_years" value="{{ old('min_experience_years', $requirement->min_experience_years) }}"></label>
                <label class="field"><span>Maximum experience (years)</span><input type="number" min="0" step="0.25" name="max_experience_years" value="{{ old('max_experience_years', $requirement->max_experience_years) }}"></label>
                <label class="field"><span>Primary TYCHIFF SPOC</span><select name="primary_spoc_id"><option value="">Not assigned</option>@foreach ($spocs as $spoc)<option value="{{ $spoc->id }}" @selected($requirement->primary_spoc_id === $spoc->id)>{{ $spoc->name }}</option>@endforeach</select></label>
                <label class="field span-all"><span>Source reference / notes</span><textarea name="source_reference" rows="2">{{ old('source_reference', $requirement->source_reference) }}</textarea></label>
                <label class="field span-all"><span>Required skills</span><textarea name="required_skills" rows="3">{{ old('required_skills', $requirement->required_skills) }}</textarea></label>
                <label class="field span-all"><span>Qualification</span><textarea name="qualification" rows="3">{{ old('qualification', $requirement->qualification) }}</textarea></label>
                <label class="field span-all"><span>Job description / details</span><textarea name="job_description" rows="5">{{ old('job_description', $requirement->job_description) }}</textarea></label>
            </div><button class="button" type="submit">Save intake updates</button>
        </form></details>
    @endcan
</section>
@endsection
