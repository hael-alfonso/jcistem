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
        <button class="button button-primary auth-submit" type="submit"><span data-submit-label>Verify code</span><span aria-hidden="true">→</span></button>
    </form>
    <form method="POST" action="{{ route('password.otp.send') }}" class="signin-resend-form">
        @csrf
        <input type="hidden" name="email" value="{{ $recoveryEmail }}">
        <button class="button button-secondary" type="submit">Send a new code</button>
    </form>
    <p class="signin-help">Check your spam folder. If the code does not arrive, confirm your email address or contact your chapter administrator.</p>
    <p class="signin-help"><a href="{{ route('password.request') }}" data-auth-nav="back"><span aria-hidden="true">←</span> Use a different email</a></p>
@endsection
