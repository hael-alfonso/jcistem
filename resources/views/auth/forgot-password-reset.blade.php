@extends('auth.layout')
@section('title', 'Choose a new password')
@section('auth-content')
    <p class="eyebrow">Account recovery · Step 3 of 3</p>
    <h1>Choose a new password.</h1>
    <p class="auth-description">Use at least 10 characters. Enter the same password in both fields.</p>
    <div class="auth-steps" aria-hidden="true"><span class="is-current"></span><span class="is-current"></span><span class="is-current"></span></div>
    <form method="POST" action="{{ route('password.recovery.update') }}" data-auth-form>
        @csrf
        <x-auth-field name="password" label="New password" type="password" required minlength="10" autocomplete="new-password"/>
        <x-auth-field name="password_confirmation" label="Confirm new password" type="password" required minlength="10" autocomplete="new-password"/>
        <x-auth-action label="Update password" />
    </form>
    <p class="signin-help">After changing your password, all previous sign-ins for this account will be cleared.</p>
@endsection
