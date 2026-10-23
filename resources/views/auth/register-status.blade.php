@extends('auth.layout')
@section('title', 'Registration status')
@section('auth-content')
    <p class="eyebrow">Registration request</p>
    <h1>Check your request.</h1>
    <p class="auth-description">Enter the reference you received and the email address used on your request.</p>
    <form method="POST" action="{{ route('register.status.check') }}" data-auth-form>
        @csrf
        <x-auth-field name="reference" label="Request reference" required maxlength="32" autocapitalize="characters" />
        <x-auth-field name="email" label="Email address" type="email" required autocomplete="email" autocapitalize="none" spellcheck="false" maxlength="200" />
        <x-auth-action label="Check status" />
    </form>
    @isset($registrationStatus)
        <div class="auth-account-help" role="status"><strong>{{ match($registrationStatus) { 'approved' => 'Approved', 'rejected' => 'Not approved', default => 'Awaiting admin review' } }}</strong><p>{{ match($registrationStatus) { 'approved' => 'Your account is active. You can sign in with the username and password you chose.', 'rejected' => 'The chapter administrator did not approve this request. Contact the chapter for help.', default => 'Your request is still waiting for a chapter administrator’s decision.' } }}</p></div>
    @else
        @if(request()->isMethod('post'))<div class="auth-account-help" role="status"><strong>Request not found</strong><p>Check your reference and email address, or contact the chapter administrator for help.</p></div>@endif
    @endisset
    <p class="signin-help"><a href="{{ route('login') }}" data-auth-nav="back">Back to sign in</a></p>
@endsection
