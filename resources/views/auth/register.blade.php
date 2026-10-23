@extends('auth.layout')
@section('title', 'Request an account')
@section('auth-content')
    <p class="eyebrow">JCISTEM member portal</p>
    <h1>Request an account.</h1>
    <p class="auth-description">Tell us about yourself. A JCI Carmona administrator will review your request before you can sign in.</p>
    <form method="POST" action="{{ route('register.store') }}" data-auth-form>
        @csrf
        <x-auth-field name="name" label="Full name" required autocomplete="name" maxlength="200" />
        <x-auth-field name="username" label="Preferred username" required autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="50" pattern="[A-Za-z0-9._-]+" />
        <x-auth-field name="email" label="Email address" type="email" required autocomplete="email" autocapitalize="none" spellcheck="false" maxlength="200" />
        <x-auth-field name="phone" label="Mobile number (optional)" type="tel" autocomplete="tel" maxlength="40" />
        <div class="auth-field"><label for="auth-reason">Why would you like access?</label><textarea id="auth-reason" name="reason" required minlength="20" maxlength="1500" @error('reason') aria-invalid="true" aria-describedby="auth-reason-error" @enderror>{{ old('reason') }}</textarea>@error('reason')<p class="field-error" id="auth-reason-error">{{ $message }}</p>@enderror</div>
        <x-auth-field name="password" label="Password" type="password" required minlength="10" autocomplete="new-password" />
        <x-auth-field name="password_confirmation" label="Confirm password" type="password" required minlength="10" autocomplete="new-password" />
        <label class="auth-consent"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>I agree that JCI Carmona may use these details to review my account request.</span></label>
        @error('consent')<p class="field-error">{{ $message }}</p>@enderror
        <div class="auth-trap" aria-hidden="true"><label for="auth-website">Website</label><input id="auth-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
        <x-auth-action label="Submit request" />
    </form>
    <p class="signin-help">Already have an account? <a href="{{ route('login') }}" data-auth-nav="back">Sign in</a></p>
@endsection
