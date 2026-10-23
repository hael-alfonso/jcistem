@extends('auth.layout')
@section('title', 'Membership application')
@section('auth-content')
    <p class="eyebrow">JCI Carmona · JCISTEM</p>
    <h1>Apply for membership.</h1>
    <p class="auth-description">Tell us a little about yourself and set up your member portal sign-in. A chapter administrator reviews your application before your account is activated.</p>

    <form class="auth-registration-form" method="POST" action="{{ route('register.store') }}" data-auth-form>
        @csrf
        <section class="auth-registration-section" aria-labelledby="application-contact">
            <div class="auth-registration-section-heading"><span>01</span><div><h2 id="application-contact">Your details</h2><p>How the chapter can identify and contact you.</p></div></div>
            <div class="auth-registration-grid">
                <x-auth-field name="name" label="Full name" required autocomplete="name" maxlength="200" />
                <x-auth-field name="phone" label="Mobile number (optional)" type="tel" autocomplete="tel" maxlength="40" />
                <x-auth-field name="email" label="Email address" type="email" required autocomplete="email" autocapitalize="none" spellcheck="false" maxlength="200" />
                <x-auth-field name="address" label="City / address (optional)" autocomplete="street-address" maxlength="500" />
            </div>
        </section>

        <section class="auth-registration-section" aria-labelledby="application-background">
            <div class="auth-registration-section-heading"><span>02</span><div><h2 id="application-background">Your background and interests</h2><p>These details help us understand where you would like to contribute. They are optional.</p></div></div>
            <div class="auth-registration-grid">
                <div class="auth-field"><label for="auth-occupation">Current status</label><div class="auth-input-wrap"><select id="auth-occupation" name="occupation"><option value="">Select if applicable</option>@foreach(['Student', 'Employed', 'Self-employed', 'Other'] as $option)<option value="{{ $option }}" @selected(old('occupation') === $option)>{{ $option }}</option>@endforeach</select></div>@error('occupation')<p class="field-error">{{ $message }}</p>@enderror</div>
                <x-auth-field name="organization" label="School, employer, or business (optional)" maxlength="200" />
                <fieldset class="auth-interests"><legend>Areas of interest</legend><div>@foreach(\App\Support\ChapterForms::AREAS as $interest)<label><input type="checkbox" name="interests[]" value="{{ $interest }}" @checked(in_array($interest, old('interests', []), true))><span>{{ $interest }}</span></label>@endforeach</div>@error('interests')<p class="field-error">{{ $message }}</p>@enderror</fieldset>
                <div class="auth-field auth-field-wide"><label for="auth-motivation">Why would you like to join JCI Carmona? (optional)</label><textarea id="auth-motivation" name="motivation" maxlength="1000" rows="3">{{ old('motivation') }}</textarea>@error('motivation')<p class="field-error">{{ $message }}</p>@enderror</div>
            </div>
        </section>

        <section class="auth-registration-section" aria-labelledby="application-account">
            <div class="auth-registration-section-heading"><span>03</span><div><h2 id="application-account">Create your sign-in</h2><p>You can use your username or email address after Admin approval.</p></div></div>
            <div class="auth-registration-grid">
                <x-auth-field name="username" label="Preferred username" required autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="50" pattern="[A-Za-z0-9._-]+" />
                <p class="auth-registration-hint">Use letters, numbers, dots, hyphens, or underscores.</p>
                <x-auth-field name="password" label="Password" type="password" required minlength="10" autocomplete="new-password" />
                <x-auth-field name="password_confirmation" label="Confirm password" type="password" required minlength="10" autocomplete="new-password" />
            </div>
        </section>

        <label class="auth-consent"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>I confirm that the information I provided is accurate and agree that JCI Carmona may use it to review my membership application and create my account if approved.</span></label>
        @error('consent')<p class="field-error">{{ $message }}</p>@enderror
        <div class="auth-trap" aria-hidden="true"><label for="auth-website">Website</label><input id="auth-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
        <x-auth-action label="Submit application" />
    </form>
    <p class="signin-help auth-registration-help">Already have an account? <a href="{{ route('login') }}" data-auth-nav="back">Sign in</a></p>
@endsection
