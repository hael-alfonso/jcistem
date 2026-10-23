@extends('auth.layout')
@section('title', 'Register an account')
@section('auth-content')
    <p class="eyebrow">JCISTEM member portal</p>
    <h1>Register an account.</h1>
    <p class="auth-description">Create your JCISTEM account. A chapter administrator will approve your registration before you can sign in.</p>
    <form class="auth-registration-form" method="POST" action="{{ route('register.store') }}" data-auth-form>
        @csrf
        <x-auth-field name="name" label="Full name" required autocomplete="name" maxlength="200" />
        <x-auth-field name="username" label="Preferred username" required autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="50" pattern="[A-Za-z0-9._-]+" />
        <x-auth-field name="email" label="Email address" type="email" required autocomplete="email" autocapitalize="none" spellcheck="false" maxlength="200" />
        <x-auth-field name="phone" label="Mobile number (optional)" type="tel" autocomplete="tel" maxlength="40" />
        <x-auth-field name="password" label="Password" type="password" required minlength="10" autocomplete="new-password" />
        <x-auth-field name="password_confirmation" label="Confirm password" type="password" required minlength="10" autocomplete="new-password" />
        <label class="auth-consent"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>I agree that JCI Carmona may use these details to review my registration.</span></label>
        @error('consent')<p class="field-error">{{ $message }}</p>@enderror
        <div class="auth-trap" aria-hidden="true"><label for="auth-website">Website</label><input id="auth-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
        <x-auth-action label="Register" />
    </form>
    <p class="signin-help auth-registration-help">Already have an account? <a href="{{ route('login') }}" data-auth-nav="back">Sign in</a></p>
@endsection
