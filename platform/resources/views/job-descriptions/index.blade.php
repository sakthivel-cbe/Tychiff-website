@extends('layouts.foundation')

@section('title', 'JD Versions')

@section('content')
<section aria-labelledby="page-title">
    <a class="back-link" href="{{ route('requirements.show', $requirement) }}">← {{ $requirement->reference }}</a>
    <div class="heading-row"><div><p class="eyebrow">{{ $requirement->clientOrganization->name }} · {{ $requirement->job_title }}</p><h1 id="page-title">JD versions</h1><p class="lede">Each version remains available with its own review and client decision history.</p></div>
        @if ($canCreate && ! $latest)<a class="button" href="{{ route('job-descriptions.create', $requirement) }}">Create manual draft</a>@endif
    </div>

    @if ($canCreate)
        <section class="panel ai-action">
            <div><h2>AI drafting assistance</h2><p class="muted">AI produces an unapproved draft from limited Requirement facts. Review and edit every field before submission. Manual drafting remains available if AI is not configured.</p></div>
            <form method="post" action="{{ route('job-descriptions.generate', $requirement) }}">@csrf<button class="button secondary" type="submit">Generate JD Draft with AI</button></form>
        </section>
    @endif

    @if ($requirement->jobDescriptionVersions->isEmpty())
        <div class="panel"><p>No JD versions yet. Create a manual draft or try AI assistance.</p></div>
    @else
        <div class="panel table-wrap"><table><thead><tr><th>Version</th><th>Title</th><th>Status</th><th>Source</th><th>Created</th><th>Reviewed</th><th>Client approval</th></tr></thead><tbody>
            @foreach ($requirement->jobDescriptionVersions as $version)
                <tr><td><a href="{{ route('job-descriptions.show', $version) }}">v{{ $version->version }}</a></td><td>{{ $version->title }}</td><td><span class="status-chip">{{ $version->status }}</span></td><td>{{ str_replace('_', ' ', $version->source) }}</td><td>{{ $version->created_at->format('d M Y H:i') }}</td><td>{{ $version->reviewed_at?->format('d M Y H:i') ?? '—' }}</td><td>{{ $version->approved_at?->format('d M Y H:i') ?? ($version->status === 'Changes Requested' ? 'Changes requested' : 'Pending / not approved') }}</td></tr>
            @endforeach
        </tbody></table></div>
    @endif
</section>
@endsection
