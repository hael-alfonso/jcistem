@extends('auth.layout')
@section('title', 'Choose a new password')
@section('auth-content')
    <div class="eyebrow">JCISTEM | ACCOUNT RECOVERY | PASSWORD</div>
    <h2>Choose a new password</h2>
    <p>Use at least 10 characters. You’ll be able to sign in with your username or email afterward.</p>
    <form method="POST" action="{{ route('password.recovery.update') }}">
        @csrf
        <x-field name="password" label="New password" type="password" required minlength="10" autocomplete="new-password" autofocus/>
        <x-field name="password_confirmation" label="Confirm new password" type="password" required minlength="10" autocomplete="new-password"/>
        <button class="btn primary" type="submit"><x-icon name="save" /> Update password</button>
    </form>
    <p class="signin-help">After changing your password, all previous sign-ins for this account will be cleared.</p>
@endsection
