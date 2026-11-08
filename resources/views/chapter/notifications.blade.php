@extends('layouts.chapter')
@section('title', 'Notifications')
@section('content')
@php
    $importantItems = $items->getCollection()->where('requires_open', true);
    $routineGroups = $items->getCollection()->where('requires_open', false)->groupBy(fn ($item) => $item->created_at->isToday() ? 'Today' : ($item->created_at->isYesterday() ? 'Yesterday' : 'Earlier'));
@endphp
<div class="page-heading">
    <div><div class="eyebrow">WORKSPACE UPDATES</div><h1>Notifications</h1><p>Review decisions and keep up with chapter activity.</p></div>
    @if($unreadCount)
        <form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="btn secondary notification-mark-all {{ $importantCount ? 'caution' : '' }}" type="submit" aria-label="Mark all notifications as read" title="Mark all notifications as read" @if($importantCount) data-confirm="Mark all notifications as read, including {{ $importantCount }} important {{ \Illuminate\Support\Str::plural('update', $importantCount) }}? You can still review them later." @endif><x-icon name="check" /></button></form>
    @endif
</div>
<nav class="notification-filters" aria-label="Notification filters">
    <a href="{{ route('notifications') }}" class="{{ !$filter ? 'selected' : '' }}" @if(!$filter) aria-current="page" @endif>All updates</a>
    <a href="{{ route('notifications', ['filter' => 'important']) }}" class="{{ $filter === 'important' ? 'selected' : '' }}" @if($filter === 'important') aria-current="page" @endif>Important <span>{{ $importantCount }}</span></a>
    <a href="{{ route('notifications', ['filter' => 'unread']) }}" class="{{ $filter === 'unread' ? 'selected' : '' }}" @if($filter === 'unread') aria-current="page" @endif>Unread <span>{{ $unreadCount }}</span></a>
</nav>
@if($importantItems->isNotEmpty())
<section class="panel notification-important-panel" aria-labelledby="important-notifications-heading">
    <div class="notification-section-heading"><div><h2 id="important-notifications-heading">Important updates</h2><p>Review project decisions and notices.</p></div><x-icon name="shieldCheck" /></div>
    <div class="notification-table-wrap"><table class="notification-important-table"><thead><tr><th scope="col">Update</th><th scope="col">Received</th><th scope="col">Action</th></tr></thead><tbody>
        @foreach($importantItems as $item)
        <tr class="{{ $item->read_at ? 'is-read' : 'is-unread' }}">
            <td data-label="Update"><strong>{{ $item->title }}</strong><span>{{ $item->body }}</span>@if(!$item->read_at)<small>{{ $item->hasDestination() ? 'Needs review' : 'Information' }}</small>@endif</td>
            <td data-label="Received"><time datetime="{{ $item->created_at->toIso8601String() }}">{{ $item->created_at->format('M d, Y') }}<small>{{ $item->created_at->format('g:i A') }}</small></time></td>
            <td data-label="Action"><div class="notification-actions">
                @if($item->hasDestination())
                    <form method="POST" action="{{ route('notifications.open', $item) }}">@csrf<button class="notification-icon-action" type="submit" aria-label="Open notification: {{ $item->title }}" title="Open notification"><x-icon name="eye" /></button></form>
                @elseif(!$item->read_at)
                    <form method="POST" action="{{ route('notifications.open', $item) }}">@csrf<button class="notification-icon-action" type="submit" aria-label="Mark notification as read: {{ $item->title }}" title="Mark as read"><x-icon name="check" /></button></form>
                @endif
                <form method="POST" action="{{ route('notifications.delete', $item) }}">@csrf @method('DELETE')<button class="notification-delete" type="submit" value="delete" aria-label="Remove notification: {{ $item->title }}" title="Remove notification" data-confirm="{{ !$item->read_at ? ($item->hasDestination() ? 'Remove this important unread notification? You can no longer open it here.' : 'Remove this important unread notification?') : 'Remove this notification?' }}"><x-icon name="trash" /></button></form>
            </div></td>
        </tr>
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
            <div class="notification-entry {{ $item->read_at ? 'is-read' : 'is-unread' }}">
                    <span class="notification-kind"><x-icon :name="$notificationIcon" /></span>
                    <span class="notification-copy"><strong>{{ $item->title }}</strong><span>{{ $item->body }}</span><small>{{ $item->created_at->diffForHumans() }} · {{ $item->created_at->format('M d, g:i A') }}</small></span>
                <div class="notification-entry-actions">
                @if($item->hasDestination())
                    <form method="POST" action="{{ route('notifications.open', $item) }}">@csrf<button class="notification-icon-action" type="submit" aria-label="Open notification: {{ $item->title }}" title="Open notification"><x-icon name="eye" /></button></form>
                @elseif(!$item->read_at)
                    <form method="POST" action="{{ route('notifications.open', $item) }}">@csrf<button class="notification-icon-action" type="submit" aria-label="Mark notification as read: {{ $item->title }}" title="Mark as read"><x-icon name="check" /></button></form>
                @endif
                <form method="POST" action="{{ route('notifications.delete', $item) }}" class="notification-delete-form">@csrf @method('DELETE')<button class="notification-delete" type="submit" value="delete" aria-label="Remove notification: {{ $item->title }}" title="Remove notification" data-confirm="Remove this notification?"><x-icon name="trash" /></button></form>
                </div>
            </div>
        @endforeach
    @endforeach
</section>
@endif
@if($items->isEmpty())
    <section class="panel notification-list"><div class="empty-state"><x-icon name="bell"/><h3>{{ $filter === 'important' ? 'No important updates' : ($filter === 'unread' ? 'You are all caught up' : 'No notifications yet') }}</h3><p>Updates for your workspace will appear here.</p></div></section>
@endif
@include('chapter.partials.pagination', ['items' => $items])
@endsection
