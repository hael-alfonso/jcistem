<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') · JCISTEM</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/brand/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/brand/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/chapter.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/jci-theme.css') }}">
</head>
<body class="signin-page">
    <div class="signin-shell">
        <section class="signin-intro" aria-label="About JCISTEM">
            <div class="signin-photo" role="img" aria-label="JCI headquarters sign on a building"></div>
            <div class="signin-identity">
                @include('shared.brand')
                <div>
                    <span class="signin-kicker">JCI CARMONA</span>
                    <h1>Develop leaders. Create positive change.</h1>
                    <p>A shared workspace for JCI Carmona projects, members, and chapter records.</p>
                </div>
            </div>
        </section>
        <main class="signin-main">
            <div class="signin-form">
                <div class="signin-feedback-slot" aria-live="polite" aria-atomic="true">
                    @if($errors->any())
                        <div class="alert error" role="alert">{{ $errors->first() }}</div>
                    @elseif(session('status'))
                        <div class="alert success" role="status">{{ session('status') }}</div>
                    @endif
                </div>
                @yield('auth-content')
            </div>
        </main>
    </div>
</body>
</html>
