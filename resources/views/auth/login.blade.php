<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · JCISTEM</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/brand/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/brand/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/chapter.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/jci-theme.css') }}">
</head>
<body class="login-page">
    <section class="login-story">
        @include('shared.brand')
        <div class="login-story-copy">
            <div class="eyebrow">LOCAL LEADERS. LASTING IMPACT.</div>
            <h1>Great ideas deserve<br>a place to grow.</h1>
            <p>Bring your chapter's people, projects, and purpose together. Build a better Carmona, one project at a time.</p>
        </div>
        <span class="login-foot">Carmona City, Cavite, Philippines · JCISTEM</span>
        <div class="banner-orbit" aria-hidden="true"></div>
    </section>
    <main class="login-main">
        <div class="login-box">
            <div class="eyebrow">JCISTEM WORKSPACE</div>
            <h2>Welcome back.</h2>
            <p>Sign in to see your projects, tasks, and chapter updates.</p>
            @if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('login.attempt') }}">
                @csrf
                <x-field name="email" label="Email address" type="email" required autocomplete="username" autofocus/>
                <x-field name="password" label="Password" type="password" required autocomplete="current-password"/>
                <label class="check-field"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                <button class="btn primary" type="submit">Sign in to workspace <span aria-hidden="true">→</span></button>
            </form>
            <small>Need an account or help signing in? Contact your chapter administrator.</small>
            <div class="login-meta">Leadership · Community · Business · International cooperation</div>
        </div>
    </main>
</body>
</html>
