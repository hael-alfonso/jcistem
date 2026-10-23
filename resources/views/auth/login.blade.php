@extends('auth.layout')
@section('title', 'Member sign in')
@section('auth-content')
    <p class="eyebrow">JCISTEM member portal</p>
    <h1>Welcome back.</h1>
    <p class="auth-description">Sign in to your JCI Carmona workspace.</p>
    <form method="POST" action="{{ route('login.attempt') }}" data-auth-form>
        @csrf
        <x-auth-field name="login" label="Username or email address" :value="old('login')" required autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="200"/>
        <x-auth-field name="password" label="Password" type="password" required autocomplete="current-password"/>
        <div class="auth-options"><a href="{{ route('password.request') }}" data-auth-nav="forward">Forgot password?</a></div>
        <x-auth-action label="Sign in" />
    </form>
    <div class="auth-account-help"><strong>Need an account?</strong><p>Submit a registration request. A chapter administrator will review it before you can sign in.</p><a href="{{ route('register') }}" data-auth-nav="forward">Request an account <span aria-hidden="true">→</span></a><a href="{{ route('register.status') }}" data-auth-nav="forward">Check request status <span aria-hidden="true">→</span></a></div>
    <p class="auth-join">New to JCI Carmona? <a href="{{ url('/') }}#join">Learn how to get involved <span aria-hidden="true">↗</span></a></p>
@endsection
