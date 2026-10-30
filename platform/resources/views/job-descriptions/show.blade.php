@extends('layouts.foundation')

@section('title', $jd->requirement->reference.' JD v'.$jd->version)

@section('content')
<section aria-labelledby="page-title">
    <a class="back-link" href="{{ route('job-descriptions.index', $jd->requirement) }}">← JD version history</a>
    <div class="heading-row"><div><p class="eyebrow">{{ $jd->requirement->reference }} · {{ $jd->requirement->clientOrganization->name }}</p><h1 id="page-title">{{ $jd->title }}</h1><p class="lede">JD v{{ $jd->version }} · {{ $jd->status }} · {{ str_replace('_', ' ', $jd->source) }}</p></div><span class="status-chip">{{ $jd->status }}</span></div>

    @if (in_array($jd->status, ['AI Draft', 'Draft'], true))
        <div class="notice info" role="note"><strong>{{ $jd->status === 'AI Draft' ? 'AI-assisted draft — human review required.' : 'Manual draft — not approved.' }}</strong> This version is not approved for recruitment use. Review all content and facts.</div>
    @endif

    @if ($jd->status === 'Sent for Client Approval')
        <div class="notice info" role="status">Sent for Client Approval. The external client decision has not yet been recorded.</div>
    @endif
    @if ($jd->status === 'Approved')
        <div class="notice success" role="status">Client approval recorded for this exact JD version at {{ $jd->approved_at?->format('d M Y H:i') }}. Recorded by {{ $jd->approvalRecorder?->name ?? 'Management' }}. Client SPOC reference: {{ $jd->approved_by_reference ?: 'Not provided' }}.</div>
    @endif

    <div class="detail-grid">
        <article class="panel detail-main">
            @can('edit', $jd)
                <h2>Edit JD content</h2>
                <form class="form-stack" method="post" action="{{ route('job-descriptions.update', $jd) }}">@csrf @method('patch')
                    @include('job-descriptions._fields', ['jd' => $jd])
                    <button class="button" type="submit">Save JD changes</button>
                </form>
            @else
                <h2>Job description</h2>
                <dl class="detail-list jd-content">
                    <div><dt>Summary</dt><dd class="preserve-lines">{{ $jd->summary ?: 'Not provided' }}</dd></div>
                    <div><dt>Responsibilities</dt><dd class="preserve-lines">{{ $jd->responsibilities ?: 'Not provided' }}</dd></div>
                    <div><dt>Required skills</dt><dd class="preserve-lines">{{ $jd->required_skills ?: 'Not provided' }}</dd></div>
                    <div><dt>Preferred skills</dt><dd class="preserve-lines">{{ $jd->preferred_skills ?: 'Not provided' }}</dd></div>
                    <div><dt>Qualification</dt><dd class="preserve-lines">{{ $jd->qualification ?: 'Not provided' }}</dd></div>
                    <div><dt>Experience</dt><dd>{{ $jd->experience_description ?: 'Not provided' }}</dd></div>
                    <div><dt>Location</dt><dd>{{ $jd->location ?: 'Not provided' }}</dd></div>
                    <div><dt>Work mode</dt><dd>{{ $jd->work_mode ?: 'Not provided' }}</dd></div>
                    <div><dt>Employment / engagement type</dt><dd>{{ $jd->employment_type ?: 'Not provided' }}</dd></div>
                    <div><dt>Compensation information</dt><dd>{{ $jd->compensation_information ?: 'Not provided' }}</dd></div>
                    <div><dt>Additional information</dt><dd class="preserve-lines">{{ $jd->additional_information ?: 'Not provided' }}</dd></div>
                </dl>
            @endcan
            <div class="jd-meta"><span>Created by {{ $jd->creator->name }} on {{ $jd->created_at->format('d M Y H:i') }}</span><span>Last updated {{ $jd->updated_at->format('d M Y H:i') }}</span>@if ($jd->reviewed_at)<span>Internally reviewed by {{ $jd->reviewer?->name ?? '—' }} on {{ $jd->reviewed_at->format('d M Y H:i') }}</span>@endif</div>
        </article>

        <aside class="side-stack">
            @can('submitForReview', $jd)
                <section class="panel action-panel"><h2>Recruiter review</h2><p class="muted">Confirm the content is accurate before requesting Recruitment Manager review.</p><form method="post" action="{{ route('job-descriptions.review', $jd) }}">@csrf<button class="button" type="submit">Submit for review</button></form></section>
            @endcan

            @can('sendForClientApproval', $jd)
                <section class="panel action-panel"><h2>Internal readiness</h2><p class="muted">Your review is recorded separately from the client's decision.</p><form method="post" action="{{ route('job-descriptions.send-for-approval', $jd) }}">@csrf<button class="button" type="submit">Send for client approval</button></form>
                    <form class="form-stack compact-form" method="post" action="{{ route('job-descriptions.return-to-recruiter', $jd) }}">@csrf<label class="field"><span>Return reason</span><textarea name="reason" rows="2" maxlength="2000" required>{{ old('reason') }}</textarea></label><button class="button secondary" type="submit">Return to Recruiter</button></form></section>
            @endcan

            @can('recordClientDecision', $jd)
                <section class="panel"><h2>Record client decision</h2><p class="muted">Management records the outcome of the external client communication. This is not a client portal action.</p>
                    <form class="form-stack compact-form" method="post" action="{{ route('job-descriptions.client-decision', $jd) }}">@csrf
                        <label class="field"><span>Client decision</span><select name="decision" required><option value="approved">Approved</option><option value="changes_requested">Changes requested</option></select></label>
                        <label class="field"><span>Client SPOC reference (if available)</span><input name="client_spoc_reference" maxlength="255" value="{{ old('client_spoc_reference') }}"></label>
                        <label class="field"><span>Client comments (required for changes)</span><textarea name="comments" rows="4" maxlength="10000">{{ old('comments') }}</textarea></label>
                        <button class="button" type="submit">Record client decision</button>
                    </form>
                </section>
            @endcan

            @can('createNextVersion', $jd)
                <section class="panel action-panel"><h2>Prepare a new version</h2><p class="muted">The approved version remains available. This new version must complete review and client approval again.</p><form method="post" action="{{ route('job-descriptions.versions.store', $jd) }}">@csrf<button class="button secondary" type="submit">Create next draft version</button></form></section>
            @endcan

            @if ($jd->clientDecisions->isNotEmpty())
                <section class="panel"><h2>Client decision history</h2><ul class="compact-list">@foreach ($jd->clientDecisions as $decision)<li><strong>{{ $decision->decision === 'approved' ? 'Approved' : 'Changes requested' }}</strong><span>{{ $decision->comments ?: 'No comments provided' }}</span><span class="meta">SPOC: {{ $decision->client_spoc_reference ?: 'Not provided' }}</span><span class="meta">Recorded by {{ $decision->recorder->name }} · {{ $decision->decided_at->format('d M Y H:i') }}</span></li>@endforeach</ul></section>
            @endif
        </aside>
    </div>

    <section class="panel history-panel"><h2>Requirement JD version history</h2><div class="table-wrap"><table><thead><tr><th>Version</th><th>Title</th><th>Status</th><th>Created</th><th>Reviewed</th><th>Client approval</th></tr></thead><tbody>@foreach ($jd->requirement->jobDescriptionVersions as $version)<tr><td><a href="{{ route('job-descriptions.show', $version) }}">v{{ $version->version }}</a></td><td>{{ $version->title }}</td><td>{{ $version->status }}</td><td>{{ $version->created_at->format('d M Y H:i') }}</td><td>{{ $version->reviewed_at?->format('d M Y H:i') ?? '—' }}</td><td>{{ $version->approved_at?->format('d M Y H:i') ?? '—' }}</td></tr>@endforeach</tbody></table></div></section>
</section>
@endsection
