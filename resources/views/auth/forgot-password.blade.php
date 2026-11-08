@extends('auth.layout')
@section('title', 'Account recovery')
@section('auth-content')
    <p class="eyebrow">Account recovery <span class="auth-step-count">01 / 03</span></p>
    <h1>Forgot your password?</h1>
    <p class="auth-description">Enter the email address linked to your JCI Carmona account. We will send a six-digit code to continue.</p>
    <div class="auth-steps" aria-hidden="true"><span class="is-current"></span><span></span><span></span></div>
    <form method="POST" action="{{ route('password.email') }}" data-auth-form>
        @csrf
        <div class="auth-recovery-input-card">
            <x-auth-field name="email" label="Account email address" type="email" :value="old('email')" required autocomplete="email" autocapitalize="none" spellcheck="false" maxlength="200"/>
            <p>Use the address registered with your chapter account.</p>
        </div>
        <x-auth-action label="Send recovery code" />
    </form>
    <p class="signin-help auth-recovery-note">No access to that email? Contact your chapter administrator.</p>
    <a class="auth-recovery-back" href="{{ route('login') }}" data-auth-nav="back"><x-icon name="arrowLeft" /> Back to sign in</a>
@endsection
