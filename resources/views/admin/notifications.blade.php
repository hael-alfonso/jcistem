@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Notifications</h1>
        <p>Project, approval, task, LOI, financial, report, member and system alerts.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-secondary" type="button" id="markAllRead"><x-icon name="check" /> Mark all read</button>
    </div>
</div>

<x-card title="Notification inbox" subtitle="Notifications point users to the related record instead of duplicating its details.">
    <div class="notifications" id="notificationList">
        @foreach ($items as $n)
            <a class="notification-row {{ $n['read'] ? 'read' : '' }}" href="{{ $n['href'] }}">
                <span class="notification-dot {{ strtolower($n['type']) }}"></span>
                <div>
                    <strong>{{ $n['title'] }}</strong>
                    <span>{{ $n['time'] }}</span>
                </div>
                <em><x-icon name="chevron" /></em>
            </a>
        @endforeach
    </div>
</x-card>
@endsection
