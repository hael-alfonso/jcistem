@extends('auth.layout')
@section('title', 'Enter recovery code')
@section('auth-content')
    <p class="eyebrow">Account recovery · Step 2 of 3</p>
    <h1>Check your email.</h1>
    <p class="auth-description">If <strong>{{ $recoveryEmail }}</strong> matches an active account, you will receive a six-digit code. Enter it below within 10 minutes.</p>
    <div class="auth-steps" aria-hidden="true"><span class="is-current"></span><span class="is-current"></span><span></span></div>
    <form method="POST" action="{{ route('password.otp.verify') }}" data-auth-form>
        @csrf
        <x-auth-field name="code" label="6-digit recovery code" :value="old('code')" required inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6"/>
        <x-auth-action label="Verify code" />
    </form>
    <form method="POST" action="{{ route('password.otp.send') }}" class="signin-resend-form">
        @csrf
        <input type="hidden" name="email" value="{{ $recoveryEmail }}">
        <button class="button button-secondary auth-icon-button" type="submit" aria-label="Send a new code" title="Send a new code"><x-icon name="refresh" /></button>
    </form>
    <p class="signin-help">Check your spam folder. If the code does not arrive, confirm your email address or contact your chapter administrator.</p>
    <p class="signin-help"><a class="auth-icon-button" href="{{ route('password.request') }}" data-auth-nav="back" aria-label="Use a different email" title="Use a different email"><x-icon name="arrowLeft" /></a></p>
@endsection
