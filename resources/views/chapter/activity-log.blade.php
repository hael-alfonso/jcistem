@extends('layouts.chapter')
@section('title', 'Activity log')
@section('content')
<div class="page-heading"><div><div class="eyebrow">CHAPTER HISTORY</div><h1>Activity log</h1><p>See who changed a record, when it happened, and what was updated.</p></div></div>
<div class="activity-toolbar">
    <form method="GET" action="{{ route('activity-log') }}">
        <label for="activity-search">Find activity</label>
        <input id="activity-search" name="q" value="{{ $search }}" placeholder="Person, record, or action">
        <label for="activity-action">Action</label>
        <select id="activity-action" name="action">
            <option value="">All actions</option>
            @foreach($actions as $option)<option value="{{ $option }}" @selected($action === $option)>{{ \Illuminate\Support\Str::headline($option) }}</option>@endforeach
        </select>
        <button class="btn secondary" type="submit"><x-icon name="filter" /> Filter</button>
        @if($action || $search)<a class="text-link" href="{{ route('activity-log') }}">Clear</a>@endif
    </form>
    <span>{{ $records->total() }} {{ \Illuminate\Support\Str::plural('entry', $records->total()) }}</span>
</div>
<section class="activity-feed" aria-label="Recorded activity">
    @forelse($records as $record)
        @php
            $before = $record->before ?? [];
            $after = $record->after ?? [];
            $changed = collect($after)->filter(fn ($value, $key) => !in_array($key, ['id', 'created_at', 'updated_at', 'versions'], true) && (!array_key_exists($key, $before) || $before[$key] !== $value));
            $simpleChanges = $changed->filter(fn ($value) => is_scalar($value) || $value === null)->take(4);
        @endphp
        <article class="activity-card">
            <header class="activity-card-heading">
                <span class="activity-marker" aria-hidden="true"><x-icon name="clock" /></span>
                <div class="activity-card-title"><h2>{{ \Illuminate\Support\Str::headline($record->action) }}</h2><p><strong>{{ $record->actor?->name ?? 'System' }}</strong> · <time datetime="{{ $record->created_at->toIso8601String() }}">{{ $record->created_at->format('M d, Y · g:i A') }}</time></p></div>
                <span class="activity-object">{{ \Illuminate\Support\Str::headline($record->object_type) }} #{{ $record->object_id }}</span>
            </header>
            @if($record->remarks)<p class="activity-remarks">{{ $record->remarks }}</p>@endif
            @if($simpleChanges->isNotEmpty())
                <dl class="activity-change-list">
                    @foreach($simpleChanges as $key => $value)
                        <div><dt>{{ \Illuminate\Support\Str::headline($key) }}</dt><dd>@if(array_key_exists($key, $before) && (is_scalar($before[$key]) || $before[$key] === null))<span>{{ \Illuminate\Support\Str::limit((string) ($before[$key] ?? 'Empty'), 80) }}</span><span aria-hidden="true">→</span>@endif<strong>{{ \Illuminate\Support\Str::limit((string) ($value ?? 'Empty'), 80) }}</strong></dd></div>
                    @endforeach
                </dl>
            @elseif($changed->isNotEmpty())
                <p class="activity-summary">Details in this record were updated.</p>
            @endif
            @if($before || $after)
                <details class="activity-details"><summary>Technical record</summary><div class="detail-grid">
                    <div><h3>Before</h3><pre class="audit-json">{{ $before ? json_encode($before, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : 'New record' }}</pre></div>
                    <div><h3>After</h3><pre class="audit-json">{{ $after ? json_encode($after, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : 'No new values' }}</pre></div>
                </div></details>
            @endif
        </article>
    @empty
        <div class="empty-state panel"><x-icon name="clock"/><h3>No activity found</h3><p>Try another action filter. Recorded changes will appear here as chapter work happens.</p></div>
    @endforelse
    @include('chapter.partials.pagination', ['items' => $records])
</section>
@endsection
