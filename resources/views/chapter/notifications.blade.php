@extends('layouts.chapter')
@section('title','Notifications')
@section('content')
<div class="page-heading"><div><div class="eyebrow">STAY IN THE LOOP</div><h1>Notifications</h1><p>Project decisions, assignments, reports, and member dues updates.</p></div><form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="btn secondary">Mark all as read</button></form></div>
<section class="panel">@forelse($items as $item)<a class="notification-row {{ $item->read_at?'read':'' }}" href="{{ $item->url??route('notifications') }}"><span class="task-dot"></span><span class="row-main"><strong>{{ $item->title }}</strong><p>{{ $item->body }}</p><small>{{ $item->created_at->diffForHumans() }}</small></span>@unless($item->read_at)<span class="badge">Unread</span>@endunless</a>@empty<div class="empty-state"><h3>You're up to date.</h3><p>Updates from your chapter will appear here.</p></div>@endforelse@include('chapter.partials.pagination',['items'=>$items])</section>
@endsection
