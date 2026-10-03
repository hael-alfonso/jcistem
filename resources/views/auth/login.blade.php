@extends('auth.layout')
@section('title', 'Sign in')
@section('auth-content')
    <div class="eyebrow">CHAPTER WORKSPACE</div>
    <h2>Welcome back</h2>
    <p>Sign in with your registered JCI Carmona account.</p>
    @if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <x-field name="email" label="Email address" type="email" required autocomplete="username" autofocus/>
        <x-field name="password" label="Password" type="password" required autocomplete="current-password"/>
        <div class="signin-options">
            <label class="check-field"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
            <a href="{{ route('password.request') }}">Forgot password?</a>
        </div>
        <button class="btn primary" type="submit">Sign in to workspace <span aria-hidden="true">→</span></button>
    </form>
    <p class="signin-help">Need access or help signing in? Contact your JCI Carmona chapter administrator.</p>
@endsection
