@extends('layouts.foundation')

@section('title', 'Candidate Access')

@section('content')
<section aria-labelledby="page-title">
    <a class="back-link" href="{{ route('candidates.show', $candidate) }}">← {{ $candidate->reference }}</a>
    <p class="eyebrow">Recruitment Manager / Management</p>
    <h1 id="page-title">Manage Recruiter access</h1>
    <p class="lede">{{ $candidate->full_name }} remains one shared TYCHIFF profile. Grant only the Recruiters who need to work with this candidate.</p>

    <section class="panel">
        <h2>Grant access</h2>
        <form class="form-stack" method="post" action="{{ route('candidates.access.grant', $candidate) }}">@csrf
            <label class="field"><span>Recruiter</span><select name="recruiter_id" required><option value="">Choose an active Recruiter</option>@foreach ($recruiters as $recruiter)<option value="{{ $recruiter->id }}">{{ $recruiter->name }}</option>@endforeach</select></label>
            <label class="field"><span>Reason (optional)</span><textarea name="reason" rows="2" maxlength="2000"></textarea></label>
            <button class="button" type="submit">Grant candidate access</button>
        </form>
    </section>

    <section class="panel history-panel"><h2>Access history</h2><div class="table-wrap"><table><thead><tr><th>Recruiter</th><th>Status</th><th>Granted</th><th>Grant reason</th><th>Revoked</th><th>Revocation reason</th><th>Action</th></tr></thead><tbody>
        @forelse ($accessRecords as $access)
            <tr><td>{{ $access->recruiter->name }}</td><td>{{ ucfirst($access->status) }}</td><td>{{ $access->grantedBy->name }} · {{ $access->granted_at->format('d M Y H:i') }}</td><td>{{ $access->grant_reason ?: '—' }}</td><td>{{ $access->revoked_at ? (($access->revokedBy?->name ?? '—').' · '.$access->revoked_at->format('d M Y H:i')) : '—' }}</td><td>{{ $access->revocation_reason ?: '—' }}</td><td>
                @if ($access->status === 'active')<form class="revoke-access-form" method="post" action="{{ route('candidates.access.revoke', [$candidate, $access]) }}">@csrf @method('delete')<label class="field"><span>Reason to revoke</span><input name="reason" maxlength="2000" required></label><button class="text-button" type="submit">Revoke</button></form>@else<span class="meta">Can be granted again</span>@endif
            </td></tr>
        @empty<tr><td colspan="7">No explicit Recruiter access grants yet.</td></tr>@endforelse
    </tbody></table></div></section>
</section>
@endsection
