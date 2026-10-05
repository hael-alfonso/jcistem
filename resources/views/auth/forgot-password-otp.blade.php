@extends('auth.layout')
@section('title', 'Enter recovery code')
@section('auth-content')
    <div class="eyebrow">ACCOUNT RECOVERY · STEP 2 OF 2</div>
    <h2>Enter your code</h2>
    <p>If the email matches an active account, a six-digit code was sent to <strong>{{ $recoveryEmail }}</strong>. It expires in 10 minutes.</p>
    <form method="POST" action="{{ route('password.otp.verify') }}">
        @csrf
        <x-field name="code" label="6-digit recovery code" :value="old('code')" required inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" autofocus/>
        <button class="btn primary" type="submit"><x-icon name="arrow" /> Verify code</button>
    </form>
    <form method="POST" action="{{ route('password.otp.send') }}" class="signin-resend-form">
        @csrf
        <input type="hidden" name="email" value="{{ $recoveryEmail }}">
        <button class="btn secondary" type="submit"><x-icon name="refresh" /> Send a new code</button>
    </form>
    <p class="signin-help"><a href="{{ route('password.request') }}"><x-icon name="arrowLeft" /> Start over</a></p>
@endsection
