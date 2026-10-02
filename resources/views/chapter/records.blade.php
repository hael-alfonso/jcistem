@extends('layouts.chapter')
@section('title', $kind === 'letters' ? 'JCI Letters of Intent' : 'Reports')
@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">{{ $kind === 'letters' ? 'JCI CORRESPONDENCE' : 'DOCUMENT THE DIFFERENCE' }}</div>
        <h1>{{ $kind === 'letters' ? 'JCI Letters of Intent' : 'Reports' }}</h1>
        <p>{{ $kind === 'letters' ? 'Prepare, review, and track JCI letters of intent for project partnerships.' : 'Find project progress, completion, and chapter financial reports in one place.' }}</p>
    </div>
    @if($projects->isNotEmpty() || ($kind === 'reports' && auth()->user()->role === 'treasurer'))
        <details class="record-create-dropdown no-print">
            <summary class="btn primary">+ {{ $kind === 'letters' ? 'Prepare JCI LOI' : 'Prepare report' }}</summary>
            <div class="record-create-panel">
                @if($kind === 'reports' && auth()->user()->role === 'treasurer')<a href="{{ route('records.create', ['kind' => 'reports']) }}"><strong>Financial report</strong><small>Chapter-wide income and spending</small></a>@endif
                @foreach($projects as $project)<a href="{{ route('records.create', ['kind' => $kind, 'project_id' => $project->id]) }}"><strong>{{ $project->title }}</strong><small>{{ $kind === 'letters' ? 'JCI LOI' : 'Project report' }}</small></a>@endforeach
            </div>
        </details>
    @endif
</div>
@if($kind === 'reports')
<nav class="filter-tabs" aria-label="Report type">
    @foreach(['' => 'All reports', 'Progress' => 'Progress', 'Completion' => 'Completion', 'Overall Financial' => 'Financial'] as $type => $label)
        <a class="{{ request('type','') === $type ? 'selected' : '' }}" href="{{ route('records', array_filter(['kind' => 'reports', 'type' => $type, 'q' => request('q'), 'status' => request('status')])) }}" @if(request('type','') === $type) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
@endif
<form class="list-filters" method="GET" action="{{ route('records', $kind) }}">
    @if(request()->filled('type'))<input type="hidden" name="type" value="{{ request('type') }}">@endif
    <label><span>Search documents</span><input name="q" value="{{ request('q') }}" placeholder="Title or project"></label>
    <label><span>Status</span><select name="status"><option value="">All statuses</option>@foreach(['Draft','For Review','Submitted','Returned','Reviewed','Approved for Sending','Sent','Archived'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></label>
    <button class="btn primary" type="submit">Apply filters</button>
    @if(request()->filled('q') || request()->filled('status'))<a class="text-link" href="{{ route('records', array_filter(['kind' => $kind, 'type' => request('type')])) }}">Clear</a>@endif
</form>
<section class="panel"><div class="table-wrap"><table>
    <thead><tr><th>Document</th><th>Project</th><th>Prepared by</th><th>Status</th><th>Version</th><th></th></tr></thead>
    <tbody>
        @forelse($records as $record)
            <tr><td><strong>{{ $record->title }}</strong><small>{{ $kind === 'letters' && $record->type === 'External Partner Letter' ? 'JCI LOI' : $record->type }}</small></td><td>{{ $record->project?->title ?? 'Chapter finances' }}</td><td>{{ $record->author?->name }}</td><td><span class="badge">{{ $record->status }}</span></td><td>v{{ $record->version }}</td><td><a class="text-link" href="{{ route('records.show', [$kind, $record->id]) }}">Open &rarr;</a></td></tr>
        @empty
            <tr><td colspan="6"><div class="empty-state"><h3>No matching {{ $kind === 'letters' ? 'LOIs' : 'reports' }}.</h3><p>Try another filter. New documents appear here after they are saved.</p><a class="btn secondary" href="{{ route('records', $kind) }}">Show all</a></div></td></tr>
        @endforelse
    </tbody>
</table></div></section>
@endsection
