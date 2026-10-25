@extends('layouts.public')
@section('title', 'Projects, events & activities')
@section('content')
<section class="chapter-page-intro"><div class="shell"><div class="chapter-intro-copy"><p class="eyebrow">JCI Carmona in action</p><h1>Our projects.<br><span>Our community.</span></h1><p>Explore community projects, youth events, chapter meetings, and volunteer activities.</p><a class="text-link" href="{{ route('public.involvement') }}">Find your way to take part <x-icon name="arrow" /></a></div></div></section>
<section class="section shell" aria-labelledby="activities-title">
    <div class="section-heading"><p class="eyebrow">The chapter journal</p><h2 id="activities-title">Projects, events &amp; activities</h2><p>Stories are grouped by year, with recent activities first. Each story explains JCI Carmona's role and links to the original update.</p></div>
    <p class="chapter-result-count">{{ $stories->count() }} published stories</p>
    @forelse($stories->groupBy(fn ($story) => substr($story['date'], 0, 4)) as $year => $yearStories)
        <section class="chapter-year" aria-labelledby="year-{{ $year }}">
            <h2 id="year-{{ $year }}">{{ $year }}</h2>
            <div class="chapter-story-grid">
                @foreach($yearStories as $story)
                    @include('shared.public-story-card', ['story' => $story])
                @endforeach
            </div>
        </section>
    @empty
        <p>Visit the chapter Facebook page for current announcements.</p>
    @endforelse
    <div class="chapter-discover"><div><h3>Looking for the next activity?</h3><p>Follow JCI Carmona for new announcements and participation details.</p></div><a class="button button-primary" href="{{ config('chapter.facebook_url') }}" target="_blank" rel="noopener noreferrer">Chapter updates <x-icon name="arrow" /></a></div>
</section>
@endsection
