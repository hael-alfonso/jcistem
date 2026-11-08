@extends('auth.layout')
@section('title', 'Enter recovery code')
@section('auth-content')
    <p class="eyebrow">Account recovery <span class="auth-step-count">02 / 03</span></p>
    <h1>Check your email.</h1>
    <p class="auth-description">If <strong>{{ $recoveryEmail }}</strong> matches an active account, a recovery code is on its way. Enter it within 10 minutes.</p>
    <div class="auth-steps" aria-hidden="true"><span class="is-current"></span><span class="is-current"></span><span></span></div>
    <form method="POST" action="{{ route('password.otp.verify') }}" data-auth-form data-otp-form>
        @csrf
        <div class="auth-field auth-otp-field">
            <label id="recovery-code-label" for="auth-code-1">6-digit recovery code</label>
            <p class="auth-otp-hint" id="recovery-code-hint">Paste the full code, or enter one digit in each box.</p>
            <input class="auth-otp-fallback" name="code" id="auth-code-fallback" type="text" value="{{ old('code') }}" required inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" aria-describedby="recovery-code-hint" @if($errors->has('code')) aria-invalid="true" @endif>
            <div class="auth-otp-cells" data-otp-cells role="group" aria-labelledby="recovery-code-label" aria-describedby="recovery-code-hint" hidden>
                @for($digit = 1; $digit <= 6; $digit++)
                    <input id="auth-code-{{ $digit }}" class="auth-otp-cell" type="text" inputmode="numeric" pattern="[0-9]" maxlength="{{ $digit === 1 ? 6 : 1 }}" required disabled aria-label="Digit {{ $digit }} of 6" @if($digit === 1) autocomplete="one-time-code" @else autocomplete="off" @endif @if($errors->has('code')) aria-invalid="true" aria-describedby="auth-code-error" @endif>
                @endfor
            </div>
            @error('code')<p class="field-error" id="auth-code-error">{{ $message }}</p>@enderror
        </div>
        <x-auth-action label="Verify code" />
    </form>
    <form method="POST" action="{{ route('password.otp.send') }}" class="signin-resend-form">
        @csrf
        <input type="hidden" name="email" value="{{ $recoveryEmail }}">
        <button class="button button-secondary" type="submit"><span data-submit-label>Send a new code</span></button>
    </form>
    <p class="signin-help auth-recovery-note">No email yet? Check Spam or Promotions, then confirm the address above.</p>
    <a class="auth-recovery-back" href="{{ route('password.request') }}" data-auth-nav="back"><x-icon name="arrowLeft" /> Use a different email</a>
@endsection
