<!doctype html>
<html lang="en" class="{{ request()->routeIs('login', 'scoped.login', 'register.thanks', 'scoped.register.thanks') ? 'auth-flow-back' : 'auth-flow-forward' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#132b46">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Sign in to JCISTEM, the JCI Carmona member portal for chapter projects, tasks, schedules, and records.">
    <title>@yield('title', 'Member sign in') | JCISTEM · JCI Carmona</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/brand/favicon.png') }}">
    <link rel="preload" as="image" href="{{ asset('assets/images/community/jci-scotland-banner.webp') }}">
    @include('shared.public-fonts')
    <link rel="stylesheet" href="{{ asset('assets/css/public.css') }}?v={{ filemtime(public_path('assets/css/public.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/auth.css') }}?v={{ filemtime(public_path('assets/css/auth.css')) }}">
    <script defer src="{{ asset('assets/js/auth-transition.js') }}?v={{ filemtime(public_path('assets/js/auth-transition.js')) }}"></script>
    <script defer src="{{ asset('assets/js/auth.js') }}?v={{ filemtime(public_path('assets/js/auth.js')) }}"></script>
</head>
<body class="auth-page">
    @php($landingUrl = url('/'))
    <a class="skip-link" href="#signin-main">Skip to form</a>
    <header class="auth-header shell">
        @include('shared.public-brand', ['homeUrl' => $landingUrl])
        <a class="auth-home auth-icon-button" href="{{ $landingUrl }}" aria-label="Back to website" title="Back to website"><x-icon name="arrowLeft" /></a>
    </header>
    <div class="auth-shell shell">
        <aside class="auth-intro" aria-label="About the member portal">
            <div class="auth-system"><img src="{{ asset('assets/images/brand/logo.png') }}" width="49" height="60" alt=""><span>{{ config('chapter.system_name') }}<small>JCI Carmona member portal</small></span></div>
            <div class="auth-intro-copy"><p class="eyebrow">Connected by purpose</p><h2>Good things happen<br>when we work<br><span>together.</span></h2><p>Your space to organize chapter work and take the next step with JCI Carmona.</p></div>
            <div class="auth-features"><div><x-icon name="clipboardCheck" /><span><strong>Keep projects moving</strong>Follow progress and your assigned tasks.</span></div><div><x-icon name="calendar" /><span><strong>Stay in the loop</strong>Check schedules and chapter updates.</span></div><div><x-icon name="report" /><span><strong>Find what you need</strong>Access records available to your role.</span></div></div>
            <div class="auth-intro-footer"><span>Lead. Connect. Contribute.</span><span>Carmona City, Cavite</span></div>
        </aside>
        <main class="auth-main" id="signin-main">
            <div class="auth-form-wrap"><div class="auth-form-panel" data-auth-panel>
                <div class="auth-feedback" aria-live="polite" aria-atomic="true">
                    @if($errors->any())
                        <div class="alert error" role="alert">{{ request()->routeIs('login') && $errors->has('email') ? $errors->first('email') : ($errors->has('token') ? $errors->first('token') : 'Please check the details below and try again.') }}</div>
                    @elseif(session('status'))
                        <div class="alert success" role="status">{{ session('status') }}</div>
                    @endif
                </div>
                @yield('auth-content')
            </div></div>
        </main>
    </div>
    <footer class="auth-footer shell"><span>&copy; {{ now()->year }} {{ config('chapter.name') }} · JCISTEM</span><div><a href="{{ $landingUrl }}#questions">Help &amp; account access</a><a href="{{ $landingUrl }}#about">About JCI Carmona</a></div></footer>
</body>
</html>
