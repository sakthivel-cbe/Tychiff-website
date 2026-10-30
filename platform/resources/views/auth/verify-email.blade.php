@extends('layouts.foundation')

@section('title', 'Verify your email')
@section('page-class', 'narrow')

@section('content')
<section class="panel" aria-labelledby="page-title">
    <p class="eyebrow">Account security</p>
    <h1 id="page-title">Verify your email address</h1>
    <p class="lede">Your account can be used after its email address is verified. Open the signed link sent to your account.</p>
    <p class="muted">If the link has expired, request a new one below. The message will not disclose whether an account exists.</p>

    <form class="form-stack" method="post" action="{{ route('verification.send') }}">
        @csrf
        <button class="button" type="submit">Send a new verification link</button>
    </form>
    <div class="form-links"><a href="{{ route('privacy.show') }}">Read the privacy notice</a></div>
</section>
@endsection
