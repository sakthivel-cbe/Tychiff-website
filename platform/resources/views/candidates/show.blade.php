@extends('layouts.foundation')

@section('title', $candidate->reference)

@section('content')
<section aria-labelledby="page-title">
    <a class="back-link" href="{{ route('candidates.index') }}">← Candidate database</a>
    <div class="heading-row candidate-heading"><div><p class="eyebrow">{{ $candidate->reference }} · Candidate profile</p><h1 id="page-title">{{ $candidate->full_name }}</h1><p class="lede">{{ $candidate->current_designation ?: 'Professional profile' }} · {{ $candidate->location ?: 'Location not provided' }}</p></div><span class="status-chip">{{ $candidate->status }}</span></div>

    <div class="detail-grid">
        <article class="panel detail-main">
            <div class="heading-row section-heading"><h2>Profile</h2>@if ($canUpdate)<a class="button secondary" href="{{ route('candidates.edit', $candidate) }}">Edit profile</a>@endif</div>
            <dl class="detail-list">
                <div><dt>Email</dt><dd>{{ $candidate->email ?: 'Not provided' }}</dd></div>
                <div><dt>Mobile</dt><dd>{{ $candidate->mobile ?: 'Not provided' }}</dd></div>
                <div><dt>Alternate contact</dt><dd>{{ $candidate->alternate_contact ?: 'Not provided' }}</dd></div>
                <div><dt>Current / recent employer</dt><dd>{{ $candidate->current_employer ?: 'Not provided' }}</dd></div>
                <div><dt>Total / relevant experience</dt><dd>{{ $candidate->total_experience_years ?? '—' }} / {{ $candidate->relevant_experience_years ?? '—' }} years</dd></div>
                <div><dt>Highest qualification</dt><dd>{{ $candidate->highest_qualification ?: 'Not provided' }}</dd></div>
                <div><dt>Current CTC</dt><dd>{{ $candidate->current_ctc ?: 'Not provided' }}</dd></div>
                <div><dt>Expected CTC</dt><dd>{{ $candidate->expected_ctc ?: 'Not provided' }}</dd></div>
                <div><dt>Notice period</dt><dd>{{ $candidate->notice_period_days !== null ? $candidate->notice_period_days.' days' : 'Not provided' }}</dd></div>
                <div><dt>Availability</dt><dd>{{ $candidate->availability ?: 'Not provided' }}</dd></div>
                <div><dt>Preferred location / mode</dt><dd>{{ $candidate->preferred_location ?: '—' }} / {{ $candidate->preferred_work_mode ?: '—' }}</dd></div>
                <div><dt>Source</dt><dd>{{ $candidate->source }}{{ $candidate->source_details ? ' · '.$candidate->source_details : '' }}</dd></div>
                <div><dt>Created by / date</dt><dd>{{ $candidate->creator->name }} · {{ $candidate->created_at->format('d M Y H:i') }}</dd></div>
                <div><dt>Last updated</dt><dd>{{ $candidate->updated_at->format('d M Y H:i') }}</dd></div>
                <div class="span-all"><dt>Primary skills</dt><dd class="preserve-lines">{{ $candidate->primary_skills ?: 'Not provided' }}</dd></div>
                <div class="span-all"><dt>Secondary skills</dt><dd class="preserve-lines">{{ $candidate->secondary_skills ?: 'Not provided' }}</dd></div>
                <div class="span-all"><dt>Profile summary</dt><dd class="preserve-lines">{{ $candidate->profile_summary ?: 'Not provided' }}</dd></div>
            </dl>
            @can('changeStatus', $candidate)
                <form class="inline-status" method="post" action="{{ route('candidates.status', $candidate) }}">@csrf
                    <label class="field"><span>Candidate status</span><select name="status">@foreach (\App\Models\Candidate::STATUSES as $status)<option @selected($candidate->status === $status)>{{ $status }}</option>@endforeach</select></label>
                    <label class="field"><span>Reason for status change</span><input name="reason" maxlength="2000" required></label><button class="button secondary" type="submit">Update status</button>
                </form>
            @endcan
        </article>

        <aside class="side-stack">
            <section class="panel">
                <div class="heading-row section-heading"><h2>Requirements</h2></div>
                <p class="muted">Internal association only. This does not submit or share the candidate with a client.</p>
                @if ($visibleRequirements->isEmpty())<p class="muted">No authorized Requirement links.</p>@else
                    <ul class="compact-list">@foreach ($visibleRequirements as $requirement)<li><a href="{{ route('requirements.show', $requirement) }}">{{ $requirement->title }}</a><span class="meta">{{ $requirement->reference }} · {{ $requirement->clientOrganization->name }}</span></li>@endforeach</ul>
                @endif
                @php($availableRequirements = $eligibleRequirements->reject(fn ($requirement) => $visibleRequirements->contains('id', $requirement->id)))
                @if ($availableRequirements->isNotEmpty())
                    <form class="form-stack compact-form" method="post" action="{{ route('candidates.requirements.link', $candidate) }}">@csrf
                        <label class="field"><span>Associate with Requirement</span><select name="requirement_id" required><option value="">Choose a Requirement</option>@foreach ($availableRequirements as $requirement)<option value="{{ $requirement->id }}">{{ $requirement->reference }} · {{ $requirement->title }} · {{ $requirement->clientOrganization->name }}</option>@endforeach</select></label>
                        <label class="field"><span>Internal reason (optional)</span><input name="reason" maxlength="2000"></label><button class="button secondary" type="submit">Add internal link</button>
                    </form>
                @endif
            </section>

            <section class="panel">
                <div class="heading-row section-heading"><h2>Private documents</h2></div>
                @if ($candidate->documents->isEmpty())<p class="muted">No resume or profile document has been uploaded.</p>@else
                    <ul class="compact-list">@foreach ($candidate->documents as $document)<li><a href="{{ route('candidate-documents.download', $document) }}">{{ $document->original_filename }}</a><span class="meta">{{ $document->document_type }} · {{ number_format($document->file_size / 1024, 0) }} KB · {{ $document->uploader->name }}</span></li>@endforeach</ul>
                @endif
                <form class="form-stack compact-form" method="post" enctype="multipart/form-data" action="{{ route('candidates.documents.store', $candidate) }}">@csrf
                    <label class="field"><span>Document type</span><select name="document_type"><option value="resume">Resume</option><option value="profile_document">Profile document</option></select></label>
                    <label class="field"><span>PDF, DOC or DOCX (maximum 10 MB)</span><input type="file" name="document" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required></label>
                    <button class="button secondary" type="submit">Upload to private storage</button>
                </form>
            </section>

            <section class="panel">
                <h2>Candidate sharing consent</h2>
                <p class="muted">Consent is separate from the Phase 1 privacy notice acknowledgement. It applies only to one Candidate + Client + Requirement scope.</p>
                @if ($candidate->consents->isEmpty())<p class="muted">No consent has been recorded.</p>@else
                    <ul class="compact-list">@foreach ($candidate->consents as $consent)<li><strong>{{ $consent->status === 'valid' ? 'Valid' : 'Withdrawn' }} · {{ $consent->clientOrganization->name }}</strong><span>{{ $consent->requirement->reference }} · {{ $consent->requirement->title }}</span><span class="meta">{{ $consent->consented_at->format('d M Y H:i') }} · {{ $consent->consent_method }} · {{ $consent->consent_text_reference }}</span><span class="meta">Recorded by {{ $consent->recorder->name }}</span>
                        @if ($consent->status === 'valid' && ! $consent->withdrawn_at)
                            <form class="compact-withdraw" method="post" action="{{ route('candidate-consents.withdraw', $consent) }}">@csrf<label class="field"><span>Withdrawal reason</span><input name="reason" maxlength="2000" required></label><button class="text-button" type="submit">Record withdrawal</button></form>
                        @else<span class="meta">Withdrawn {{ $consent->withdrawn_at?->format('d M Y H:i') }} by {{ $consent->withdrawnBy?->name ?? '—' }} · {{ $consent->withdrawal_reason }}</span>@endif
                    </li>@endforeach</ul>
                @endif
                @if ($consentRequirements->isNotEmpty())
                    <form class="form-stack compact-form" method="post" action="{{ route('candidates.consents.store', $candidate) }}">@csrf
                        <label class="field"><span>Requirement / Client scope</span><select name="requirement_id" required><option value="">Choose an unconsented scope</option>@foreach ($consentRequirements as $requirement)<option value="{{ $requirement->id }}">{{ $requirement->reference }} · {{ $requirement->title }} · {{ $requirement->clientOrganization->name }}</option>@endforeach</select></label>
                        <label class="field"><span>Consent date and time</span><input type="datetime-local" name="consented_at" max="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('consented_at', now()->format('Y-m-d\TH:i')) }}" required></label>
                        <label class="field"><span>Consent method</span><select name="consent_method" required><option value="">Choose method</option>@foreach (['written', 'email', 'phone', 'in_person', 'other'] as $method)<option value="{{ $method }}">{{ str_replace('_', ' ', ucfirst($method)) }}</option>@endforeach</select></label>
                        <label class="field"><span>Consent text/version or reference</span><input name="consent_text_reference" maxlength="255" required></label>
                        <label class="checkbox-row"><input type="checkbox" name="candidate_permission_confirmed" value="1" required><span class="checkbox-label">I confirm the candidate gave permission to use/share their profile for this Client and Requirement scope.</span></label>
                        <button class="button" type="submit">Record candidate consent</button>
                    </form>
                @else<p class="muted">Link an authorized Requirement to record consent for that scope. Existing valid scopes are omitted to avoid repeated requests.</p>@endif
            </section>
        </aside>
    </div>

    <section class="panel history-panel">
        <div class="heading-row section-heading"><div><h2>Internal recruitment notes</h2><p class="muted">Notes are visible only to users authorized for this Candidate.</p></div></div>
        <form class="form-stack compact-form" method="post" action="{{ route('candidates.notes.store', $candidate) }}">@csrf<label class="field"><span>Add internal note</span><textarea name="note" rows="3" maxlength="10000" required></textarea></label><button class="button secondary" type="submit">Add note</button></form>
        @if ($candidate->notes->isEmpty())<p class="muted">No notes yet.</p>@else<ul class="activity-list">@foreach ($candidate->notes as $note)<li><p class="preserve-lines">{{ $note->note }}</p><span class="meta">{{ $note->author->name }} · {{ $note->created_at->format('d M Y H:i') }}</span></li>@endforeach</ul>@endif
    </section>

    @if ($canManageAccess)
        <section class="panel history-panel"><div class="heading-row section-heading"><div><h2>Recruiter access</h2><p class="muted">Candidate access is shared by explicit grant; a Recruiter does not gain access from the profile's existence.</p></div><a class="button secondary" href="{{ route('candidates.access.index', $candidate) }}">Manage access</a></div>
            <ul class="compact-list">@foreach ($candidate->accessRecords as $access)<li><strong>{{ $access->recruiter->name }} · {{ ucfirst($access->status) }}</strong><span class="meta">Granted by {{ $access->grantedBy->name }} · {{ $access->granted_at->format('d M Y H:i') }}{{ $access->grant_reason ? ' · '.$access->grant_reason : '' }}</span>@if ($access->revoked_at)<span class="meta">Revoked by {{ $access->revokedBy?->name ?? '—' }} · {{ $access->revoked_at->format('d M Y H:i') }} · {{ $access->revocation_reason }}</span>@endif</li>@endforeach</ul>
        </section>
    @endif

    <section class="panel history-panel"><h2>Candidate activity</h2>@if ($activities->isEmpty())<p class="muted">No activity recorded.</p>@else<ul class="activity-list">@foreach ($activities as $activity)<li><strong>{{ str_replace('_', ' ', $activity->action) }}</strong><span class="meta">{{ $activity->actor?->name ?? 'System' }} · {{ $activity->created_at->format('d M Y H:i') }}</span></li>@endforeach</ul>@endif</section>
</section>
@endsection
