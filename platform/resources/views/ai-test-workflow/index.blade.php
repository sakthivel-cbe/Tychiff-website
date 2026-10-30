@extends('layouts.foundation')

@section('title', 'Local AI test workflow')

@section('content')
<section class="panel">
    <h1>Local resume validation test</h1>
    <p>This local-only workspace is restricted to test fixtures. Imported JDs remain drafts. Running a test does not approve a JD, reject a candidate, submit a candidate or contact a client.</p>
    @if (! config('ai.providers.openai.api_key') && ! config('ai.providers.anthropic.api_key'))
        <p role="status"><strong>AI provider unavailable — live AI inference was not executed.</strong></p>
    @endif
</section>

<section class="panel">
    <h2>Run selected resumes against one JD</h2>
    <form method="post" action="{{ route('ai-test-workflow.run') }}">
        @csrf
        <label for="requirement_id">Test JD</label>
        <select id="requirement_id" name="requirement_id" required>
            @foreach ($requirements as $requirement)
                <option value="{{ $requirement->id }}">{{ $requirement->title }} ({{ $requirement->status }})</option>
            @endforeach
        </select>
        <label for="job_description_version_id">JD version</label>
        <select id="job_description_version_id" name="job_description_version_id" required>
            @foreach ($requirements as $requirement)
                @foreach ($requirement->jobDescriptionVersions as $version)
                    <option value="{{ $version->id }}" data-requirement="{{ $requirement->id }}">{{ $requirement->title }} · v{{ $version->version }} · {{ $version->status }}</option>
                @endforeach
            @endforeach
        </select>
        <fieldset>
            <legend>Resumes</legend>
            @forelse ($documents as $document)
                <label style="display:block; margin:.5rem 0">
                    <input type="checkbox" name="document_ids[]" value="{{ $document->id }}">
                    {{ $document->original_filename }} — {{ $document->extraction_status }}
                    @if ($document->extraction_status !== 'extracted')
                        <button type="submit" formmethod="post" formaction="{{ route('ai-test-workflow.candidate-extract', $document) }}">Extract / retry</button>
                    @endif
                </label>
            @empty
                <p>No authorized test resume documents are currently imported.</p>
            @endforelse
        </fieldset>
        <button type="submit" @disabled($documents->isEmpty() || $requirements->isEmpty())>Run evaluation</button>
    </form>
</section>

<section class="panel">
    <h2>JD document extraction</h2>
    @forelse ($requirements as $requirement)
        <h3>{{ $requirement->title }}</h3>
        @foreach ($requirement->documents as $document)
            <p>{{ $document->original_filename }} — {{ $document->extraction_status }}
                @if ($document->extraction_status !== 'extracted')
                    <form method="post" action="{{ route('ai-test-workflow.requirement-extract', $document) }}">@csrf <button>Extract / retry</button></form>
                @endif
            </p>
        @endforeach
    @empty
        <p>No authorized test JDs are currently imported.</p>
    @endforelse
</section>

<section class="panel">
    <h2>Evaluation history</h2>
    @forelse ($evaluations as $evaluation)
        <article class="panel">
            <h3>{{ $evaluation->candidate->full_name }} · {{ $evaluation->requirement->title }} · {{ $evaluation->jobDescriptionVersion->status }}</h3>
            <p>Evaluation: {{ $evaluation->status }} · Validation: {{ $evaluation->validation_status }} · Scoring: {{ $evaluation->scoring_status }}</p>
            @if ($evaluation->failure_code)
                <p>Reason: {{ $evaluation->failure_code }}</p>
            @endif
            @if ($evaluation->validation_result)
                <h4>Validation evidence result</h4>
                <pre>{{ json_encode($evaluation->validation_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif
            @if ($evaluation->scoring_result)
                <h4>Scoring result</h4>
                <pre>{{ json_encode($evaluation->scoring_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif
            @foreach ($evaluation->agentExecutions as $execution)
                <p>{{ $execution->agent_type }}: {{ $execution->status }} @if($execution->provider) · {{ $execution->provider }} / {{ $execution->model }} @endif</p>
                <ul>@foreach ($execution->evidence as $evidence)<li>{{ $evidence->source_type }} · {{ $evidence->field }} · {{ $evidence->retrieval_reason }}</li>@endforeach</ul>
            @endforeach
        </article>
    @empty
        <p>No evaluations have been requested.</p>
    @endforelse
</section>

<script>
    document.getElementById('requirement_id')?.addEventListener('change', event => {
        const versions = document.getElementById('job_description_version_id');
        [...versions.options].forEach(option => { option.hidden = option.dataset.requirement !== event.target.value; });
        const first = [...versions.options].find(option => !option.hidden);
        if (first) versions.value = first.value;
    });
    document.getElementById('requirement_id')?.dispatchEvent(new Event('change'));
</script>
@endsection
