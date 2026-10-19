@extends('auth.layout')
@section('title', 'Account recovery')
@section('auth-content')
    <p class="eyebrow">Account recovery · Step 1 of 3</p>
    <h1>Forgot your password?</h1>
    <p class="auth-description">Enter your registered email address to request a six-digit recovery code.</p>
    <div class="auth-steps" aria-hidden="true"><span class="is-current"></span><span></span><span></span></div>
    <form method="POST" action="{{ route('password.email') }}" data-auth-form>
        @csrf
        <x-auth-field name="email" label="Account email address" type="email" :value="old('email')" required autocomplete="email" autocapitalize="none" spellcheck="false" maxlength="200"/>
        <button class="button button-primary auth-submit" type="submit"><span data-submit-label>Send recovery code</span><span aria-hidden="true">→</span></button>
    </form>
    <p class="signin-help">No longer have access to that email? Contact your chapter administrator for help.</p>
    <p class="signin-help"><a href="{{ route('login') }}" data-auth-nav="back"><span aria-hidden="true">←</span> Back to sign in</a></p>
@endsection
