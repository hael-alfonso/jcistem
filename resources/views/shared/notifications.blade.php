@extends('layouts.workspace')
@section('content')
<div class="page-head"><div class="page-copy"><div class="eyebrow">{{ strtoupper(\App\Support\WorkspaceNav::label(auth()->user()->role)) }}</div><h1>Notifications</h1><p>Alerts for this workspace.</p></div>
<div class="page-actions"><button class="btn btn-secondary" type="button" id="markAllRead"><x-icon name="check" /> Mark all read</button></div></div>
<x-card title="Inbox" subtitle="Open the related record instead of duplicating details.">
    <div class="notifications">
        @foreach ($items as $n)
            <a class="notification-row {{ !empty($n['read']) ? 'read' : '' }}" href="{{ $n['href'] }}">
                <span class="notification-dot {{ strtolower($n['type']) }}"></span>
                <div><strong>{{ $n['title'] }}</strong><span>{{ $n['time'] }}</span></div>
                <em><x-icon name="chevron" /></em>
            </a>
        @endforeach
    </div>
</x-card>
@endsection
