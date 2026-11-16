@forelse($notifications as $notification)
    <div class="topbar-notification-item {{ $notification->read_at ? 'is-read' : 'is-unread' }} {{ $notification->requires_open ? 'is-important' : '' }}">
        <form method="POST" action="{{ route('notifications.open', $notification) }}" class="topbar-notification-open-form">@csrf
            <button type="submit" class="topbar-notification-static"><span class="topbar-notification-title">{{ $notification->title }}@if($notification->requires_open && !$notification->read_at)<em>Important</em>@endif</span><span class="topbar-notification-body">{{ $notification->body }}</span><small>{{ $notification->created_at->diffForHumans() }}@if($notification->requires_open && !$notification->read_at) · Open to mark read @endif</small></button>
        </form>
    </div>
@empty
    <p class="topbar-notification-empty">You're all caught up. New updates will appear here.</p>
@endforelse
