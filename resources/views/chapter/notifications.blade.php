@extends('layouts.chapter')
@section('title', 'Notifications')
@section('content')
@php
    $importantItems = $items->getCollection()->where('requires_open', true);
    $routineGroups = $items->getCollection()->where('requires_open', false)->groupBy(fn ($item) => $item->created_at->isToday() ? 'Today' : ($item->created_at->isYesterday() ? 'Yesterday' : 'Earlier'));
@endphp
<div class="page-heading">
    <div><div class="eyebrow">WORKSPACE UPDATES</div><h1>Notifications</h1><p>Review important decisions and follow your chapter updates.</p></div>
    @if($routineUnreadCount)
        <form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="btn secondary" type="submit"><x-icon name="check" /> Mark routine read</button></form>
    @endif
</div>
<div class="notification-summary"><span><strong>{{ $unreadCount }}</strong> unread</span><span class="notification-important-count"><strong>{{ $importantCount }}</strong> important to open</span></div>
<nav class="notification-filters" aria-label="Notification filters">
    <a href="{{ route('notifications') }}" class="{{ !$filter ? 'selected' : '' }}" @if(!$filter) aria-current="page" @endif>All updates</a>
    <a href="{{ route('notifications', ['filter' => 'important']) }}" class="{{ $filter === 'important' ? 'selected' : '' }}" @if($filter === 'important') aria-current="page" @endif>Important <span>{{ $importantCount }}</span></a>
    <a href="{{ route('notifications', ['filter' => 'unread']) }}" class="{{ $filter === 'unread' ? 'selected' : '' }}" @if($filter === 'unread') aria-current="page" @endif>Unread <span>{{ $unreadCount }}</span></a>
</nav>
@if($importantItems->isNotEmpty())
<section class="panel notification-important-panel" aria-labelledby="important-notifications-heading">
    <div class="notification-section-heading"><div><h2 id="important-notifications-heading">Important updates</h2><p>Open each update to mark it as read and view its related record.</p></div><x-icon name="shieldCheck" /></div>
    <div class="notification-table-wrap"><table class="notification-important-table"><thead><tr><th scope="col">Update</th><th scope="col">Received</th><th scope="col">Action</th></tr></thead><tbody>
        @foreach($importantItems as $item)
        <tr class="{{ $item->read_at ? 'is-read' : 'is-unread' }}"><td data-label="Update"><strong>{{ $item->title }}</strong><span>{{ $item->body }}</span>@if(!$item->read_at)<small>Needs review</small>@endif</td><td data-label="Received"><time datetime="{{ $item->created_at->toIso8601String() }}">{{ $item->created_at->format('M d, Y') }}<small>{{ $item->created_at->format('g:i A') }}</small></time></td><td data-label="Action"><form method="POST" action="{{ route('notifications.open', $item) }}">@csrf<button class="btn secondary" type="submit">Open <x-icon name="arrow" /></button></form></td></tr>
        @endforeach
    </tbody></table></div>
</section>
@endif
@if($filter !== 'important' && $routineGroups->isNotEmpty())
<section class="panel notification-list" aria-label="Routine notifications">
    <div class="notification-section-heading"><div><h2>Routine updates</h2><p>Reminders and activity from your workspace.</p></div></div>
    @foreach($routineGroups as $period => $group)
        <h3 class="notification-period">{{ $period }}</h3>
        @foreach($group as $item)
            @php
                $notificationIcon = str_contains(strtolower($item->title.' '.$item->body), 'task') ? 'checklist' : (str_contains(strtolower($item->title.' '.$item->body), 'report') ? 'report' : (str_contains(strtolower($item->title.' '.$item->body), 'budget') || str_contains(strtolower($item->title.' '.$item->body), 'dues') ? 'wallet' : 'briefcase'));
            @endphp
            <form method="POST" action="{{ route('notifications.open', $item) }}" class="notification-entry {{ $item->read_at ? 'is-read' : 'is-unread' }}">@csrf
                <button type="submit" class="notification-action">
                    <span class="notification-kind"><x-icon :name="$notificationIcon" /></span>
                    <span class="notification-copy"><strong>{{ $item->title }}</strong><span>{{ $item->body }}</span><small>{{ $item->created_at->diffForHumans() }} · {{ $item->created_at->format('M d, g:i A') }}</small></span>
                    <span class="notification-open" aria-label="Open notification" title="Open"><x-icon name="arrow" /></span>
                </button>
            </form>
        @endforeach
    @endforeach
</section>
@endif
@if($items->isEmpty())
    <section class="panel notification-list"><div class="empty-state"><x-icon name="bell"/><h3>{{ $filter === 'important' ? 'No important updates' : ($filter === 'unread' ? 'You are all caught up' : 'No notifications yet') }}</h3><p>Updates for your workspace will appear here.</p></div></section>
@endif
@include('chapter.partials.pagination', ['items' => $items])
@endsection
