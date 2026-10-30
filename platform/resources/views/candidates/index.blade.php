@extends('layouts.foundation')

@section('title', 'Candidates')

@section('content')
<section aria-labelledby="page-title">
    <div class="heading-row"><div><p class="eyebrow">Shared TYCHIFF resource</p><h1 id="page-title">Candidate database</h1><p class="lede">One canonical candidate profile with access controlled for each Recruiter.</p></div>@if ($canCreate)<a class="button" href="{{ route('candidates.create') }}">Create candidate</a>@endif</div>
    <form class="filter-bar" method="get" action="{{ route('candidates.index') }}">
        <label class="field"><span>Search authorized profiles</span><input type="search" name="q" value="{{ request('q') }}" placeholder="Name, reference, email or mobile"></label>
        <label class="field"><span>Status</span><select name="status"><option value="">All statuses</option>@foreach (\App\Models\Candidate::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></label>
        <button class="button secondary" type="submit">Filter</button>
    </form>
    <div class="panel table-wrap">
        @if ($candidates->isEmpty())<p class="muted">No candidates are available in your authorized view.</p>@else
            <table><thead><tr><th>Candidate</th><th>Location</th><th>Designation</th><th>Experience</th><th>Status</th><th>Source</th></tr></thead><tbody>
                @foreach ($candidates as $candidate)
                    <tr><td><a href="{{ route('candidates.show', $candidate) }}"><strong>{{ $candidate->full_name }}</strong></a><span class="meta block">{{ $candidate->reference }}</span></td><td>{{ $candidate->location ?: '—' }}</td><td>{{ $candidate->current_designation ?: '—' }}</td><td>{{ $candidate->total_experience_years !== null ? $candidate->total_experience_years.' yrs' : '—' }}</td><td><span class="status-chip">{{ $candidate->status }}</span></td><td>{{ $candidate->source }}</td></tr>
                @endforeach
            </tbody></table>
        @endif
        {{ $candidates->links() }}
    </div>
</section>
@endsection
