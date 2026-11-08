@extends('layouts.chapter')
@section('title', 'Activity log')
@section('content')
<div class="page-heading"><div><div class="eyebrow">CHAPTER HISTORY</div><h1>Activity log</h1><p>A clear history of chapter work, decisions, and updates.</p></div></div>
@php $hasActivityDates = request()->filled('from') || request()->filled('to'); @endphp
<form class="list-filters collection-toolbar activity-filter-toolbar" method="GET" action="{{ route('activity-log') }}">
        <label for="activity-search"><span>Find activity</span><input id="activity-search" name="q" value="{{ $search }}" placeholder="Person, record, or action"></label>
        <label for="activity-action"><span>Action</span><select id="activity-action" name="action">
            <option value="">All actions</option>
            @foreach($actions as $option)<option value="{{ $option }}" @selected($action === $option)>{{ \Illuminate\Support\Str::headline($option) }}</option>@endforeach
        </select></label>
        <label for="activity-actor"><span>Person</span><select id="activity-actor" name="actor"><option value="">Everyone</option>@foreach($actors as $person)<option value="{{ $person->id }}" @selected((string) $actor === (string) $person->id)>{{ $person->name }}</option>@endforeach</select></label>
        <button class="btn secondary" type="submit"><x-icon name="filter" /> Apply filters</button>
        @if($action || $actor || $search || $hasActivityDates)<a class="text-link" href="{{ route('activity-log') }}"><x-icon name="close" /> Clear filters</a>@endif
        <details class="task-advanced-filters full" @if($hasActivityDates) open @endif><summary><x-icon name="calendar" /> Date filters @if($hasActivityDates)<span>Active</span>@endif <x-icon name="chevron" class="disclosure-icon" /></summary><div class="task-advanced-filter-grid"><label for="activity-from"><span>From date</span><input id="activity-from" name="from" type="date" value="{{ request('from') }}"></label><label for="activity-to"><span>To date</span><input id="activity-to" name="to" type="date" value="{{ request('to') }}"></label></div></details>
</form>
<div class="collection-results"><span>{{ number_format($records->total()) }} {{ \Illuminate\Support\Str::plural('entry', $records->total()) }}</span><span>Newest activity first</span></div>
<section class="activity-feed" aria-label="Recorded activity">
    @forelse($records as $record)
        @php
            $before = $record->before ?? [];
            $after = $record->after ?? [];
            $labels = ['title' => 'Title', 'status' => 'Status', 'amount' => 'Amount', 'priority' => 'Priority', 'deadline' => 'Due date', 'decision' => 'Decision', 'category' => 'Category', 'period' => 'Period', 'transaction_date' => 'Transaction date', 'starts_on' => 'Start date', 'ends_on' => 'End date'];
            $changes = collect($labels)->keys()->filter(fn ($key) => array_key_exists($key, $after) && (!array_key_exists($key, $before) || $before[$key] !== $after[$key]) && (is_scalar($after[$key]) || $after[$key] === null))->take(3);
            $recordType = \Illuminate\Support\Str::headline(\Illuminate\Support\Str::afterLast($record->object_type, '\\'));
        @endphp
        <article class="activity-card">
            <header class="activity-card-heading">
                <span class="activity-marker" aria-hidden="true"><x-icon name="clock" /></span>
                <div class="activity-card-title"><h2>{{ \Illuminate\Support\Str::headline($record->action) }}</h2><p><strong>By {{ $record->actor?->name ?? 'System' }}</strong> &middot; <time datetime="{{ $record->created_at->toIso8601String() }}">{{ $record->created_at->format('M d, Y · g:i A') }}</time></p></div>
                <span class="activity-object">{{ $recordType }}</span>
            </header>
            @if($record->remarks)<p class="activity-remarks">{{ $record->remarks }}</p>@endif
            @if($changes->isNotEmpty())
                <dl class="activity-change-list">
                    @foreach($changes as $key)
                        <div><dt>{{ $labels[$key] }}</dt><dd>@if(array_key_exists($key, $before) && (is_scalar($before[$key]) || $before[$key] === null))<span>{{ $key === 'amount' ? 'PHP '.number_format((float) $before[$key], 2) : \Illuminate\Support\Str::limit((string) ($before[$key] ?? 'None'), 80) }}</span><span aria-hidden="true">&rarr;</span>@endif<strong>{{ $key === 'amount' ? 'PHP '.number_format((float) $after[$key], 2) : \Illuminate\Support\Str::limit((string) ($after[$key] ?? 'None'), 80) }}</strong></dd></div>
                    @endforeach
                </dl>
            @endif
        </article>
    @empty
        <div class="empty-state panel"><x-icon name="clock"/><h3>No activity found</h3><p>Try another action filter. Recorded changes will appear here as chapter work happens.</p></div>
    @endforelse
    @include('chapter.partials.pagination', ['items' => $records])
</section>
@endsection
