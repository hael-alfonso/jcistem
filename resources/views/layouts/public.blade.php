<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#132b46">
    <meta name="description" content="@yield('description', 'Explore JCI Carmona projects, chapter activities, and ways to take part in Carmona City, Cavite.')">
    <title>@yield('title') | JCI Carmona</title>
    <link rel="icon" href="{{ asset('assets/images/brand/favicon.png') }}">
    @include('shared.public-fonts')
    @foreach(['public', 'landing', 'chapter-public'] as $style)
        <link rel="stylesheet" href="{{ asset('assets/css/'.$style.'.css') }}?v={{ filemtime(public_path('assets/css/'.$style.'.css')) }}">
    @endforeach
    <link rel="stylesheet" href="{{ asset('assets/css/overlay-ui.css') }}?v={{ filemtime(public_path('assets/css/overlay-ui.css')) }}">
    <script defer src="{{ asset('assets/js/landing.js') }}?v={{ filemtime(public_path('assets/js/landing.js')) }}"></script>
</head>
<body id="top">
@include('shared.public-header')
<main id="main">@yield('content')</main>
@include('shared.public-footer')
@include('shared.media-viewer')
</body>
</html>
