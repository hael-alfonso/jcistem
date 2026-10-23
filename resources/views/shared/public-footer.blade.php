@php($footerRoot = request()->routeIs('contact*', 'scoped.contact*') ? url('/') : '')
<footer class="site-footer" id="footer" aria-label="JCI Carmona footer">
    <div class="shell footer-main">
        <div class="footer-brand">
            @include('shared.public-brand', ['homeUrl' => '#top'])
            <h2>Stay connected.</h2>
            <p>Follow JCI Carmona for chapter updates, community stories, and ways to get involved.</p>
            <a class="button button-primary" href="{{ config('chapter.facebook_url') }}" target="_blank" rel="noopener noreferrer">Follow JCI Carmona <span aria-hidden="true">↗</span></a>
            <span class="footer-location"><x-icon name="globe" />{{ config('chapter.location') }}</span>
        </div>
        <div class="footer-content">
            <div class="footer-links">
                <nav aria-label="Footer menu">
                    <h2>Menu</h2>
                    <a href="{{ $footerRoot }}#top">Home</a>
                    <a href="{{ $footerRoot }}#about">About us</a>
                    <a href="{{ $footerRoot }}#opportunities">What we do</a>
                    <a href="{{ $footerRoot }}#partners">Partners</a>
                    <a href="{{ $footerRoot }}#impact">News &amp; projects</a>
                    <a href="{{ $footerRoot }}#media">Media</a>
                    <a href="{{ route('contact') }}">Contact us</a>
                </nav>
                <nav aria-label="Connect with JCI">
                    <h2>Connect</h2>
                    <a href="{{ config('chapter.facebook_url') }}" target="_blank" rel="noopener noreferrer">JCI Carmona <span aria-hidden="true">↗</span></a>
                    <a href="{{ config('chapter.instagram_url') }}" target="_blank" rel="noopener noreferrer">Instagram <span aria-hidden="true">↗</span></a>
                    <a href="{{ config('chapter.national_url') }}" target="_blank" rel="noopener noreferrer">JCI Philippines <span aria-hidden="true">↗</span></a>
                    <a href="{{ config('chapter.international_url') }}" target="_blank" rel="noopener noreferrer">JCI Worldwide <span aria-hidden="true">↗</span></a>
                    <a href="{{ config('chapter.membership_url') }}" target="_blank" rel="noopener noreferrer">Membership inquiries <span aria-hidden="true">↗</span></a>
                </nav>
                <nav aria-label="Member resources">
                    <h2>Resources</h2>
                    <a href="{{ route('login') }}">Member sign in</a>
                    <a href="{{ route('password.request') }}">Account recovery</a>
                    <a href="{{ $footerRoot }}#portal">About JCISTEM</a>
                    <a href="{{ $footerRoot }}#questions">Common questions</a>
                </nav>
            </div>
            <div class="footer-connect">
                <div>
                    <h2>Follow us</h2>
                    <a class="footer-social" href="{{ config('chapter.facebook_url') }}" target="_blank" rel="noopener noreferrer">
                        <span class="facebook-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" focusable="false"><path d="M24 12a12 12 0 1 0-13.875 11.854v-8.385H7.078V12h3.047V9.356c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953h-1.513c-1.49 0-1.956.925-1.956 1.875V12h3.328l-.532 3.469h-2.796v8.385A12.003 12.003 0 0 0 24 12Z"/></svg></span>
                        <span>JCI Carmona on Facebook <span aria-hidden="true">↗</span></span>
                    </a>
                    <a class="footer-social" href="{{ config('chapter.instagram_url') }}" target="_blank" rel="noopener noreferrer"><span class="facebook-icon"><x-icon name="instagram" /></span><span>@jcicarmona <span aria-hidden="true">↗</span></span></a>
                </div>
                <div class="footer-portal">
                    <h2>Your member portal</h2>
                    <p>Projects, schedules, and chapter records in one place.</p>
                    <a class="text-link" href="{{ route('login') }}">Open JCISTEM <span aria-hidden="true">↗</span></a>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; {{ now()->year }} {{ config('chapter.name') }}. JCISTEM member portal.</span>
                <a href="#top">Back to top <span aria-hidden="true">↑</span></a>
            </div>
        </div>
    </div>
</footer>
