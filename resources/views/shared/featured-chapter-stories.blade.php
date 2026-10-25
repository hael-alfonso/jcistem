@php
    $featuredSlugs = ['project-lapis', 'habi-at-kulay', 'project-musa', 'project-bloom', 'ngiting-maningning', 'bags2school'];
    $featuredStories = collect(config('chapter_stories'))->whereIn('slug', $featuredSlugs)->sortBy('date');
@endphp
<div class="chapter-story-grid">
@foreach($featuredStories as $story)
    @include('shared.public-story-card', ['story' => $story])
@endforeach
</div>
<div class="chapter-discover"><div><p class="eyebrow">14 July 2026 · Regional volunteer forum</p><h3>Every contribution matters.</h3><p>JCI Carmona shared how young volunteers can help vulnerable groups at the CALABARZON International Volunteer Year forum.</p></div><a class="button button-primary" href="{{ route('public.story', 'volunteer-year-forum') }}">Read the forum story</a></div>
<div class="chapter-discover"><div><h3>See more chapter activities.</h3><p>Browse chapter projects, meetings, and volunteer updates.</p></div><a class="button button-primary" href="{{ route('public.activities') }}">All projects &amp; activities <x-icon name="arrow" /></a></div>
