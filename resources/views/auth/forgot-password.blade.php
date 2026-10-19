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
        <x-auth-action label="Send recovery code" />
    </form>
    <p class="signin-help">No longer have access to that email? Contact your chapter administrator for help.</p>
    <p class="signin-help"><a class="auth-icon-button" href="{{ route('login') }}" data-auth-nav="back" aria-label="Back to sign in" title="Back to sign in"><x-icon name="arrowLeft" /></a></p>
@endsection
