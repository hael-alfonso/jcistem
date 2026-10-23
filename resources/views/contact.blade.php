<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#132b46">
    <meta name="description" content="Contact JCI Carmona about membership, partnerships, community projects, media requests, or JCISTEM account support.">
    <title>Contact us | JCI Carmona</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/brand/favicon.png') }}">
    @include('shared.public-fonts')
    <link rel="stylesheet" href="{{ asset('assets/css/public.css') }}?v={{ filemtime(public_path('assets/css/public.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}?v={{ filemtime(public_path('assets/css/landing.css')) }}">
    <script defer src="{{ asset('assets/js/landing.js') }}?v={{ filemtime(public_path('assets/js/landing.js')) }}"></script>
</head>
<body id="top">
@include('shared.public-header')
<main id="main" class="contact-page">
    <section class="contact-intro"><div class="shell contact-intro-grid">
        <div><p class="eyebrow">Contact JCI Carmona</p><h1>Let’s start a<br><span>useful conversation.</span></h1><p>Tell us what you need and the right chapter officer will review your message inside JCISTEM.</p></div>
        <div class="contact-route-list" aria-label="Types of inquiries"><span><x-icon name="users" />Membership &amp; orientation</span><span><x-icon name="handshake" />Projects &amp; partnerships</span><span><x-icon name="megaphone" />Media &amp; speaking requests</span><span><x-icon name="support" />Member portal support</span></div>
    </div></section>
    <section class="section shell contact-layout">
        <form class="public-contact-form" method="POST" action="{{ route('contact.store') }}" novalidate>
            @csrf
            @if($errors->any())<div class="contact-errors" role="alert"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="contact-form-grid">
                <label><span>Full name <em>Required</em></span><input name="name" value="{{ old('name') }}" required autocomplete="name" maxlength="120" @error('name') aria-invalid="true" @enderror></label>
                <label><span>Email address <em>Required</em></span><input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" maxlength="200" @error('email') aria-invalid="true" @enderror></label>
                <label><span>Mobile number <small>Optional</small></span><input type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" maxlength="40"></label>
                <label><span>Organization <small>Optional</small></span><input name="organization" value="{{ old('organization') }}" autocomplete="organization" maxlength="160"></label>
                <label class="contact-form-full"><span>How can we help? <em>Required</em></span><select name="category" required><option value="">Select a topic</option>@foreach($categories as $value => $label)<option value="{{ $value }}" @selected(old('category', request('category')) === $value)>{{ $label }}</option>@endforeach</select></label>
                <label class="contact-form-full"><span>Subject <em>Required</em></span><input name="subject" value="{{ old('subject') }}" required maxlength="180" placeholder="A short summary of your inquiry"></label>
                <label class="contact-form-full"><span>Message <em>Required</em></span><textarea name="message" required minlength="30" maxlength="5000" rows="7" placeholder="Share the relevant details, preferred next step, and any useful dates.">{{ old('message') }}</textarea><small>30–5,000 characters. Please do not include passwords or financial account details.</small></label>
                <label class="contact-honeypot" aria-hidden="true" tabindex="-1">Website<input name="website" tabindex="-1" autocomplete="off"></label>
                <label class="contact-consent contact-form-full"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>I agree that JCI Carmona may use the details above to review and respond to this inquiry. <em>Required</em></span></label>
            </div>
            <div class="contact-form-footer"><p>Your details are available only to authorized administrators handling chapter inquiries.</p><button class="button button-primary" type="submit">Send inquiry <x-icon name="send" /></button></div>
        </form>
        <aside class="contact-guidance contact-details"><p class="eyebrow">Contact JCI Carmona</p><h2>General inquiries</h2><p>Have a question, suggestion, or opportunity to share? Complete the form and an authorized chapter administrator will route it to the right officer.</p><div class="contact-detail-list"><div><span class="contact-detail-icon"><x-icon name="mapPin" /></span><p><strong>Chapter location</strong>{{ config('chapter.location') }}</p></div><div><span class="contact-detail-icon"><x-icon name="clipboardCheck" /></span><p><strong>What happens next</strong>Your message is recorded in JCISTEM with a reference number for tracking.</p></div><div><span class="contact-detail-icon"><x-icon name="link" /></span><p><strong>Connect with the chapter</strong><a href="{{ config('chapter.facebook_url') }}" target="_blank" rel="noopener noreferrer">Facebook</a><span aria-hidden="true"> · </span><a href="{{ config('chapter.instagram_url') }}" target="_blank" rel="noopener noreferrer">Instagram</a></p></div></div><div class="contact-response-note"><strong>Choose the closest topic</strong><p>Membership, projects and partnerships, media requests, or member portal support. Fields marked required must be completed.</p></div></aside>
    </section>
</main>
@include('shared.public-footer')
</body>
</html>
