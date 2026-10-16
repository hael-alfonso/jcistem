<!doctype html>
<html lang="en" class="{{ request()->routeIs('login', 'scoped.login') ? 'auth-flow-back' : 'auth-flow-forward' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') | JCI Carmona Member Portal</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/brand/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/brand/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/chapter.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/jci-theme.css') }}?v={{ filemtime(public_path('assets/css/jci-theme.css')) }}">
    <script defer src="{{ asset('assets/js/auth-transition.js') }}?v={{ filemtime(public_path('assets/js/auth-transition.js')) }}"></script>
</head>
<body class="signin-page">
    @php
        $landingUrl = request()->routeIs('scoped.*')
            ? url('/workspaces/'.request()->route('workspace'))
            : url('/');
    @endphp
    <div class="signin-shell">
        <section class="signin-intro" aria-label="About JCI Carmona">
            <a class="signin-home-link" href="{{ $landingUrl }}" aria-label="Back to JCI Carmona home" title="Back to JCI Carmona home"><span aria-hidden="true">&larr;</span></a>
            <div class="signin-identity">
                <div class="signin-jci-brand signin-system-brand">
                    <img src="{{ asset('assets/images/brand/logo.png') }}" alt="JCISTEM system logo">
                    <span></span><strong>JCI CARMONA<small>CHAPTER MANAGEMENT SYSTEM</small></strong>
                </div>
                <div class="signin-intro-copy">
                    <span class="signin-kicker">OFFICIAL CHAPTER MEMBER PORTAL</span>
                    <h1>One chapter.<br>One shared purpose.</h1>
                    <p>JCISTEM gives JCI Carmona members one organized workspace for projects, chapter records, finances, and collaboration.</p>
                </div>
                <div class="signin-organization-card">
                    <span>BUILT FOR THE ORGANIZATION</span>
                    <strong>One secure chapter workspace</strong>
                    <p>Projects, members, records, finances, and collaboration in one place.</p>
                </div>
                <div class="signin-pillars"><span>LEAD</span><i></i><span>CONNECT</span><i></i><span>SERVE</span></div>
            </div>
        </section>
        <main class="signin-main">
            <div class="signin-form">
                <a class="signin-system-mark" href="{{ $landingUrl }}" aria-label="JCISTEM, JCI Carmona member workspace">
                    <img src="{{ asset('assets/images/brand/logo.png') }}" alt="">
                    <span><strong>JCISTEM</strong><small>JCI CARMONA MEMBER WORKSPACE</small></span>
                </a>
                <div class="signin-form-viewport">
                    <div class="signin-form-panel" data-auth-panel>
                        <div class="signin-feedback-slot" aria-live="polite" aria-atomic="true">
                            @if($errors->any())
                                <div class="alert error" role="alert">{{ $errors->first() }}</div>
                            @elseif(session('status'))
                                <div class="alert success" role="status">{{ session('status') }}</div>
                            @endif
                        </div>
                        @yield('auth-content')
                    </div>
                </div>
                <div class="signin-social"><span>JCI Carmona 2026</span><a href="https://www.facebook.com/jcicarmonaofficial" target="_blank" rel="noopener noreferrer">Official Facebook &#8599;</a><a href="{{ $landingUrl }}#impact">Community impact</a></div>
            </div>
        </main>
    </div>
</body>
</html>
