@extends('layouts.public')
@section('title', 'Community partners')
@section('description', 'Meet the groups named in JCI Carmona projects and learn how to start a community partnership.')
@section('content')
<section class="chapter-page-intro"><div class="shell"><div class="chapter-intro-copy"><p class="eyebrow">Working together in Carmona</p><h1>More people.<br><span>More ways to help.</span></h1><p>Schools, community groups, local government, and fellow JCI chapters help turn ideas into action.</p><a class="button button-primary" href="{{ route('contact', ['category' => 'partnership']) }}">Talk about a partnership <x-icon name="arrow" /></a></div></div></section>
<section class="section shell"><div class="section-heading"><h2>Community collaborators</h2><p>Meet the groups involved in the activities on this website. Each entry explains the connection, including project collaboration or hosting an event attended by JCI Carmona.</p></div><div class="chapter-partner-directory">
@foreach(config('chapter_partners') as $partner)
<article><div class="chapter-partner-mark">@if($partner['image'])<img src="{{ asset('assets/images/partners/'.$partner['image']) }}" width="90" height="90" alt="{{ $partner['name'] }} logo" loading="lazy" decoding="async">@else<x-icon :name="$partner['icon']" />@endif</div><p class="eyebrow">{{ $partner['activity'] }}</p><h3>{{ $partner['name'] }}</h3><p>{{ $partner['role'] }}</p><a class="text-link" href="{{ $partner['source'] }}" target="_blank" rel="noopener noreferrer">Read the activity source ↗</a></article>
@endforeach
</div><div class="chapter-discover"><div><h3>Have an idea for Carmona?</h3><p>Tell us who you want to help, what your group can offer, and when you hope to start.</p></div><a class="button button-primary" href="{{ route('contact', ['category' => 'partnership']) }}">Send your idea <x-icon name="send" /></a></div>

</section>
@endsection
