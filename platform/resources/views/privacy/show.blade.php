@extends('layouts.foundation')

@section('title', 'Privacy notice')
@section('page-class', 'reading')

@section('content')
<article class="panel" aria-labelledby="page-title">
    <p class="eyebrow">TYCHIFF Platform</p>
    <h1 id="page-title">{{ $notice->title }}</h1>
    <p class="meta">
        <span class="version-chip">Version {{ $notice->version }}</span>
        @if ($notice->published_at)
            <span>Published {{ $notice->published_at->format('F j, Y') }}</span>
        @endif
    </p>
    <div class="notice-copy">{{ $notice->body }}</div>
    @auth
        @if (auth()->user()->hasVerifiedEmail() && auth()->user()->status === 'active')
            <div class="form-links"><a class="button secondary" href="{{ route('consent.index') }}">View acknowledgement</a></div>
        @endif
    @else
        <div class="form-links"><a class="button secondary" href="{{ route('login') }}">Sign in</a></div>
    @endauth
</article>
@endsection
