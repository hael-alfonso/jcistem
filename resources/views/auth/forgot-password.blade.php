@extends('auth.layout')
@section('title', 'Forgot password')
@section('auth-content')
    <div class="eyebrow">ACCOUNT RECOVERY</div>
    <h2>Forgot your password?</h2>
    <p>Enter your registered email address to request a reset link.</p>
    @if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <x-field name="email" label="Email address" type="email" :value="old('email')" required autocomplete="username" autofocus/>
        <button class="btn primary" type="submit">Request reset link</button>
    </form>
    <p class="signin-help"><a href="{{ route('login') }}">← Back to sign in</a></p>
@endsection
