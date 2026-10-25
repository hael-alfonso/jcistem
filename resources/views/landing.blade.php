<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#132b46">
    <meta name="description" content="Get to know JCI Carmona: young leaders taking action in Carmona City, Cavite. Explore community stories, learn how to get involved, or sign in to JCISTEM.">
    <meta property="og:title" content="JCI Carmona | Lead with purpose">
    <meta property="og:description" content="Leadership, connection, and community action in Carmona City, Cavite. Discover JCI Carmona and the JCISTEM member portal.">
    <meta property="og:type" content="website">
    <title>JCI Carmona | Lead with purpose</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/brand/favicon.png') }}">
    <link rel="preload" as="image" href="{{ asset('assets/images/community/carmona-technohub-aerial.webp') }}">
    @include('shared.public-fonts')
    <link rel="stylesheet" href="{{ asset('assets/css/public.css') }}?v={{ filemtime(public_path('assets/css/public.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}?v={{ filemtime(public_path('assets/css/landing.css')) }}">
    <script defer src="{{ asset('assets/js/landing.js') }}?v={{ filemtime(public_path('assets/js/landing.js')) }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/chapter-public.css') }}?v={{ filemtime(public_path('assets/css/chapter-public.css')) }}">
</head>
<body id="top">
@php($loginUrl = route('login'))
@include('shared.public-header', ['isLanding' => true, 'loginUrl' => $loginUrl])
<main id="main">
    <section class="hero" aria-labelledby="hero-title"><div class="shell hero-layout">
        <div class="hero-copy"><p class="eyebrow"><span class="eyebrow-line" aria-hidden="true"></span>Young leaders. Local action.</p><h1 id="hero-title"><span>JCI Carmona.</span><br>Lead with purpose.<br>Make a difference.</h1><p>Grow as a leader. Connect with people who care. Join JCI Carmona in turning ideas into action for our community.</p><div class="hero-actions"><a class="button button-primary" href="{{ route('public.involvement') }}">Get involved <span aria-hidden="true">↗</span></a><a class="button button-secondary" href="#about">Discover our chapter <span aria-hidden="true">↓</span></a></div><div class="hero-location"><x-icon name="globe" /><span>{{ config('chapter.location') }}<small>Connected through JCI Philippines</small></span></div></div>
        <figure class="hero-visual"><div class="hero-photo"><a class="media-open" data-media href="{{ asset('assets/images/community/project-lapis.webp') }}" aria-label="View photo: Volunteers and learners at a Project LAPIS literacy session in Barangay Milagrosa, Carmona"><img src="{{ asset('assets/images/community/project-lapis.webp') }}" alt="Volunteers and learners at a Project LAPIS literacy session in Barangay Milagrosa, Carmona" width="1200" height="756" fetchpriority="high"></a><span class="photo-tag">Community in action</span></div><figcaption><div><span class="eyebrow">Learning together</span><strong>Small steps. Meaningful change.</strong><p>Project LAPIS · Carmona City · January 2026</p></div><a href="#impact" aria-label="Explore the Project LAPIS story"><span aria-hidden="true">↗</span></a></figcaption><a class="photo-credit" href="https://mb.com.ph/2026/02/02/carmona-lgu-rolls-out-literacy-project-for-out-of-school-children-youth" target="_blank" rel="noopener noreferrer">Photo: Leonard Danniel M. Diaz / Manila Bulletin ↗</a></figure>
    </div></section>

    <div class="chapter-paths"><div class="shell paths-grid">
        <a href="#opportunities"><span class="path-icon"><x-icon name="users" /></span><span><strong>Find your opportunity</strong><small>Learn, connect, and contribute</small></span><b aria-hidden="true">↗</b></a>
        <a href="#impact"><span class="path-icon"><x-icon name="globe" /></span><span><strong>See community action</strong><small>Explore stories from Carmona</small></span><b aria-hidden="true">↗</b></a>
        <a href="{{ $loginUrl }}"><span class="path-icon"><x-icon name="dashboard" /></span><span><strong>Already a member?</strong><small>Sign in to your JCISTEM workspace</small></span><b aria-hidden="true">↗</b></a>
    </div></div>

    <section class="section shell about-layout" id="about" aria-labelledby="about-title">
        <div class="section-heading"><p class="eyebrow">Meet JCI Carmona</p><h2 id="about-title">A place to grow.<br>A community to<br><span>grow with.</span></h2><a class="text-link" href="{{ config('chapter.national_url') }}" target="_blank" rel="noopener noreferrer">Meet the JCI Philippines network <span aria-hidden="true">↗</span></a></div>
        <div class="about-copy"><p class="lead">Learn new skills. Meet people. Help Carmona.</p><p>JCI Carmona brings young people together to learn, lead, and help the community in Carmona City, Cavite. We are part of the JCI Philippines network.</p><p>Members build skills by planning projects, working in teams, and helping with local activities.</p><div class="mission-card"><span class="eyebrow">The mission we share with JCI</span><p>To provide leadership development opportunities that empower young people to create positive change.</p><a class="text-link" href="https://jci.cc/about/" target="_blank" rel="noopener noreferrer">JCI mission and vision <span aria-hidden="true">↗</span></a></div></div>
    </section>

    <section class="section opportunities" id="opportunities" aria-labelledby="opportunity-title"><div class="shell">
        <div class="section-heading split-heading opportunity-heading"><div><p class="eyebrow">Four areas of opportunity</p><h2 id="opportunity-title">More ways to grow.<br><span>More ways to contribute.</span></h2></div><p>JCI's four areas connect personal development with opportunities to work with others, locally and around the world.</p></div>
        <div class="opportunity-grid">
            <article class="opportunity-card"><a class="media-open opportunity-image" data-media href="{{ asset('assets/images/community/chapter-meeting.jpg') }}" aria-label="View JCI Carmona chapter meeting photo"><img src="{{ asset('assets/images/community/chapter-meeting.jpg') }}" alt="JCI Carmona members at a chapter meeting" width="586" height="414" loading="lazy" decoding="async"></a><h3>Individual Development</h3><p>Practice communication, teamwork, and leadership by learning and taking responsibility.</p></article>
            <article class="opportunity-card"><a class="media-open opportunity-image" data-media href="{{ asset('assets/images/community/bags2school-group.jpg') }}" aria-label="View JCI Carmona Bags2School photo"><img src="{{ asset('assets/images/community/bags2school-group.jpg') }}" alt="JCI Carmona volunteers and children at Bags2School 2026" width="586" height="414" loading="lazy" decoding="async"></a><h3>Community Impact</h3><p>Work with others to understand local needs and take thoughtful, practical action.</p></article>
            <article class="opportunity-card"><a class="media-open opportunity-image" data-media href="{{ asset('assets/images/opportunities/business.webp') }}" aria-label="View JCI business and entrepreneurship photo"><img src="{{ asset('assets/images/opportunities/business.webp') }}" alt="JCI entrepreneurship event with people browsing stalls" width="700" height="700" loading="lazy" decoding="async"></a><h3>Business &amp; Entrepreneurship</h3><p>Exchange ideas, develop an enterprising mindset, and build professional connections.</p></article>
            <article class="opportunity-card"><a class="media-open opportunity-image" data-media href="{{ asset('assets/images/opportunities/international.webp') }}" aria-label="View JCI international cooperation photo"><img src="{{ asset('assets/images/opportunities/international.webp') }}" alt="JCI members gathered with national flags at an international event" width="700" height="467" loading="lazy" decoding="async"></a><h3>International Cooperation</h3><p>Learn from different cultures and connect with young leaders across the JCI network.</p></article>
        </div><a class="text-link opportunity-source" href="{{ config('chapter.international_url') }}" target="_blank" rel="noopener noreferrer">Explore the four areas at JCI Worldwide <span aria-hidden="true">↗</span></a>
    </div></section>

    <section class="section shell" id="impact" aria-labelledby="impact-title">
        <div class="section-heading split-heading"><div><p class="eyebrow">Community stories · 2026</p><h2 id="impact-title">Projects that help.<br><span>People who care.</span></h2></div><p>Start with these selected 2026 projects. Each story includes the activity date and a link to its source.</p></div>
        @include('shared.featured-chapter-stories')
        @include('shared.chapter-gallery')
    </section>

    <section class="section partners-section" id="partners" aria-labelledby="partners-title"><div class="shell partners-layout">
        <div class="section-heading partners-heading"><p class="eyebrow">Community collaboration</p><h2 id="partners-title">Local partners.<br><span>Shared purpose.</span></h2><p>JCI Carmona works alongside civic, education, youth, and community groups to turn local needs into practical action.</p><a class="button button-primary" href="{{ route('contact', ['category' => 'partnership']) }}">Propose a partnership <span aria-hidden="true">→</span></a></div>
        <div class="partners-content">
            <p class="partners-note">Organizations featured in recent JCI Carmona initiatives</p>
            <div class="partner-grid" role="list">
                <article role="listitem"><div class="partner-card-top"><span class="partner-logo"><img src="{{ asset('assets/images/partners/city-of-carmona-optimized.png') }}" width="360" height="360" alt="" loading="lazy"></span><span class="partner-type">Local government</span></div><h3>City Government of Carmona</h3><p>Community programs and citywide youth initiatives.</p></article>
                <article role="listitem"><div class="partner-card-top"><span class="partner-logo"><img src="{{ asset('assets/images/partners/caydo.webp') }}" width="512" height="512" alt="" loading="lazy"></span><span class="partner-type">Youth development</span></div><h3>Community Affairs and Youth Development Office</h3><p>Youth participation, citizenship, and local leadership.</p></article>
                <article role="listitem"><div class="partner-card-top"><span class="partner-logo partner-logo-cvsu"><img src="{{ asset('assets/images/partners/cavite-state-university-optimized.png') }}" width="360" height="325" alt="" loading="lazy"></span><span class="partner-type">Education</span></div><h3>Cavite State University – Carmona Campus</h3><p>Learning, volunteerism, and student engagement.</p></article>
                <article role="listitem"><div class="partner-card-top"><span class="partner-logo"><img src="{{ asset('assets/images/partners/iskolar-ng-bayan.webp') }}" width="512" height="511" alt="" loading="lazy"></span><span class="partner-type">Youth network</span></div><h3>Iskolar ng Bayan ng Carmona Cavite</h3><p>Connecting scholars with service opportunities.</p></article>
                <article role="listitem"><div class="partner-card-top"><span class="partner-logo partner-logo-rotaract"><img src="{{ asset('assets/images/partners/rotaract-optimized.png') }}" width="352" height="360" alt="" loading="lazy"></span><span class="partner-type">Civic network</span></div><h3>Rotaract Club of Carmona</h3><p>Coordinating volunteer-led community action.</p></article>
                <article role="listitem"><div class="partner-card-top"><span class="partner-logo"><img src="{{ asset('assets/images/partners/carmona-childrens-federation.webp') }}" width="512" height="512" alt="" loading="lazy"></span><span class="partner-type">Child participation</span></div><h3>Carmona Children’s Federation</h3><p>Supporting inclusive learning and youth-focused programs.</p></article>
            </div>
            <p class="partners-disclaimer">These groups are named in the activities featured on this website.</p><a class="text-link" href="{{ route('public.partners') }}">See all featured groups and their activities &rarr;</a>
        </div>
    </div></section>

    <section class="portal-section" id="portal" aria-labelledby="portal-title"><div class="shell portal-layout">
        <div class="portal-copy"><div class="portal-system"><img src="{{ asset('assets/images/brand/logo.png') }}" width="49" height="60" alt=""><span>{{ config('chapter.system_name') }}<small>JCI Carmona member portal</small></span></div><p class="eyebrow">For our chapter members</p><h2 id="portal-title">Your chapter work.<br>One connected space.</h2><p>JCISTEM helps members and officers stay organized, follow up on responsibilities, and keep chapter work moving.</p><a class="button button-white" href="{{ $loginUrl }}">Sign in to JCISTEM <span aria-hidden="true">→</span></a><small>Account access requires chapter administrator approval.</small></div>
        <div class="portal-features"><article><span><x-icon name="clipboardCheck" /></span><div><h3>Projects &amp; tasks</h3><p>Follow project progress, check assignments, and coordinate your next steps.</p></div></article><article><span><x-icon name="calendar" /></span><div><h3>Calendar &amp; updates</h3><p>Keep track of chapter schedules and notifications in your workspace.</p></div></article><article><span><x-icon name="report" /></span><div><h3>Documents &amp; records</h3><p>Find the project files and chapter records available to your role.</p></div></article><article><span><x-icon name="receipt" /></span><div><h3>Membership dues</h3><p>Check your dues and payment records. Finance tools are available to authorized officers.</p></div></article></div>
    </div></section>

    <section class="section shell" id="join" aria-labelledby="join-title"><div class="section-heading join-heading"><p class="eyebrow">Start a conversation</p><h2 id="join-title">There's a place for<br><span>your next step.</span></h2><p>Interested in membership, a community initiative, or working together? Connect with the chapter to learn how you can take part.</p></div><div class="join-grid">
        <article><span class="join-number">01 / Become a member</span><h3>Grow with the chapter.</h3><p>Ask about membership, orientation, and the next opportunity to meet JCI Carmona.</p><a class="text-link" href="{{ route('contact', ['category' => 'membership']) }}">Send a membership inquiry <span aria-hidden="true">→</span></a></article>
        <article><span class="join-number">02 / Work together</span><h3>Bring an idea to life.</h3><p>Discuss a community need, a collaboration, or ways your organization could contribute.</p><a class="text-link" href="{{ route('contact', ['category' => 'partnership']) }}">Contact the chapter <span aria-hidden="true">→</span></a></article>
        <article><span class="join-number">03 / Explore the network</span><h3>Find your connection.</h3><p>Use JCI Philippines' membership inquiry form to be referred to a local chapter.</p><a class="text-link" href="{{ config('chapter.membership_url') }}" target="_blank" rel="noopener noreferrer">Visit JCI Philippines <span aria-hidden="true">↗</span></a></article>
    </div></section>

    <section class="faq-section" id="questions" aria-labelledby="faq-title"><div class="shell faq-layout"><div class="section-heading"><p class="eyebrow">A little guidance</p><h2 id="faq-title">Good to know.</h2><p>A few answers to help you find your way.</p></div><div class="faq-list">
        <details><summary>What is JCI Carmona?<span aria-hidden="true">+</span></summary><p>JCI Carmona is a local chapter connected to JCI Philippines and Junior Chamber International. JCI provides opportunities for young people to develop leadership skills and create positive change.</p></details>
        <details><summary>How do I get involved?<span aria-hidden="true">+</span></summary><p>Contact <a href="{{ config('chapter.facebook_url') }}" target="_blank" rel="noopener noreferrer">JCI Carmona on Facebook ↗</a> to ask about membership, orientation, or collaboration. The chapter can explain current opportunities and requirements.</p></details>
        <details><summary>Who can sign in to JCISTEM?<span aria-hidden="true">+</span></summary><p>Members and officers with an active, chapter-approved account can sign in. The tools and records you can access depend on your assigned role. Membership inquiries are handled separately from portal access.</p></details>
        <details><summary>I am a member. How do I access my account?<span aria-hidden="true">+</span></summary><p>Choose <a href="{{ $loginUrl }}">Member sign in</a> and enter your username or registered email and password. If you have forgotten your password, choose <a href="{{ route('password.request') }}">Forgot password?</a>. If you need an account, <a href="{{ route('register') }}">register here</a>. A chapter administrator approves new accounts before sign in.</p></details>
    </div></div></section>
    <section class="section shell" aria-label="Contact JCI Carmona">@include('shared.chapter-connect')</section>
</main>
@include('shared.public-footer')
@include('shared.media-viewer')
</body>
</html>
