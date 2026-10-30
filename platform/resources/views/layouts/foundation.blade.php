<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TYCHIFF Platform') · TYCHIFF Solutions</title>
    <link rel="stylesheet" href="{{ asset('css/foundation.css') }}">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="{{ route('privacy.show') }}" aria-label="TYCHIFF Solutions">
            <img src="{{ asset('images/tychiff-logo-light.png') }}" alt="TYCHIFF Solutions">
        </a>
        <nav class="header-nav" aria-label="Main navigation">
            <a href="{{ route('privacy.show') }}">Privacy notice</a>
            @auth
                @if (auth()->user()->hasVerifiedEmail() && auth()->user()->status === 'active')
                    <a href="{{ route('home') }}">Account home</a>
                    <a href="{{ route('consent.index') }}">Notice acknowledgement</a>
                    @can('viewAny', \App\Models\Requirement::class)
                        <a href="{{ route('requirements.index') }}">Requirements</a>
                    @endcan
                    @can('viewAny', \App\Models\Candidate::class)
                        <a href="{{ route('candidates.index') }}">Candidates</a>
                    @endcan
                @endif
                <form class="nav-form" method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Sign out</button>
                </form>
            @else
                <a href="{{ route('login') }}">Sign in</a>
            @endauth
        </nav>
    </div>
</header>

<main class="page-shell @yield('page-class')">
    @if (session('status'))
        <div class="notice success" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="notice error" role="alert" aria-labelledby="error-title">
            <strong id="error-title">Please review the following message.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<footer class="footer">
    <div class="footer-inner">
        <span>TYCHIFF Solutions · Recruitment &amp; Staffing</span>
        <nav class="footer-links" aria-label="Footer navigation">
            <a href="{{ route('privacy.show') }}">Privacy notice</a>
            @guest <a href="{{ route('login') }}">Sign in</a> @endguest
        </nav>
    </div>
</footer>
</body>
</html>
