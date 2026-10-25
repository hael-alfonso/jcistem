@extends('layouts.public')
@section('title', $story['title'])
@section('description', $story['summary'])
@section('content')
<article class="section shell chapter-story-detail">
    <a class="text-link" href="{{ route('public.activities') }}">← All projects &amp; activities</a>
    <header><p class="eyebrow">{{ $story['topic'] }} · JCI Carmona</p><h1>{{ $story['title'] }}</h1><p>{{ $story['date_label'] }}</p><p class="lead">{{ $story['summary'] }}</p></header>
    <dl class="chapter-story-facts">
        <div><dt>Activity type</dt><dd>{{ $story['kind'] }}</dd></div>
        <div><dt>JCI Carmona's role</dt><dd>{{ $story['chapter_role'] }}</dd></div>
        @if($story['location'])<div><dt>Location</dt><dd>{{ $story['location'] }}</dd></div>@endif
    </dl>
    @if($story['image'])<figure><a data-media class="media-open" href="{{ asset('assets/images/community/'.$story['image']) }}" aria-label="View full image for {{ $story['title'] }}"><img src="{{ asset('assets/images/community/'.$story['image']) }}" alt="{{ $story['title'] }}" width="1200" height="800"></a><figcaption>Image: {{ $story['credit'] }}</figcaption></figure>@endif
    @if($story['slug'] === 'project-lapis') @include('shared.lapis-milestones') @endif
    <div class="chapter-story-reading"><h2>About this activity</h2><p>{{ $story['body'] }}</p><a class="text-link" href="{{ $story['source'] }}" target="_blank" rel="noopener noreferrer">Read the original update ↗</a><p class="chapter-source">Source: {{ $story['source_label'] }}</p><h2>Inspired to contribute?</h2><p>Tell the chapter about your interests, skills, or organization. Ask about current opportunities connected to this initiative and how you can help with future activities.</p><a class="button button-primary" href="{{ route('contact', ['category' => 'project', 'subject' => 'Inquiry about '.$story['title']]) }}">Ask about this activity <x-icon name="send" /></a></div>
</article>
@endsection
