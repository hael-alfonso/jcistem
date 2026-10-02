@extends('layouts.chapter')
@section('title', 'Activity log')
@section('content')
<div class="page-heading"><div><div class="eyebrow">CHAPTER HISTORY</div><h1>Activity log</h1><p>A chronological record of chapter actions and changes.</p></div></div>
<div class="activity-toolbar">
    <form method="GET" action="{{ route('activity-log') }}">
        <label for="activity-action">Show activity</label>
        <select id="activity-action" name="action" onchange="this.form.submit()">
            <option value="">All actions</option>
            @foreach($actions as $option)<option value="{{ $option }}" @selected($action === $option)>{{ \Illuminate\Support\Str::headline($option) }}</option>@endforeach
        </select>
        <noscript><button class="btn secondary" type="submit">Apply</button></noscript>
    </form>
    <span>{{ $records->total() }} {{ \Illuminate\Support\Str::plural('entry', $records->total()) }}</span>
</div>
<section class="activity-feed" aria-label="Recorded activity">
    @forelse($records as $record)
        <article class="activity-entry">
            <span class="activity-marker" aria-hidden="true"><x-icon name="clock" /></span>
            <div class="activity-card">
                <header><div><h2>{{ \Illuminate\Support\Str::headline($record->action) }}</h2><p>{{ $record->actor?->name ?? 'System' }} ? <time datetime="{{ $record->created_at->toIso8601String() }}">{{ $record->created_at->format('M d, Y ? g:i A') }}</time></p></div><span class="badge">{{ $record->object_type }} #{{ $record->object_id }}</span></header>
                @if($record->remarks)<p class="activity-remarks">{{ $record->remarks }}</p>@endif
                @if($record->before || $record->after)
                    <details class="activity-details"><summary>View recorded details</summary><div class="detail-grid">
                        <div><h3>Before</h3><pre class="audit-json">{{ $record->before ? json_encode($record->before, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : 'No previous values' }}</pre></div>
                        <div><h3>After</h3><pre class="audit-json">{{ $record->after ? json_encode($record->after, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : 'No new values' }}</pre></div>
                    </div></details>
                @endif
            </div>
        </article>
    @empty
        <div class="empty-state panel"><h3>No activity found.</h3><p>Recorded changes will appear here when chapter work begins.</p></div>
    @endforelse
    @include('chapter.partials.pagination', ['items' => $records])
</section>
@endsection
