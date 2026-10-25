<article class="chapter-story-card">
    @if($story['image'])
        <a class="chapter-story-image media-open" data-media href="{{ asset('assets/images/community/'.$story['image']) }}" aria-label="View image for {{ $story['title'] }}"><img src="{{ asset('assets/images/community/'.$story['image']) }}" alt="{{ $story['title'] }}" width="600" height="400" loading="lazy" decoding="async"></a>
    @else
        <div class="chapter-story-art" aria-hidden="true"><x-icon :name="$story['icon'] ?? 'handshake'" /><span>{{ $story['kind'] }}</span></div>
    @endif
    <div class="chapter-story-copy">
        <p class="eyebrow">{{ $story['kind'] }} &middot; {{ $story['topic'] }}</p>
        <p class="chapter-story-date">{{ $story['date_label'] }}</p>
        <h3><a href="{{ route('public.story', $story['slug']) }}">{{ $story['title'] }}</a></h3>
        <p>{{ $story['summary'] }}</p>
        <a class="text-link" href="{{ route('public.story', $story['slug']) }}">Read the story <x-icon name="arrow" /></a>
    </div>
</article>
