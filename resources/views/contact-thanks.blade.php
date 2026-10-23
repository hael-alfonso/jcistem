<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#132b46">
    <title>Inquiry received | JCI Carmona</title><link rel="icon" type="image/png" href="{{ asset('assets/images/brand/favicon.png') }}">
    @include('shared.public-fonts')
    <link rel="stylesheet" href="{{ asset('assets/css/public.css') }}?v={{ filemtime(public_path('assets/css/public.css')) }}"><link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}?v={{ filemtime(public_path('assets/css/landing.css')) }}"><script defer src="{{ asset('assets/js/landing.js') }}?v={{ filemtime(public_path('assets/js/landing.js')) }}"></script>
</head>
<body id="top">@include('shared.public-header')<main id="main" class="contact-thanks"><section class="shell"><span class="contact-success-mark" aria-hidden="true">✓</span><p class="eyebrow">Inquiry received</p><h1>Thank you for contacting<br><span>JCI Carmona.</span></h1><p>Your message is now available to authorized chapter administrators.</p><div class="contact-reference"><span>Your reference</span><strong>{{ $reference }}</strong><small>Keep this number if you need to follow up.</small></div><div class="hero-actions"><a class="button button-primary" href="{{ url('/') }}">Return home <span aria-hidden="true">→</span></a><a class="button button-secondary" href="{{ config('chapter.facebook_url') }}" target="_blank" rel="noopener noreferrer">Visit Facebook <span aria-hidden="true">↗</span></a></div></section></main>@include('shared.public-footer')</body>
</html>
