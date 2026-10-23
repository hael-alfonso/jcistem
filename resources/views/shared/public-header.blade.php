@php
    $isLanding = $isLanding ?? false;
    $loginUrl = $loginUrl ?? route('login');
    $publicLink = fn (string $anchor) => $isLanding ? $anchor : url('/').$anchor;
@endphp
<a class="skip-link" href="#main">Skip to content</a>
<div class="utility-bar"><div class="shell"><span>Local action. A worldwide connection.</span><a href="{{ config('chapter.international_url') }}" target="_blank" rel="noopener noreferrer">Discover JCI worldwide <span aria-hidden="true">↗</span></a></div></div>
<header class="site-header">
    <div class="shell header-inner">
        @include('shared.public-brand', ['homeUrl' => $isLanding ? '#top' : url('/')])
        <nav class="desktop-nav" aria-label="Main navigation">
            <a href="{{ $publicLink('#top') }}">Home</a>
            <details class="nav-dropdown"><summary><span>About</span><x-icon name="chevronDown" class="nav-chevron" /></summary><div class="nav-panel nav-mega nav-mega-three">
                <section><img src="{{ asset('assets/images/community/chapter-meeting.jpg') }}" width="586" height="414" alt="" loading="lazy"><h2>Who we are</h2><a href="{{ $publicLink('#about') }}">Our chapter <span aria-hidden="true">→</span></a><a href="https://jci.cc/about/" target="_blank" rel="noopener noreferrer">Mission and vision <span aria-hidden="true">↗</span></a></section>
                <section><img src="{{ asset('assets/images/community/ngiting-maningning.jpg') }}" width="586" height="414" alt="" loading="lazy"><h2>Leadership in action</h2><a href="{{ $publicLink('#opportunities') }}">Areas of opportunity <span aria-hidden="true">→</span></a><a href="{{ $publicLink('#impact') }}">Community projects <span aria-hidden="true">→</span></a></section>
                <section><img src="{{ asset('assets/images/community/bags2school-group.jpg') }}" width="586" height="414" alt="" loading="lazy"><h2>Our network</h2><a href="{{ config('chapter.national_url') }}" target="_blank" rel="noopener noreferrer">JCI Philippines <span aria-hidden="true">↗</span></a><a href="{{ config('chapter.international_url') }}" target="_blank" rel="noopener noreferrer">JCI Worldwide <span aria-hidden="true">↗</span></a></section>
            </div></details>
            <details class="nav-dropdown"><summary><span>What we do</span><x-icon name="chevronDown" class="nav-chevron" /></summary><div class="nav-panel nav-mega nav-mega-four">
                <section><span class="mega-number">01</span><h2>Individual development</h2><p>Practice leadership through learning and responsibility.</p><a href="{{ $publicLink('#opportunities') }}">Explore opportunities <span aria-hidden="true">→</span></a></section>
                <section><span class="mega-number">02</span><h2>Community impact</h2><p>Work with partners on practical local needs.</p><a href="{{ $publicLink('#impact') }}">See our projects <span aria-hidden="true">→</span></a></section>
                <section><span class="mega-number">03</span><h2>Business &amp; enterprise</h2><p>Build professional skills, ideas, and connections.</p><a href="{{ $publicLink('#opportunities') }}">Learn about JCI <span aria-hidden="true">→</span></a></section>
                <section><span class="mega-number">04</span><h2>International cooperation</h2><p>Connect local action with a worldwide network.</p><a href="{{ config('chapter.international_url') }}" target="_blank" rel="noopener noreferrer">Visit JCI Worldwide <span aria-hidden="true">↗</span></a></section>
            </div></details>
            <details class="nav-dropdown"><summary><span>Where we are</span><x-icon name="chevronDown" class="nav-chevron" /></summary><div class="nav-panel nav-mega nav-location-menu">
                <div><span class="mega-kicker">LOCAL CHAPTER</span><h2>Carmona City, Cavite</h2><p>Young leaders creating positive change with communities and partners in Carmona.</p><a class="button button-primary" href="{{ route('contact') }}">Contact the chapter <span aria-hidden="true">→</span></a></div>
                <nav aria-label="JCI network locations"><a href="{{ config('chapter.national_url') }}" target="_blank" rel="noopener noreferrer"><strong>JCI Philippines</strong><small>Connect with the national organization ↗</small></a><a href="{{ config('chapter.international_url') }}" target="_blank" rel="noopener noreferrer"><strong>JCI Worldwide</strong><small>Explore the global network ↗</small></a><a href="{{ $publicLink('#join') }}"><strong>Get involved locally</strong><small>Membership and collaboration →</small></a></nav>
            </div></details>
            <a href="{{ $publicLink('#partners') }}">Partners</a>
            <a href="{{ $publicLink('#impact') }}">News &amp; Projects</a>
            <a href="{{ $publicLink('#media') }}">Media</a>
            <a href="{{ route('contact') }}" @if(request()->routeIs('contact*')) aria-current="page" @endif>Contact Us</a>
        </nav>
        <a class="button button-primary header-login" href="{{ $loginUrl }}">Member sign in <span aria-hidden="true">↗</span></a>
        <details class="mobile-menu"><summary aria-label="Open navigation menu" title="Open menu"><span class="menu-lines" aria-hidden="true"></span></summary><nav aria-label="Mobile navigation"><a href="{{ $publicLink('#top') }}">Home</a><a href="{{ $publicLink('#about') }}">About JCI Carmona</a><a href="{{ $publicLink('#opportunities') }}">What we do</a><a href="{{ $publicLink('#impact') }}">News &amp; projects</a><a href="{{ $publicLink('#media') }}">Media</a><a href="{{ $publicLink('#partners') }}">Partners</a><a href="{{ route('contact') }}">Contact us</a><a href="{{ $loginUrl }}">Member sign in <span aria-hidden="true">↗</span></a></nav></details>
    </div>
</header>
