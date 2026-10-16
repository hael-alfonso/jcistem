@extends('auth.layout')
@section('title', 'Sign in')
@section('auth-content')
    <div class="eyebrow">JCISTEM | SECURE MEMBER PORTAL</div>
    <h2>Welcome, JCI Carmona member.</h2>
    <p>Sign in to manage chapter work, coordinate projects, and keep the organization moving together.</p>
    <form method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <x-field name="login" label="Username or email address" :value="old('login')" required autocomplete="username" autofocus/>
        <x-field name="password" label="Password" type="password" required autocomplete="current-password"/>
        <div class="signin-options"><span class="signin-security-note">Secure access for JCI Carmona members</span><a href="{{ route('password.request') }}" data-auth-nav="forward">Forgot password?</a></div>
        <label class="signin-replace-session"><input type="checkbox" name="replace_session" value="1"><span>Replace an existing sign-in if I cannot access it</span></label>
        <button class="btn primary" type="submit"><x-icon name="arrow" /> Enter member workspace</button>
    </form>
    <p class="signin-help">Need help accessing your account? Contact your JCI Carmona chapter administrator.</p>
@endsection
