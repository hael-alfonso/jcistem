@extends('layouts.chapter')
@section('title', 'Notifications')
@section('content')
<div class="page-heading">
    <div><div class="eyebrow">STAY IN THE LOOP</div><h1>Notifications</h1><p>Project decisions, assignments, reports, and dues updates in one place.</p></div>
    @if($unreadCount)
        <form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="btn secondary" type="submit">Mark all as read</button></form>
    @endif
</div>
<nav class="notification-filters" aria-label="Notification filters">
    <a href="{{ route('notifications') }}" class="{{ $filter !== 'unread' ? 'selected' : '' }}" @if($filter !== 'unread') aria-current="page" @endif>All</a>
    <a href="{{ route('notifications', ['filter' => 'unread']) }}" class="{{ $filter === 'unread' ? 'selected' : '' }}" @if($filter === 'unread') aria-current="page" @endif>Unread <span>{{ $unreadCount }}</span></a>
</nav>
<section class="panel notification-list" aria-label="Notification list">
    @forelse($items as $item)
        <form method="POST" action="{{ route('notifications.open', $item) }}" class="notification-entry {{ $item->read_at ? 'is-read' : 'is-unread' }}">
            @csrf
            <button type="submit" class="notification-action">
                <span class="notification-status" aria-hidden="true"></span>
                <span class="notification-copy"><strong>{{ $item->title }}</strong><span>{{ $item->body }}</span><small>{{ $item->created_at->diffForHumans() }}</small></span>
                <span class="notification-open">Open <span aria-hidden="true">&rarr;</span></span>
            </button>
        </form>
    @empty
        <div class="empty-state"><h3>{{ $filter === 'unread' ? 'All caught up.' : 'No notifications yet.' }}</h3><p>{{ $filter === 'unread' ? 'New updates will appear here when they arrive.' : 'Chapter updates will appear here.' }}</p></div>
    @endforelse
    @include('chapter.partials.pagination', ['items' => $items])
</section>
@endsection
