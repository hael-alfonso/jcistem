@extends('layouts.chapter')
@section('title', $kind === 'letters' ? 'JCI Letters of Intent' : 'Project Reports')
@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">{{ $kind === 'letters' ? 'JCI CORRESPONDENCE' : 'DOCUMENT THE DIFFERENCE' }}</div>
        <h1>{{ $kind === 'letters' ? 'JCI Letters of Intent' : 'Project Reports' }}</h1>
        <p>{{ $kind === 'letters' ? 'Submit external LOIs to Admin, then print approved copies for hard-copy delivery.' : 'Find Project Completion Reports, liquidation reports, and chapter financial records.' }}</p>
    </div>
    @if($projects->isNotEmpty() || ($kind === 'reports' && auth()->user()->role === 'treasurer'))
        <details class="record-create-dropdown no-print">
            <summary class="btn primary"><x-icon name="filePlus" /> {{ $kind === 'letters' ? 'Prepare JCI LOI' : 'Prepare Report' }}</summary>
            <div class="record-create-panel">
                @if($kind === 'reports' && auth()->user()->role === 'treasurer')<a href="{{ route('records.create', ['kind' => 'reports']) }}"><strong>Financial report</strong><small>Chapter-wide income and spending</small></a>@endif
                @foreach($projects as $project)
                    @if($kind === 'letters')<a href="{{ route('records.create', ['kind' => 'letters', 'project_id' => $project->id]) }}"><strong>{{ $project->title }}</strong><small>External partnership LOI</small></a>
                    @else<a href="{{ route('records.create', ['kind' => 'reports', 'project_id' => $project->id, 'type' => 'Completion']) }}"><strong>{{ $project->title }} · Completion Report</strong><small>Project Completion Report (PCR)</small></a><a href="{{ route('records.create', ['kind' => 'reports', 'project_id' => $project->id, 'type' => 'Liquidation']) }}"><strong>{{ $project->title }} · Liquidation Report</strong><small>Receipts and cash reconciliation</small></a>@endif
                @endforeach
            </div>
        </details>
    @endif
</div>
@if($kind === 'reports')
<nav class="filter-tabs" aria-label="Report type">
    @foreach(['' => 'All Reports', 'Completion' => 'Completion Reports', 'Liquidation' => 'Liquidation Reports', 'Overall Financial' => 'Financial Reports'] as $type => $label)
        <a class="{{ request('type','') === $type ? 'selected' : '' }}" href="{{ route('records', array_filter(['kind' => 'reports', 'type' => $type] + request()->only(['q', 'status', 'from', 'to', 'mine']))) }}" @if(request('type','') === $type) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
@if(auth()->user()->role === 'admin')
    <div class="notice reports-inbox-note"><strong>Admin report inbox.</strong> Submitted reports are retained here. Admin approves Completion and chapter financial reports; liquidation copies remain visible here while the Treasurer makes the approval.</div>
@endif
@endif
<form class="list-filters" method="GET" action="{{ route('records', $kind) }}">
    @if(request()->boolean('mine'))<input type="hidden" name="mine" value="1">@endif
    @if(request()->filled('type'))<input type="hidden" name="type" value="{{ request('type') }}">@endif
    <label><span>Search documents</span><input name="q" value="{{ request('q') }}" placeholder="Title, project, or author"></label>
    <label><span>Status</span><select name="status"><option value="">All statuses</option>@foreach($kind === 'reports' ? ['Draft','Submitted','Returned','Reviewed'] : ['Draft','For Review','Returned','Approved for Sending','Sent','Archived'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></label>
    <label>Created from<input type="date" name="from" value="{{ request('from') }}"></label><label>Created to<input type="date" name="to" value="{{ request('to') }}"></label>
    <button class="btn secondary" type="submit"><x-icon name="filter" /> Apply filters</button>
    @if(request()->hasAny(['q', 'status', 'from', 'to']))<a class="text-link" href="{{ route('records', array_filter(['kind' => $kind, 'type' => request('type'), 'mine' => request('mine')])) }}"><x-icon name="close" /> Clear filters</a>@endif
</form>
<section class="panel"><div class="table-wrap"><table>
    <thead><tr><th>Document</th><th>Project</th><th>Prepared by</th><th>Status</th><th>Version</th><th></th></tr></thead>
    <tbody>
        @forelse($records as $record)
            <tr><td><strong>{{ $record->title }}</strong><small>{{ $kind === 'letters' && $record->type === 'External Partner Letter' ? 'JCI LOI' : ($record->type === 'Completion' ? 'Completion Report' : $record->type) }}@if($kind === 'reports' && $record->type === 'Liquidation' && auth()->user()->role === 'admin') · Admin copy; Treasurer approval @endif</small></td><td>{{ $record->project?->title ?? 'Chapter finances' }}</td><td>{{ $record->author?->name }}</td><td><x-status-badge :status="$record->status" /></td><td>v{{ $record->version }}</td><td><a class="text-link" href="{{ route('records.show', [$kind, $record->id]) }}"><x-icon name="eye" /> Open</a></td></tr>
        @empty
            <tr><td colspan="6"><div class="empty-state"><h3>No matching {{ $kind === 'letters' ? 'LOIs' : 'reports' }}.</h3><p>Try another filter. New documents appear here after they are saved.</p><a class="btn secondary" href="{{ route('records', $kind) }}"><x-icon name="close" /> Show all</a></div></td></tr>
        @endforelse
    </tbody>
</table></div>@include('chapter.partials.pagination',['items'=>$records])</section>
@endsection
