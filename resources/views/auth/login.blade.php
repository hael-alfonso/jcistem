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
        <details class="auth-session-help" @if($errors->has('login') && str_contains($errors->first('login'), 'already signed in')) open @endif>
            <summary>Already signed in on another device?</summary>
            <p>If you cannot access that session, select the option below, then sign in. This will sign out your previous session.</p>
            <label><input type="checkbox" name="replace_session" value="1"><span>Replace any existing sign-in</span></label>
        </details>
    </form>
    <div class="auth-account-help"><strong>Need an account?</strong><p>Accounts are provided by the chapter administrator. Contact them to request access or check your account status.</p></div>
    <p class="auth-join">New to JCI Carmona? <a href="{{ url('/') }}#join">Learn how to get involved <span aria-hidden="true">↗</span></a></p>
@endsection
