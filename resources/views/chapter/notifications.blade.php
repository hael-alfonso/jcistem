@extends('layouts.chapter')
@section('title', 'Notifications')
@section('content')
@php
    $notificationGroups = $items->getCollection()->groupBy(fn ($item) => $item->created_at->isToday() ? 'Today' : ($item->created_at->isYesterday() ? 'Yesterday' : 'Earlier'));
@endphp
<div class="page-heading"><div><div class="eyebrow">WORKSPACE UPDATES</div><h1>Notifications</h1><p>Project decisions, assignments, reports, and finance updates in chronological order.</p></div>@if($unreadCount)<form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="btn secondary" type="submit"><x-icon name="check" /> Mark all read</button></form>@endif</div>
<div class="notification-summary"><span><strong>{{ $unreadCount }}</strong> unread</span><span><strong>{{ $items->total() }}</strong> {{ $filter === 'unread' ? 'unread updates' : 'updates' }}</span></div>
<nav class="notification-filters" aria-label="Notification filters"><a href="{{ route('notifications') }}" class="{{ $filter !== 'unread' ? 'selected' : '' }}" @if($filter !== 'unread') aria-current="page" @endif>All updates</a><a href="{{ route('notifications', ['filter' => 'unread']) }}" class="{{ $filter === 'unread' ? 'selected' : '' }}" @if($filter === 'unread') aria-current="page" @endif>Unread <span>{{ $unreadCount }}</span></a></nav>
<section class="panel notification-list" aria-label="Notification list">
    @forelse($notificationGroups as $period => $group)
        <h2 class="notification-period">{{ $period }}</h2>
        @foreach($group as $item)
            @php
                $notificationIcon = str_contains(strtolower($item->title.' '.$item->body), 'task') ? 'checklist' : (str_contains(strtolower($item->title.' '.$item->body), 'report') ? 'report' : (str_contains(strtolower($item->title.' '.$item->body), 'budget') || str_contains(strtolower($item->title.' '.$item->body), 'dues') ? 'wallet' : 'briefcase'));
            @endphp
            <form method="POST" action="{{ route('notifications.open', $item) }}" class="notification-entry {{ $item->read_at ? 'is-read' : 'is-unread' }}">@csrf<button type="submit" class="notification-action"><span class="notification-kind"><x-icon :name="$notificationIcon" /></span><span class="notification-copy"><strong>{{ $item->title }}</strong><span>{{ $item->body }}</span><small>{{ $item->created_at->diffForHumans() }} · {{ $item->created_at->format('M d, g:i A') }}</small></span><span class="notification-open" aria-label="Open notification" title="Open"><x-icon name="arrow" /></span></button></form>
        @endforeach
    @empty
        <div class="empty-state"><x-icon name="bell"/><h3>{{ $filter === 'unread' ? 'You are all caught up' : 'No notifications yet' }}</h3><p>{{ $filter === 'unread' ? 'New items that need your attention will appear here.' : 'Project and chapter updates will appear here.' }}</p></div>
    @endforelse
    @include('chapter.partials.pagination', ['items' => $items])
</section>
@endsection
