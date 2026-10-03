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
                    <h1>Project management for chapter impact.</h1>
                    <p>Plan projects, follow progress, and keep chapter records together in JCISTEM.</p>
                </div>
            </div>
        </section>
        <main class="signin-main">
            <div class="signin-form">
                @if(session('status'))<div class="alert success" role="status">{{ session('status') }}</div>@endif
                @yield('auth-content')
            </div>
        </main>
    </div>
</body>
</html>
