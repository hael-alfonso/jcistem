@extends('auth.layout')
@section('title', 'Membership application')
@section('auth-content')
    <p class="eyebrow">JCI Carmona · JCISTEM</p>
    <h1>Apply for membership.</h1>
    <p class="auth-description">Tell us a little about yourself and set up your member portal sign-in. A chapter administrator reviews your application before your account is activated.</p>

    <form class="auth-registration-form" method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data" data-auth-form>
        @csrf
        <section class="auth-registration-section" aria-labelledby="application-contact">
            <div class="auth-registration-section-heading"><span>01</span><div><h2 id="application-contact">Your details</h2><p>How the chapter can identify and contact you.</p></div></div>
            <div class="auth-registration-grid">
                <div class="auth-field"><label for="auth-title">Title (optional)</label><div class="auth-input-wrap"><select id="auth-title" name="title"><option value="">Select if applicable</option>@foreach(['Mr.', 'Ms.', 'Mrs.', 'Dr.', 'Other'] as $option)<option value="{{ $option }}" @selected(old('title') === $option)>{{ $option }}</option>@endforeach</select></div>@error('title')<p class="field-error">{{ $message }}</p>@enderror</div>
                <x-auth-field name="given_name" label="First / given name" required autocomplete="given-name" maxlength="100" />
                <x-auth-field name="family_name" label="Surname / family name" required autocomplete="family-name" maxlength="100" />
                <x-auth-field name="date_of_birth" label="Date of birth (optional)" type="date" max="{{ now()->subDay()->toDateString() }}" />
                <x-auth-field name="phone" label="Mobile number" type="tel" autocomplete="tel" maxlength="40" required />
                <x-auth-field name="email" label="Email address" type="email" required autocomplete="email" autocapitalize="none" spellcheck="false" maxlength="200" />
                <x-auth-field name="address" label="Home address (optional)" autocomplete="street-address" maxlength="500" />
                <x-auth-field name="city" label="City / town (optional)" autocomplete="address-level2" maxlength="100" />
                <x-auth-field name="home_phone" label="Home phone (optional)" type="tel" maxlength="40" />
            </div>
        </section>

        <section class="auth-registration-section" aria-labelledby="application-background">
            <div class="auth-registration-section-heading"><span>02</span><div><h2 id="application-background">Your background and interests</h2><p>These details help us understand where you would like to contribute. They are optional.</p></div></div>
            <div class="auth-registration-grid">
                <div class="auth-field"><label for="auth-occupation">Current status</label><div class="auth-input-wrap"><select id="auth-occupation" name="occupation"><option value="">Select if applicable</option>@foreach(['Student', 'Employed', 'Self-employed', 'Other'] as $option)<option value="{{ $option }}" @selected(old('occupation') === $option)>{{ $option }}</option>@endforeach</select></div>@error('occupation')<p class="field-error">{{ $message }}</p>@enderror</div>
                <x-auth-field name="organization" label="School, employer, or business (optional)" maxlength="200" />
                <x-auth-field name="position" label="Current position or role (optional)" maxlength="160" />
                <x-auth-field name="business_sector" label="Business sector (optional)" maxlength="160" />
                <x-auth-field name="university_major" label="University / course or major (optional)" maxlength="160" />
                <fieldset class="auth-interests"><legend>Areas of interest</legend><div>@foreach(\App\Support\ChapterForms::AREAS as $interest)<label><input type="checkbox" name="interests[]" value="{{ $interest }}" @checked(in_array($interest, old('interests', []), true))><span>{{ $interest }}</span></label>@endforeach</div>@error('interests')<p class="field-error">{{ $message }}</p>@enderror</fieldset>
                <fieldset class="auth-interests"><legend>Activities you would enjoy (optional)</legend><div>@foreach(\App\Support\ChapterForms::MEMBERSHIP_INTERESTS as $interest)<label><input type="checkbox" name="interest_topics[]" value="{{ $interest }}" @checked(in_array($interest, old('interest_topics', []), true))><span>{{ $interest }}</span></label>@endforeach</div>@error('interest_topics')<p class="field-error">{{ $message }}</p>@enderror</fieldset>
                <div class="auth-field auth-field-wide"><label for="auth-motivation">Why would you like to join JCI Carmona? (optional)</label><textarea id="auth-motivation" name="motivation" maxlength="1000" rows="3">{{ old('motivation') }}</textarea>@error('motivation')<p class="field-error">{{ $message }}</p>@enderror</div>
                <x-auth-field name="photo" label="Applicant photo (optional, JPG, PNG, or WebP)" type="file" accept="image/jpeg,image/png,image/webp" />
            </div>
        </section>

        <section class="auth-registration-section" aria-labelledby="application-account">
            <div class="auth-registration-section-heading"><span>03</span><div><h2 id="application-account">Create your sign-in</h2><p>You can use your username or email address after Admin approval. Your JCI Carmona member ID is assigned automatically when approved.</p></div></div>
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
