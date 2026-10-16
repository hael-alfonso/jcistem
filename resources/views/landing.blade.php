<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111936">
    <meta name="description" content="JCI Carmona is a youth-led nonprofit organization developing leaders and creating positive change through community action in Carmona City.">
    <title>JCI Carmona | Developing Leaders for a Changing World</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/brand/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}">
</head>
<body>
@php
    $loginUrl = request()->routeIs('scoped.*')
        ? route('scoped.login', ['workspace' => request()->route('workspace')])
        : route('login');
@endphp

<a class="skip-link" href="#main">Skip to content</a>

<div class="utility-bar">
    <div class="shell utility-inner">
        <span><i aria-hidden="true"></i> A local organization of JCI Philippines</span>
        <span>Carmona City, Cavite</span>
    </div>
</div>

<header class="site-header">
    <div class="shell header-inner">
        <a class="chapter-logo" href="#top" aria-label="JCI Carmona home">
            <img src="https://jcieurope.cc/wp-content/smush-webp/2025/12/New_JCI_Logo_Primary-1-scaled.png.webp" alt="Junior Chamber International">
            <span aria-hidden="true"></span>
            <strong>CARMONA<small>JCI PHILIPPINES</small></strong>
        </a>

        <nav class="desktop-nav" aria-label="Main navigation">
            <a href="#about">Who we are</a>
            <a href="#opportunities">What we do</a>
            <a href="#impact">Our impact</a>
            <a href="#join">Get involved</a>
        </nav>

        <a class="portal-button" href="{{ $loginUrl }}">Member portal <span aria-hidden="true">&#8599;</span></a>

        <details class="mobile-menu">
            <summary>Menu</summary>
            <nav aria-label="Mobile navigation">
                <a href="#about">Who we are</a>
                <a href="#opportunities">What we do</a>
                <a href="#impact">Our impact</a>
                <a href="#join">Get involved</a>
                <a href="{{ $loginUrl }}">Member portal</a>
            </nav>
        </details>
    </div>
</header>

<main id="main">
    <section class="hero" id="top">
        <div class="shell hero-layout">
            <div class="hero-copy">
                <p class="eyebrow eyebrow-light"><span></span> JCI CARMONA 2026</p>
                <h1>Developing leaders for a <em>changing Carmona.</em></h1>
                <p>Young active citizens turn ideas into action through leadership development, community partnerships, and projects that create lasting local impact.</p>
                <div class="hero-actions">
                    <a class="button button-teal" href="#join">Become part of JCI Carmona <span aria-hidden="true">&#8599;</span></a>
                    <a class="text-link text-link-light" href="#opportunities">Explore what we do <span aria-hidden="true">&#8595;</span></a>
                </div>
                <div class="hero-tags" aria-label="Chapter priorities">
                    <span>Lead</span><i></i><span>Connect</span><i></i><span>Serve</span><i></i><span>Create impact</span>
                </div>
            </div>

            <div class="hero-image">
                <img src="https://mb.com.ph/manilabulletin/uploads/images/2026/02/02/76134.webp" alt="JCI Carmona volunteers supporting learners during Project LAPIS" fetchpriority="high">
                <div class="image-label"><small>COMMUNITY IMPACT</small><strong>Project LAPIS</strong><span>Carmona City | 2026</span></div>
            </div>
        </div>
    </section>

    <section class="feature-rail shell" aria-label="JCI Carmona highlights">
        <a class="feature-card feature-photo" href="https://mb.com.ph/2026/02/02/carmona-lgu-rolls-out-literacy-project-for-out-of-school-children-youth" target="_blank" rel="noopener noreferrer">
            <img src="https://mb.com.ph/manilabulletin/uploads/images/2026/02/02/76132.webp" alt="Project LAPIS small group learning session" loading="lazy">
            <span class="feature-overlay"></span>
            <span class="feature-content"><small>2026 EDUCATION INITIATIVE</small><strong>Learning support where it matters</strong><b>Read the story &#8599;</b></span>
        </a>
        <a class="feature-card feature-photo" href="https://pnvsca.gov.ph/?p=9684" target="_blank" rel="noopener noreferrer">
            <img src="https://pnvsca.gov.ph/wp-content/uploads/2026/07/Forum-Title-with-Mayor-and-Host-1024x483.png" alt="Carmona hosts the CALABARZON International Volunteer Year forum" loading="lazy">
            <span class="feature-overlay"></span>
            <span class="feature-content"><small>REGIONAL VOLUNTEERISM</small><strong>Every contribution matters</strong><b>View the forum &#8599;</b></span>
        </a>
        <a class="feature-card feature-portal" href="{{ $loginUrl }}">
            <img src="{{ asset('assets/images/brand/logo.png') }}" alt="" aria-hidden="true">
            <span><small>FOR CHAPTER MEMBERS</small><strong>JCISTEM</strong><p>Projects, members, records, and chapter work in one secure workspace.</p><b>Open member portal &#8594;</b></span>
        </a>
    </section>

    <section class="purpose section shell" id="about">
        <div class="section-heading purpose-heading">
            <p class="eyebrow">WHO WE ARE</p>
            <h2>Built for local leaders.<br><em>Driven by purpose.</em></h2>
        </div>
        <div class="purpose-copy">
            <p class="lead">JCI Carmona is a youth-led nonprofit organization creating opportunities for young people to grow while helping build a stronger Carmona City.</p>
            <p>Connected to JCI Philippines and a worldwide movement, the chapter develops leadership through real projects, practical responsibility, collaboration, and service.</p>
            <div class="network-row">
                <div><strong>JCI Carmona</strong><span>Local action</span></div>
                <div><strong>JCI Philippines</strong><span>National network</span></div>
                <div><strong>JCI</strong><span>Global movement</span></div>
            </div>
        </div>
    </section>

    <section class="purpose-gallery shell" aria-label="JCI Carmona community work">
        <figure class="gallery-tall">
            <img src="https://pnvsca.gov.ph/wp-content/uploads/2026/07/Group_IVY-4A_Forum-1024x856.png" alt="Participants in the 2026 CALABARZON volunteerism forum" loading="lazy">
            <figcaption><span>01</span><strong>Young leaders in regional action</strong></figcaption>
        </figure>
        <figure>
            <img src="https://pnvsca.gov.ph/wp-content/uploads/2026/07/Workshop-1024x407.png" alt="Interactive workshop during the 2026 volunteerism forum" loading="lazy">
            <figcaption><span>02</span><strong>Ideas shaped through collaboration</strong></figcaption>
        </figure>
        <div class="purpose-statement">
            <p class="eyebrow eyebrow-light">OUR SHARED DIRECTION</p>
            <blockquote>Leadership becomes meaningful when it moves people to act together.</blockquote>
        </div>
    </section>

    <section class="opportunities section" id="opportunities">
        <div class="shell">
            <div class="section-heading split-heading">
                <div>
                    <p class="eyebrow">FOUR AREAS OF OPPORTUNITY</p>
                    <h2>Grow through experience.<br><em>Lead through action.</em></h2>
                </div>
                <p>JCI develops young leaders through four connected areas that turn personal growth into meaningful community results.</p>
            </div>

            <div class="opportunity-grid">
                <article class="opportunity-card opportunity-blue">
                    <span class="card-number">01</span>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19V9m8 10V5m8 14v-7M2 19h20"/></svg>
                    <h3>Business &amp;<br>Entrepreneurship</h3>
                    <p>Turn ideas into sustainable ventures through innovation, enterprise, and purposeful connections.</p>
                </article>
                <article class="opportunity-card opportunity-navy">
                    <span class="card-number">02</span>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 3.8 5.5 3.8 9S14.5 18.5 12 21c-2.5-2.5-3.8-5.5-3.8-9S9.5 5.5 12 3Z"/></svg>
                    <h3>International<br>Cooperation</h3>
                    <p>Connect Carmona's young leaders with people, cultures, and ideas beyond local borders.</p>
                </article>
                <article class="opportunity-card opportunity-cyan">
                    <span class="card-number">03</span>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 8.5 9.5 2 13l6.5 3.5L12 23l3.5-6.5L22 13l-6.5-3.5L12 3Z"/></svg>
                    <h3>Individual<br>Development</h3>
                    <p>Build communication, confidence, and practical leadership through real responsibility.</p>
                </article>
                <article class="opportunity-card opportunity-teal">
                    <span class="card-number">04</span>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20v-7a8 8 0 0 1 16 0v7M8 20v-5h8v5M9 7.5 12 4l3 3.5"/></svg>
                    <h3>Community<br>Impact</h3>
                    <p>Work with partners on inclusive and sustainable responses to real community needs.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="chapter-numbers" aria-label="2026 chapter snapshot">
        <div class="shell numbers-grid">
            <div><p class="eyebrow eyebrow-light">2026 AT A GLANCE</p><strong>Local action, wider reach.</strong></div>
            <div><b>04</b><span>Areas of<br>opportunity</span></div>
            <div><b>~400</b><span>Regional forum<br>participants</span></div>
            <div><b>05</b><span>CALABARZON provinces<br>represented</span></div>
        </div>
    </section>

    <section class="impact section" id="impact">
        <div class="shell">
            <div class="section-heading split-heading impact-heading">
                <div>
                    <p class="eyebrow">IMPACT IN ACTION</p>
                    <h2>For Carmona.<br><em>With Carmona.</em></h2>
                </div>
                <p>The chapter works alongside public institutions, youth groups, and community partners to turn shared concerns into organized action.</p>
            </div>

            <div class="story-grid">
                <article class="story-card story-featured">
                    <a class="story-image" href="https://mb.com.ph/2026/02/02/carmona-lgu-rolls-out-literacy-project-for-out-of-school-children-youth" target="_blank" rel="noopener noreferrer">
                        <img src="https://mb.com.ph/manilabulletin/uploads/images/2026/02/02/76132.webp" alt="A literacy learning session under Project LAPIS" loading="lazy">
                    </a>
                    <div class="story-copy">
                        <span>EDUCATION | SDG 4 | 2026</span>
                        <h3>Project LAPIS</h3>
                        <p>JCI Carmona joined the City of Carmona and local youth organizations in a community-based literacy initiative supporting struggling readers and out-of-school children and youth.</p>
                        <a href="https://mb.com.ph/2026/02/02/carmona-lgu-rolls-out-literacy-project-for-out-of-school-children-youth" target="_blank" rel="noopener noreferrer">Read the project story &#8599;</a>
                    </div>
                </article>

                <article class="story-card">
                    <a class="story-image" href="https://pnvsca.gov.ph/?p=9684" target="_blank" rel="noopener noreferrer">
                        <img src="https://pnvsca.gov.ph/wp-content/uploads/2026/07/Forum-Title-with-Mayor-and-Host-1024x483.png" alt="Regional International Volunteer Year forum hosted by Carmona" loading="lazy">
                    </a>
                    <div class="story-copy">
                        <span>VOLUNTEERISM | 2026</span>
                        <h3>Every Contribution Matters</h3>
                        <p>At the CALABARZON forum, JCI Carmona shared how youth-led mobilization can support vulnerable groups and strengthen community action.</p>
                        <a href="https://pnvsca.gov.ph/?p=9684" target="_blank" rel="noopener noreferrer">View the forum story &#8599;</a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="creed">
        <div class="shell creed-inner">
            <p class="eyebrow eyebrow-light">THE JCI CREED</p>
            <blockquote>&ldquo;Service to humanity is the best work of life.&rdquo;</blockquote>
            <p>Leadership is practiced through service: listening well, acting responsibly, and bringing people together for a greater purpose.</p>
            <a class="text-link text-link-light" href="https://jci.cc/about/" target="_blank" rel="noopener noreferrer">Learn about JCI worldwide <span aria-hidden="true">&#8599;</span></a>
        </div>
    </section>

    <section class="join section" id="join">
        <div class="shell join-panel">
            <div>
                <p class="eyebrow eyebrow-light">READY TO LEAD?</p>
                <h2>Your next chapter<br>starts in Carmona.</h2>
                <p>Grow through experience, find your community, and help create positive change where it matters most.</p>
                <div class="join-actions">
                    <a class="button button-white" href="https://jciphilippines.com/get-involved/join-us/" target="_blank" rel="noopener noreferrer">Become a member <span aria-hidden="true">&#8599;</span></a>
                    <a class="text-link text-link-light" href="{{ $loginUrl }}">Already a member? Open JCISTEM <span aria-hidden="true">&#8594;</span></a>
                </div>
            </div>
            <div class="join-list">
                <div><span>01</span><p><strong>Develop practical leadership</strong>Learn by planning, deciding, collaborating, and leading real initiatives.</p></div>
                <div><span>02</span><p><strong>Build meaningful connections</strong>Meet people who share your drive to grow and contribute.</p></div>
                <div><span>03</span><p><strong>Create visible impact</strong>Help shape projects that respond to real needs in Carmona.</p></div>
            </div>
        </div>
    </section>

    <section class="social-strip" aria-labelledby="social-title">
        <div class="shell social-inner">
            <div><p class="eyebrow">STAY CONNECTED</p><h2 id="social-title">Follow JCI Carmona.</h2></div>
            <p>See chapter activities, community partnerships, announcements, and opportunities to participate.</p>
            <div class="social-actions">
                <a href="https://www.facebook.com/jcicarmonaofficial" target="_blank" rel="noopener noreferrer"><span>f</span><strong>Official Facebook</strong><b>&#8599;</b></a>
                <a href="https://m.me/jcicarmonaofficial" target="_blank" rel="noopener noreferrer"><span>m</span><strong>Messenger</strong><b>&#8599;</b></a>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="shell footer-main">
        <div class="footer-brand">
            <a class="chapter-logo" href="#top" aria-label="Back to top">
                <img src="https://jcieurope.cc/wp-content/smush-webp/2025/12/New_JCI_Logo_Primary-1-scaled.png.webp" alt="Junior Chamber International">
                <span aria-hidden="true"></span>
                <strong>CARMONA<small>JCI PHILIPPINES</small></strong>
            </a>
            <p>Young active citizens creating positive change in Carmona City, Cavite.</p>
        </div>
        <nav class="footer-links" aria-label="Explore"><strong>Explore</strong><a href="#about">Who we are</a><a href="#opportunities">What we do</a><a href="#impact">Our impact</a><a href="#join">Get involved</a></nav>
        <nav class="footer-links" aria-label="Connect"><strong>Connect</strong><a href="https://www.facebook.com/jcicarmonaofficial" target="_blank" rel="noopener noreferrer">Official Facebook &#8599;</a><a href="https://jciphilippines.com/" target="_blank" rel="noopener noreferrer">JCI Philippines &#8599;</a><a href="https://jci.cc/" target="_blank" rel="noopener noreferrer">JCI Worldwide &#8599;</a><a href="{{ $loginUrl }}">JCISTEM member portal</a></nav>
    </div>
    <div class="shell footer-bottom"><span>&copy; {{ date('Y') }} JCI Carmona</span><span>Leadership | Fellowship | Service</span></div>
</footer>
</body>
</html>
