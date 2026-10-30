@extends('layouts.foundation')

@section('title', 'Choose a new password')
@section('page-class', 'narrow')

@section('content')
<section class="panel" aria-labelledby="page-title">
    <p class="eyebrow">Account access</p>
    <h1 id="page-title">Choose a new password</h1>
    <p class="lede">Use at least 12 characters. You will need to sign in again after the reset.</p>

    <form class="form-stack" method="post" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="field">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required maxlength="255" autofocus>
        </div>
        <div class="field">
            <label for="password">New password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required minlength="12" maxlength="255">
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="12" maxlength="255">
        </div>
        <button class="button" type="submit">Save new password</button>
    </form>

    <div class="form-links"><a href="{{ route('login') }}">Back to sign in</a></div>
</section>
@endsection
