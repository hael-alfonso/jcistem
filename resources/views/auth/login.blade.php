<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in · JCI Carmona</title><link rel="stylesheet" href="{{ asset('assets/css/chapter.css') }}"></head>
<body class="login-page"><section class="login-story"><a class="chapter-brand" href="{{ route('login') }}"><span class="brand-word">JCI<span class="brand-spark">✦</span></span><span>CARMONA<small>PROJECT MANAGEMENT</small></span></a><div><div class="eyebrow">LOCAL LEADERS. LASTING IMPACT.</div><h1>Great ideas deserve<br>a place to grow.</h1><p>Bring your chapter's people, projects, and purpose together. Build a better Carmona, one project at a time.</p></div><span class="login-foot">Carmona City, Cavite, Philippines · JCI Carmona</span><div class="banner-orbit"></div></section>
<main class="login-main"><div class="login-box"><div class="eyebrow">WELCOME TO YOUR CHAPTER WORKSPACE</div><h2>Good to have you here.</h2><p>Sign in to continue your chapter's work.</p>
@if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('login.attempt') }}">@csrf
<x-field name="email" label="Email address" type="email" required autocomplete="username" autofocus/>
<x-field name="password" label="Password" type="password" required autocomplete="current-password"/>
<label class="check-field"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
<button class="btn primary">Sign in to workspace →</button></form>
<small>Need an account or help signing in? Contact your chapter administrator.</small><div class="login-meta">One workspace. Four areas of opportunity.<br>Leadership · Community · Business · International cooperation</div>
</div></main></body></html>
