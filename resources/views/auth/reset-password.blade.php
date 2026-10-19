@extends('auth.layout')
@section('title', 'Reset password')
@section('auth-content')
    <p class="eyebrow">Account recovery</p>
    <h1>Create a new password</h1>
    <p class="auth-description">Choose a password with at least 10 characters for your JCISTEM account. Enter the same password in both fields.</p>
    <form method="POST" action="{{ route('password.update') }}" data-auth-form>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-auth-field name="email" label="Email address" type="email" :value="$email" required autocomplete="username" autocapitalize="none" spellcheck="false"/>
        <x-auth-field name="password" label="New password" type="password" minlength="10" required autocomplete="new-password"/>
        <x-auth-field name="password_confirmation" label="Confirm new password" type="password" minlength="10" required autocomplete="new-password"/>
        <button class="button button-primary auth-submit" type="submit"><span data-submit-label>Save new password</span><span aria-hidden="true">→</span></button>
    </form>
    <p class="signin-help"><a href="{{ route('login') }}" data-auth-nav="back"><span aria-hidden="true">←</span> Back to sign in</a></p>
@endsection
