@extends('layouts.foundation')

@section('title', 'Account home')

@section('content')
<section class="panel" aria-labelledby="page-title">
    <p class="eyebrow">Phase 1 account foundation</p>
    <h1 id="page-title">{{ $workspace }}</h1>
    <p class="lede">You are signed in to the TYCHIFF platform foundation.</p>
    <div class="workspace-card">
        <strong>{{ auth()->user()->name }}</strong>
        <p class="muted">Your authenticated access is controlled by the server-side role and permission policies.</p>
    </div>
    <p class="muted">Recruitment and staffing workspaces will be introduced in later phases. This foundation page does not expose recruitment records or grant additional permissions.</p>
    <div class="form-links">
        <a class="button secondary" href="{{ route('consent.index') }}">Privacy notice acknowledgement</a>
        <a href="{{ route('privacy.show') }}">Read the privacy notice</a>
    </div>
</section>
@endsection
