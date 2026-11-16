<form method="POST" action="{{ route('notifications.open', $notificationId) }}" class="record-unread-form no-print">
    @csrf
    <button type="submit" class="record-unread-indicator" title="Open this update and mark it as read"><i aria-hidden="true"></i>{{ $label }}</button>
</form>
