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
    @include('shared.public-fonts')
    <link rel="stylesheet" href="{{ asset('assets/css/public.css') }}?v={{ filemtime(public_path('assets/css/public.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}?v={{ filemtime(public_path('assets/css/landing.css')) }}">
    <script defer src="{{ asset('assets/js/landing.js') }}?v={{ filemtime(public_path('assets/js/landing.js')) }}"></script>
</head>
<body id="top">
@php($loginUrl = route('login'))
<a class="skip-link" href="#main">Skip to content</a>
<div class="utility-bar"><div class="shell"><span>Local action. A worldwide connection.</span><a href="{{ config('chapter.international_url') }}" target="_blank" rel="noopener noreferrer">Discover JCI worldwide <span aria-hidden="true">↗</span></a></div></div>
<header class="site-header">
    <div class="shell header-inner">
        @include('shared.public-brand', ['homeUrl' => '#top'])
        <nav class="desktop-nav" aria-label="Main navigation"><a href="#about">About us</a><a href="#opportunities">What we do</a><a href="#impact">Community stories</a><a href="#join">Get involved</a></nav>
        <a class="button button-primary header-login" href="{{ $loginUrl }}">Member sign in <span aria-hidden="true">↗</span></a>
        <details class="mobile-menu"><summary aria-label="Open navigation menu"><span class="menu-lines" aria-hidden="true"></span>Menu</summary><nav aria-label="Mobile navigation"><a href="#about">About us</a><a href="#opportunities">What we do</a><a href="#impact">Community stories</a><a href="#portal">Member portal</a><a href="#join">Get involved</a><a href="{{ $loginUrl }}">Member sign in <span aria-hidden="true">↗</span></a></nav></details>
    </div>
</header>
<main id="main">
    <section class="hero" aria-labelledby="hero-title"><div class="shell hero-layout">
        <div class="hero-copy"><p class="eyebrow"><span class="eyebrow-line" aria-hidden="true"></span>Young leaders. Local action.</p><h1 id="hero-title">Lead with purpose.<br>Make a difference<br><span>in Carmona.</span></h1><p>Grow as a leader. Connect with people who care. Join JCI Carmona in turning ideas into action for our community.</p><div class="hero-actions"><a class="button button-primary" href="#join">Get involved <span aria-hidden="true">↗</span></a><a class="button button-secondary" href="#about">Discover our chapter <span aria-hidden="true">↓</span></a></div><div class="hero-location"><x-icon name="globe" /><span>{{ config('chapter.location') }}<small>Connected through JCI Philippines</small></span></div></div>
        <figure class="hero-visual"><div class="hero-photo"><a class="media-open" data-media href="{{ asset('assets/images/community/project-lapis.webp') }}" aria-label="View photo: Volunteers and learners at a Project LAPIS literacy session in Barangay Milagrosa, Carmona"><img src="{{ asset('assets/images/community/project-lapis.webp') }}" alt="Volunteers and learners at a Project LAPIS literacy session in Barangay Milagrosa, Carmona" width="1200" height="756" fetchpriority="high"></a><span class="photo-tag">Community in action</span></div><figcaption><div><span class="eyebrow">Learning together</span><strong>Small steps. Meaningful change.</strong><p>Project LAPIS · Carmona City · January 2026</p></div><a href="#impact" aria-label="Explore the Project LAPIS story"><span aria-hidden="true">↗</span></a></figcaption><a class="photo-credit" href="https://mb.com.ph/2026/02/02/carmona-lgu-rolls-out-literacy-project-for-out-of-school-children-youth" target="_blank" rel="noopener noreferrer">Photo: Leonard Danniel M. Diaz / Manila Bulletin ↗</a></figure>
    </div></section>

    <div class="chapter-paths"><div class="shell paths-grid">
        <a href="#opportunities"><span class="path-icon"><x-icon name="users" /></span><span><strong>Find your opportunity</strong><small>Learn, connect, and contribute</small></span><b aria-hidden="true">↗</b></a>
        <a href="#impact"><span class="path-icon"><x-icon name="globe" /></span><span><strong>See community action</strong><small>Explore stories from Carmona</small></span><b aria-hidden="true">↗</b></a>
        <a href="{{ $loginUrl }}"><span class="path-icon"><x-icon name="dashboard" /></span><span><strong>Already a member?</strong><small>Sign in to your JCISTEM workspace</small></span><b aria-hidden="true">↗</b></a>
    </div></div>

    <section class="section shell about-layout" id="about" aria-labelledby="about-title">
        <div class="section-heading"><p class="eyebrow">Meet JCI Carmona</p><h2 id="about-title">A place to grow.<br>A community to<br><span>grow with.</span></h2><a class="text-link" href="{{ config('chapter.national_url') }}" target="_blank" rel="noopener noreferrer">Meet the JCI Philippines network <span aria-hidden="true">↗</span></a></div>
        <div class="about-copy"><p class="lead">Leadership starts with people willing to take part.</p><p>JCI Carmona brings young people together around leadership and community action in Carmona City, Cavite. Our chapter connects local participation with the wider JCI movement.</p><p>Through shared projects, learning, and collaboration, members have opportunities to take responsibility, develop their skills, and contribute to their community.</p><div class="mission-card"><span class="eyebrow">The mission we share with JCI</span><p>To provide leadership development opportunities that empower young people to create positive change.</p><a class="text-link" href="https://jci.cc/about/" target="_blank" rel="noopener noreferrer">JCI mission and vision <span aria-hidden="true">↗</span></a></div></div>
    </section>

    <section class="section opportunities" id="opportunities" aria-labelledby="opportunity-title"><div class="shell">
        <div class="section-heading split-heading"><div><p class="eyebrow">Four areas of opportunity</p><h2 id="opportunity-title">More ways to grow.<br><span>More ways to contribute.</span></h2></div><p>JCI's four areas connect personal development with opportunities to work with others, locally and around the world.</p></div>
        <div class="opportunity-grid">
            <article class="opportunity-card"><div class="opportunity-top"><x-icon name="users" /><span>01</span></div><h3>Individual<br> Development</h3><p>Practice communication, teamwork, and leadership by learning and taking responsibility.</p><span class="opportunity-note">Build your confidence</span></article>
            <article class="opportunity-card"><div class="opportunity-top"><x-icon name="globe" /><span>02</span></div><h3>Community<br> Impact</h3><p>Work with others to understand local needs and take thoughtful, practical action.</p><span class="opportunity-note">Contribute where you live</span></article>
            <article class="opportunity-card"><div class="opportunity-top"><x-icon name="briefcase" /><span>03</span></div><h3>Business &amp;<br> Entrepreneurship</h3><p>Exchange ideas, develop an enterprising mindset, and build professional connections.</p><span class="opportunity-note">Explore new possibilities</span></article>
            <article class="opportunity-card"><div class="opportunity-top"><x-icon name="globe" /><span>04</span></div><h3>International<br> Cooperation</h3><p>Learn from different cultures and connect with young leaders across the JCI network.</p><span class="opportunity-note">Broaden your perspective</span></article>
        </div><a class="text-link opportunity-source" href="{{ config('chapter.international_url') }}" target="_blank" rel="noopener noreferrer">Explore the four areas at JCI Worldwide <span aria-hidden="true">↗</span></a>
    </div></section>

    <section class="section shell" id="impact" aria-labelledby="impact-title">
        <div class="section-heading split-heading"><div><p class="eyebrow">Community stories · 2026</p><h2 id="impact-title">Projects with purpose.<br><span>People at the heart.</span></h2></div><p>Explore recent projects and chapter updates, with event dates and reports from JCI Carmona and its community partners.</p></div>
        @include('shared.recent-projects')
        <div class="story-grid">
            <article class="story-card"><div class="story-image"><a class="media-open" data-media href="{{ asset('assets/images/community/lapis-learning.webp') }}" aria-label="View photo: A Project LAPIS volunteer supports learners during a literacy activity"><img src="{{ asset('assets/images/community/lapis-learning.webp') }}" alt="A Project LAPIS volunteer supports learners during a literacy activity" width="1200" height="756" loading="lazy"></a></div><div class="story-body"><div class="story-meta"><span>Education &amp; community</span><time datetime="2026-02-02">2 February 2026</time></div><h3>Learning opens doors.</h3><p>JCI Carmona was among the partners in Carmona City's Project LAPIS, a literacy initiative for out-of-school children and youth. Pilot learning sessions began in Barangay Milagrosa in January 2026.</p><a class="text-link" href="https://mb.com.ph/2026/02/02/carmona-lgu-rolls-out-literacy-project-for-out-of-school-children-youth" target="_blank" rel="noopener noreferrer">Read in Manila Bulletin <span aria-hidden="true">↗</span></a><small class="story-credit">Photo: Leonard Danniel M. Diaz / Manila Bulletin</small></div></article>
            <article class="story-card"><div class="forum-art" aria-hidden="true"><span>CALABARZON · 2026</span><strong>Every<br>contribution<br><em>matters.</em></strong><div>International Volunteer Year<span>14 July 2026</span></div></div><div class="story-body"><div class="story-meta"><span>Volunteerism &amp; connection</span><time datetime="2026-07-31">31 July 2026</time></div><h3>Every contribution matters.</h3><p>At the CALABARZON International Volunteer Year forum on 14 July 2026, JCI Carmona shared perspectives on mobilizing young people to support vulnerable groups.</p><a class="text-link" href="https://pnvsca.gov.ph/?p=9684" target="_blank" rel="noopener noreferrer">Read the PNVSCA report <span aria-hidden="true">↗</span></a><small class="story-credit">Source: Philippine National Volunteer Service Coordinating Agency</small></div></article>
        </div>
        @include('shared.chapter-gallery')
        @include('shared.chapter-update')
    </section>

    <section class="portal-section" id="portal" aria-labelledby="portal-title"><div class="shell portal-layout">
        <div class="portal-copy"><div class="portal-system"><img src="{{ asset('assets/images/brand/logo.png') }}" width="49" height="60" alt=""><span>{{ config('chapter.system_name') }}<small>JCI Carmona member portal</small></span></div><p class="eyebrow">For our chapter members</p><h2 id="portal-title">Your chapter work.<br>One connected space.</h2><p>JCISTEM helps members and officers stay organized, follow up on responsibilities, and keep chapter work moving.</p><a class="button button-white" href="{{ $loginUrl }}">Sign in to JCISTEM <span aria-hidden="true">→</span></a><small>Use the account provided by your chapter administrator.</small></div>
        <div class="portal-features"><article><span><x-icon name="clipboardCheck" /></span><div><h3>Projects &amp; tasks</h3><p>Follow project progress, check assignments, and coordinate your next steps.</p></div></article><article><span><x-icon name="calendar" /></span><div><h3>Calendar &amp; updates</h3><p>Keep track of chapter schedules and notifications in your workspace.</p></div></article><article><span><x-icon name="report" /></span><div><h3>Documents &amp; records</h3><p>Find the project files and chapter records available to your role.</p></div></article><article><span><x-icon name="receipt" /></span><div><h3>Membership dues</h3><p>Check your dues and payment records. Finance tools are available to authorized officers.</p></div></article></div>
    </div></section>

    <section class="section shell" id="join" aria-labelledby="join-title"><div class="section-heading join-heading"><p class="eyebrow">Start a conversation</p><h2 id="join-title">There's a place for<br><span>your next step.</span></h2><p>Interested in membership, a community initiative, or working together? Connect with the chapter to learn how you can take part.</p></div><div class="join-grid">
        <article><span class="join-number">01 / Become a member</span><h3>Grow with the chapter.</h3><p>Ask about membership, orientation, and the next opportunity to meet JCI Carmona.</p><a class="text-link" href="{{ config('chapter.facebook_url') }}" target="_blank" rel="noopener noreferrer">Connect on Facebook <span aria-hidden="true">↗</span></a></article>
        <article><span class="join-number">02 / Work together</span><h3>Bring an idea to life.</h3><p>Discuss a community need, a collaboration, or ways your organization could contribute.</p><a class="text-link" href="{{ config('chapter.facebook_url') }}" target="_blank" rel="noopener noreferrer">Talk to the chapter <span aria-hidden="true">↗</span></a></article>
        <article><span class="join-number">03 / Explore the network</span><h3>Find your connection.</h3><p>Use JCI Philippines' membership inquiry form to be referred to a local chapter.</p><a class="text-link" href="{{ config('chapter.membership_url') }}" target="_blank" rel="noopener noreferrer">Visit JCI Philippines <span aria-hidden="true">↗</span></a></article>
    </div></section>

    <section class="faq-section" id="questions" aria-labelledby="faq-title"><div class="shell faq-layout"><div class="section-heading"><p class="eyebrow">A little guidance</p><h2 id="faq-title">Good to know.</h2><p>A few answers to help you find your way.</p></div><div class="faq-list">
        <details><summary>What is JCI Carmona?<span aria-hidden="true">+</span></summary><p>JCI Carmona is a local chapter connected to JCI Philippines and Junior Chamber International. JCI provides opportunities for young people to develop leadership skills and create positive change.</p></details>
        <details><summary>How do I get involved?<span aria-hidden="true">+</span></summary><p>Contact <a href="{{ config('chapter.facebook_url') }}" target="_blank" rel="noopener noreferrer">JCI Carmona on Facebook ↗</a> to ask about membership, orientation, or collaboration. The chapter can explain current opportunities and requirements.</p></details>
        <details><summary>Who can sign in to JCISTEM?<span aria-hidden="true">+</span></summary><p>Members and officers with an active account provided by the chapter administrator can sign in. The tools and records you can access depend on your assigned role. Membership inquiries are handled separately from portal access.</p></details>
        <details><summary>I am a member. How do I access my account?<span aria-hidden="true">+</span></summary><p>Choose <a href="{{ $loginUrl }}">Member sign in</a> and enter your username or registered email and password. If you have forgotten your password, choose <a href="{{ route('password.request') }}">Forgot password?</a> on the sign-in page. If you do not have an account yet, contact your chapter administrator.</p></details>
    </div></div></section>
</main>
@include('shared.public-footer')
@include('shared.media-viewer')
</body>
</html>
