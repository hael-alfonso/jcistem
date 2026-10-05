@extends('auth.layout')
@section('title', 'Sign in')
@section('auth-content')
    <div class="eyebrow">JCI CARMONA · CHAPTER WORKSPACE</div>
    <h2>Welcome back</h2>
    <p>Sign in to continue your chapter’s projects, people, and records.</p>
    <form method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <x-field name="login" label="Username or email address" :value="old('login')" required autocomplete="username" autofocus/>
        <x-field name="password" label="Password" type="password" required autocomplete="current-password"/>
        <div class="signin-options"><span class="signin-security-note">Secure access for JCI Carmona members</span><a href="{{ route('password.request') }}">Forgot password?</a></div>
        <label class="signin-replace-session"><input type="checkbox" name="replace_session" value="1"><span>Replace an existing sign-in if I can’t access it</span></label>
        <button class="btn primary" type="submit"><x-icon name="arrow" /> Sign in to workspace</button>
    </form>
    <p class="signin-help">Need help accessing your account? Contact your JCI Carmona chapter administrator.</p>
@endsection
