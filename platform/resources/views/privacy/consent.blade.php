@extends('layouts.foundation')

@section('title', 'Privacy notice acknowledgement')
@section('page-class', 'reading')

@section('content')
<section class="panel" aria-labelledby="page-title">
    <p class="eyebrow">Account privacy</p>
    <h1 id="page-title">Privacy notice acknowledgement</h1>
    <p class="lede">Review the current notice and record that it was presented to you.</p>
    <p><a href="{{ route('privacy.show') }}">Read the full privacy notice</a> <span class="version-chip">Version {{ $notice->version }}</span></p>

    <div class="notice info" role="note">
        This records acknowledgement of the Phase 1 platform notice only. It is not candidate consent, client-sharing authorization, marketing consent, or permission for future recruitment workflows. Acknowledgement is not required to use your account foundation.
    </div>

    @if ($acknowledged)
        <p class="notice success" role="status">You have acknowledged this notice version.</p>
    @else
        <form class="form-stack" method="post" action="{{ route('consent.store') }}">
            @csrf
            <label class="checkbox-row checkbox-label" for="acknowledged">
                <input id="acknowledged" name="acknowledged" type="checkbox" value="1" required>
                <span>I have read and acknowledge receipt of this privacy notice version.</span>
            </label>
            @error('acknowledged') <span class="field-error">{{ $message }}</span> @enderror
            <button class="button" type="submit">Record acknowledgement</button>
        </form>
    @endif

    <h2>Your notice history</h2>
    @if ($history->isEmpty())
        <p class="muted">No notice acknowledgement has been recorded for this account.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Notice</th><th>Recorded</th></tr></thead>
                <tbody>
                    @foreach ($history as $record)
                        <tr>
                            <td>{{ $record->noticeVersion?->title }} <span class="version-chip">{{ $record->noticeVersion?->version }}</span></td>
                            <td>{{ $record->recorded_at->format('F j, Y H:i') }} UTC</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
