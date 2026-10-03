@extends('auth.layout')
@section('title', 'Reset password')
@section('auth-content')
    <div class="eyebrow">ACCOUNT RECOVERY</div>
    <h2>Create a new password</h2>
    <p>Choose a password with at least 10 characters for your JCISTEM account.</p>
    @if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field name="email" label="Email address" type="email" :value="$email" required autocomplete="username"/>
        <x-field name="password" label="New password" type="password" minlength="10" required autocomplete="new-password"/>
        <x-field name="password_confirmation" label="Confirm new password" type="password" minlength="10" required autocomplete="new-password"/>
        <button class="btn primary" type="submit">Save new password</button>
    </form>
    <p class="signin-help"><a href="{{ route('login') }}">← Back to sign in</a></p>
@endsection
