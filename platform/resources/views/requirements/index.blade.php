@extends('layouts.foundation')

@section('title', 'Requirements')

@section('content')
<section aria-labelledby="page-title">
    <p class="eyebrow">Recruitment operations</p>
    <div class="heading-row">
        <div>
            <h1 id="page-title">Requirements</h1>
            <p class="lede">Review client and business intake, recruitment priority and delivery status.</p>
        </div>
        @if ($canCreate)
            <a class="button" href="{{ route('requirements.create') }}">Receive requirement</a>
        @endif
    </div>

    <form class="filter-bar" method="get" action="{{ route('requirements.index') }}">
        <label class="field"><span>Search</span><input type="search" name="q" value="{{ request('q') }}" placeholder="Reference, title or client"></label>
        <label class="field"><span>Status</span><select name="status"><option value="">All statuses</option>@foreach (\App\Models\Requirement::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></label>
        <button class="button secondary" type="submit">Filter</button>
    </form>

    <div class="panel table-wrap">
        @if ($requirements->isEmpty())
            <p class="muted">No requirements match this view.</p>
        @else
            <table>
                <thead><tr><th>Requirement</th><th>Client</th><th>Priority</th><th>Status</th><th>Recruiters</th><th>Target</th></tr></thead>
                <tbody>
                    @foreach ($requirements as $requirement)
                        <tr>
                            <td><a href="{{ route('requirements.show', $requirement) }}"><strong>{{ $requirement->title }}</strong></a><span class="meta block">{{ $requirement->reference }} · {{ $requirement->job_title }}</span></td>
                            <td>{{ $requirement->clientOrganization->name }}</td>
                            <td><span class="priority-chip priority-{{ \Illuminate\Support\Str::slug($requirement->priority) }}">{{ $requirement->priority }}</span></td>
                            <td><span class="status-chip">{{ $requirement->status }}</span></td>
                            <td>{{ $requirement->activeAssignments->pluck('recruiter.name')->filter()->join(', ') ?: 'Unassigned' }}</td>
                            <td>{{ $requirement->target_date?->format('d M Y') ?? 'Not set' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        {{ $requirements->links() }}
    </div>
</section>
@endsection
