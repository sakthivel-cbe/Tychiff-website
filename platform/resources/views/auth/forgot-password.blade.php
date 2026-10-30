@extends('layouts.foundation')

@section('title', 'Reset password')
@section('page-class', 'narrow')

@section('content')
<section class="panel" aria-labelledby="page-title">
    <p class="eyebrow">Account access</p>
    <h1 id="page-title">Reset your password</h1>
    <p class="lede">Enter your account email. If it is eligible, we will send password-reset instructions.</p>

    <form class="form-stack" method="post" action="{{ route('password.email') }}">
        @csrf
        <div class="field">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255" autofocus @error('email') aria-invalid="true" @enderror>
            @error('email') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <button class="button" type="submit">Send reset instructions</button>
    </form>

    <div class="form-links"><a href="{{ route('login') }}">Back to sign in</a></div>
</section>
@endsection
