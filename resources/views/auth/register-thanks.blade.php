@extends('auth.layout')
@section('title', 'Request received')
@section('auth-content')
    <p class="eyebrow">Registration request</p>
    <h1>Request received.</h1>
    <p class="auth-description">Your reference is <strong>{{ $reference }}</strong>. Save it if you need to ask the chapter administrator about your request.</p>
    <div class="auth-account-help"><strong>What happens next?</strong><p>An administrator reviews your details. Your JCISTEM account becomes available only if they approve it. You can then sign in with the username and password you chose.</p></div>
    <p class="signin-help"><a href="{{ route('register.status') }}" data-auth-nav="forward">Check request status</a></p>
    <p class="signin-help"><a href="{{ route('login') }}" data-auth-nav="back">Back to sign in</a></p>
@endsection
