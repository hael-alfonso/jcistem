@extends('auth.layout')
@section('title', 'Registration submitted')
@section('auth-content')
    <p class="eyebrow">JCI Carmona membership</p>
    <h1>Application submitted.</h1>
    <p class="auth-description">A JCI Carmona administrator will review your membership application. Your JCISTEM account becomes available after approval.</p>
    <div class="auth-account-help"><strong>What happens next?</strong><p>Once the chapter confirms your account is active, sign in with the username and password you chose. For help, <a href="{{ route('contact', ['category' => 'portal']) }}">contact JCI Carmona</a>.</p></div>
    <p class="signin-help"><a href="{{ route('login') }}" data-auth-nav="back">Back to sign in</a></p>
@endsection
