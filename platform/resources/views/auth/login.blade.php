@extends('layouts.foundation')

@section('title', 'Sign in')
@section('page-class', 'narrow')

@section('content')
<section class="panel" aria-labelledby="page-title">
    <p class="eyebrow">TYCHIFF Platform</p>
    <h1 id="page-title">Sign in to your account</h1>
    <p class="lede">Use the email address associated with your TYCHIFF account.</p>

    <form class="form-stack" method="post" action="{{ route('login.store') }}">
        @csrf
        <div class="field">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required maxlength="255" autofocus @error('email') aria-invalid="true" @enderror>
            @error('email') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required maxlength="255" @error('password') aria-invalid="true" @enderror>
            @error('password') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <label class="checkbox-row checkbox-label" for="remember">
            <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
            <span>Keep me signed in on this device</span>
        </label>
        <button class="button" type="submit">Sign in</button>
    </form>

    <div class="form-links"><a href="{{ route('password.request') }}">Forgot your password?</a></div>
</section>
@endsection
