@extends('auth.layout')
@section('title', 'Account recovery')
@section('auth-content')
    <div class="eyebrow">ACCOUNT RECOVERY · STEP 1 OF 2</div>
    <h2>Reset your password</h2>
    <p>Enter the email address registered to your JCI Carmona account. We’ll send a one-time recovery code.</p>
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <x-field name="email" label="Account email address" type="email" :value="old('email')" required autocomplete="email" autofocus/>
        <button class="btn primary" type="submit"><x-icon name="arrow" /> Send recovery code</button>
    </form>
    <p class="signin-help"><a href="{{ route('login') }}"><x-icon name="arrowLeft" /> Back to sign in</a></p>
@endsection
