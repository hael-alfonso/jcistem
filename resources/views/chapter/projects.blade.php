@extends('layouts.chapter')
@section('title', $projectFolder['title'])
@section('content')
@php
    $statusFilters = [
        'review' => 'In Review',
        'approved' => 'Approved',
        'ongoing' => 'Ongoing',
        'launching' => 'Launching (happening now)',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
    $selectedFilter = request('filter', '');
    $specialFilter = $projectFolder['key'] === 'active' && $selectedFilter !== '' && !array_key_exists($selectedFilter, $statusFilters);
    $hasDateFilters = request()->filled('from') || request()->filled('to');
    $isArchive = in_array($projectFolder['key'], ['archived', 'cancelled'], true);
    $archiveQuery = request()->except(['archive', 'page']);
    $completedArchiveQuery = array_merge($archiveQuery, ['archive' => 'completed']);
    $cancelledArchiveQuery = array_merge($archiveQuery, ['archive' => 'cancelled']);
@endphp
<div class="page-heading collection-heading">
    <div><div class="eyebrow">{{ $projectFolder['eyebrow'] }}</div><h1>{{ $projectFolder['title'] }}</h1><p>{{ $projectFolder['description'] }}</p></div>
    <div class="heading-actions">@if(auth()->user()->role === 'admin')<a class="btn secondary" href="{{ route($projectFolder['route'], array_merge($projectFolder['routeParams'], request()->query(), ['print' => 1])) }}"><x-icon name="print"/> Print project report</a>@endif<a class="btn primary" href="{{ route('projects.create') }}"><x-icon name="plus"/> Create Project</a></div>
</div>
<nav class="project-folder-nav" aria-label="Project folders">
    <a href="{{ route('projects') }}" @if(request()->routeIs('projects')) aria-current="page" @endif><x-icon name="briefcase" /><span><strong>All Projects</strong><small>Submitted and closed records</small></span><b>{{ $projectFolderCounts['active'] }}</b></a>
    <a href="{{ route('projects.completed') }}" @if(request()->routeIs('projects.completed')) aria-current="page" @endif><x-icon name="check" /><span><strong>Completed</strong><small>Awaiting 7-day archive</small></span><b>{{ $projectFolderCounts['completed'] }}</b></a>
    <a href="{{ route('projects.archived') }}" @if($isArchive) aria-current="page" @endif><x-icon name="archive" /><span><strong>Archive</strong><small>Completed and cancelled records</small></span><b>{{ $projectFolderCounts['archived'] + $projectFolderCounts['cancelled'] }}</b></a>
    @if(auth()->user()->role === 'admin')<a href="{{ route('projects.removed') }}" @if(request()->routeIs('projects.removed')) aria-current="page" @endif><x-icon name="trash" /><span><strong>Deleted Projects</strong><small>Restorable records</small></span><b>{{ $projectFolderCounts['removed'] }}</b></a>@endif
</nav>
@if(in_array($projectFolder['key'], ['completed', 'archived'], true))
<div class="notice project-library-note"><x-icon name="archive" /><span><strong>Chapter project records.</strong> Members can open completed plans for reference and use <em>Reuse as new concept</em> to start a fresh draft.</span></div>
@endif
@if($projectFolder['key'] === 'active' && request('filter') === 'review')
<div class="notice">{{ match(auth()->user()->role) {
    'admin' => 'Drafts are visible here for context. Review submitted concepts, record Board decisions with signed evidence, and follow completion reports.',
    'treasurer' => 'Drafts are visible here for context. Review submitted budgets independently, then verify liquidations.',
    default => 'Drafts are visible here for context. Read submitted full proposals before the Board meeting; Admin records the official decision.'
} }}</div>
@endif
<div class="project-collection-controls">
    @if($isArchive)
    <nav class="project-folder-nav project-archive-tabs" aria-label="Archive sections">
        <a href="{{ route('projects.archived', $completedArchiveQuery) }}" @if($projectFolder['key'] === 'archived') aria-current="page" @endif><x-icon name="check" /><span><strong>Completed</strong><small>Finished project records</small></span><b>{{ $projectFolderCounts['archived'] }}</b></a>
        <a href="{{ route('projects.archived', $cancelledArchiveQuery) }}" @if($projectFolder['key'] === 'cancelled') aria-current="page" @endif><x-icon name="folder" /><span><strong>Cancelled &amp; Closed</strong><small>Cancelled, declined, and not approved</small></span><b>{{ $projectFolderCounts['cancelled'] }}</b></a>
    </nav>
    @endif
    <nav class="project-sort-tabs" aria-label="Sort projects">
        @foreach(['newest' => 'Newest first', 'oldest' => 'Oldest first', 'title' => 'Title A-Z'] as $sortValue => $sortLabel)
            @php $sortQuery = request()->except(['sort', 'page']); if ($sortValue !== 'newest') $sortQuery['sort'] = $sortValue; @endphp
            <a href="{{ route($projectFolder['route'], array_merge($projectFolder['routeParams'], $sortQuery)) }}" @if(request('sort', 'newest') === $sortValue) aria-current="page" @endif>{{ $sortLabel }}</a>
        @endforeach
    </nav>
</div>
<form class="list-filters collection-toolbar" method="GET" action="{{ route($projectFolder['route']) }}">
    @if($isArchive)<input type="hidden" name="archive" value="{{ $projectFolder['routeParams']['archive'] }}">@endif
    <input type="hidden" name="sort" value="{{ request('sort', 'newest') }}">
    <label class="project-search-field">Find project<input name="q" value="{{ request('q') }}" placeholder="Title, reference, or Chair"></label>
    @if($projectFolder['key'] === 'active')
    <label class="project-status-select">Project status
        <select name="filter">
            <option value="">All statuses</option>
            @if($specialFilter)<option value="{{ $selectedFilter }}" selected>{{ $projectFolder['title'] }}</option>@endif
            @foreach($statusFilters as $value => $label)
                <option value="{{ $value }}" @selected($selectedFilter === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    @endif
    <label>Created from month<input type="month" name="from" value="{{ request('from') }}"></label>
    <label>Created to month<input type="month" name="to" value="{{ request('to') }}"></label>
    <div class="filter-actions"><button class="btn secondary" type="submit"><x-icon name="filter" /> Apply</button>
    @if(request()->filled('q') || request()->filled('filter') || request()->filled('from') || request()->filled('to') || request('sort', 'newest') !== 'newest')<a class="text-link" href="{{ route($projectFolder['route'], $projectFolder['routeParams']) }}"><x-icon name="close" /> Clear filters</a>@endif</div>
</form>
<div class="collection-results"><span>{{ number_format($projects->total()) }} {{ \Illuminate\Support\Str::plural('project', $projects->total()) }}</span><span>{{ match(request('sort', 'newest')) { 'oldest' => 'Oldest first', 'title' => 'Title A–Z', default => 'Newest first' } }}</span></div>
<div class="project-grid modern-project-grid">
    @forelse($projects as $project)
        @php
            $tone = $project->status_tone;
            $statusIcon = match ($tone) { 'draft' => 'filePlus', 'review' => 'fileSignature', 'revision' => 'info', 'approved' => 'shieldCheck', 'ongoing' => 'briefcase', 'completed' => 'check', 'declined' => 'close', 'archived' => 'folder' };
            $isFundedStage = in_array($project->status, \App\Models\Project::APPROVED, true);
            $workProgress = \App\Support\ProjectProgress::summarize($project, (int) $project->completed_tasks_count, (int) $project->tasks_count);
            $chair = $project->chair;
            $chairHasPhoto = $chair && ! $chair->trashed() && $chair->status === 'active' && ! empty($chair->profile['photo_path']);
        @endphp
        <article class="project-card modern-project-card status-{{ $tone }}">
            <div class="project-card-heading"><span class="project-card-icon"><x-icon :name="$statusIcon"/></span><span class="badge project-status status-{{ $tone }}">{{ \App\Support\ProjectFlow::statusLabel($project) }}</span></div>
            <div class="project-card-copy"><span class="project-reference">{{ $project->reference ?? 'Project #'.$project->id }}</span><h2>@if($project->trashed()){{ $project->title }}@else<a href="{{ route('projects.show', $project) }}">{{ $project->title }}</a>@endif</h2><p class="project-focus">{{ $project->area }}</p></div>
            <div class="project-card-facts">
                <div><small>Created by</small><strong>{{ $project->owner?->name ?? 'Unknown' }}</strong></div>
                <div class="project-card-chair">
                    <span class="project-chair-avatar" aria-hidden="true">
                        @if($chair)
                            <span class="project-chair-initial">{{ strtoupper(mb_substr($chair->name, 0, 1)) }}</span>
                            @if($chairHasPhoto)<img src="{{ route('members.photo', $chair) }}?v={{ $chair->updated_at?->timestamp }}" width="34" height="34" alt="" loading="lazy" decoding="async">@endif
                        @else
                            <x-icon name="user" />
                        @endif
                    </span>
                    <span class="project-chair-name"><small>Project chair</small><strong>{{ $chair?->name ?? 'Not assigned' }}</strong></span>
                </div>
            </div>
            @include('chapter.partials.project-progress', ['completedTasks' => $project->completed_tasks_count, 'totalTasks' => $project->tasks_count])
            @if($project->trashed())
                <div class="project-card-footer"><span><small>Removed on</small><strong>{{ $project->deleted_at?->format('M d, Y') }}</strong></span><form class="project-card-actions" method="POST" action="{{ route('projects.restore', $project->id) }}">@csrf<button class="project-card-action" type="submit" data-confirm="Restore this project to its original chapter collection?" aria-label="Restore project: {{ $project->title }}" title="Restore project"><x-icon name="refresh" /></button></form></div>
            @else
                <div class="project-card-footer"><span><small>{{ $isFundedStage ? 'Allocated budget' : 'Proposed budget' }}</small><strong>PHP {{ number_format($isFundedStage ? ($project->allocated_amount ?? 0) : $project->proposed_budget, 2) }}</strong></span><div class="project-card-actions"><button class="project-card-action" type="button" data-project-preview data-title="{{ $project->title }}" data-reference="{{ $project->reference ?? 'Project #'.$project->id }}" data-status="{{ \App\Support\ProjectFlow::statusLabel($project) }}" data-area="{{ $project->area }}" data-chair="{{ $project->chair?->name ?? 'Not assigned' }}" data-dates="{{ $project->starts_on?->format('M d, Y') ?? 'Not set' }} – {{ $project->ends_on?->format('M d, Y') ?? 'Not set' }}" data-completed="{{ $project->completed_tasks_count }}" data-total="{{ $project->tasks_count }}" data-progress="{{ $workProgress['percent'] }}" data-pace="{{ $workProgress['pace'] }}" data-elapsed="{{ $workProgress['elapsed'] }}" data-objective="{{ $project->concept_submission_method === 'upload' ? '' : ($project->concept['objective'] ?? '') }}" data-budget-label="{{ $isFundedStage ? 'Allocated budget' : 'Proposed budget' }}" data-budget="PHP {{ number_format($isFundedStage ? ($project->allocated_amount ?? 0) : $project->proposed_budget, 2) }}" data-href="{{ route('projects.show', $project) }}" aria-label="Quick view: {{ $project->title }}" title="Quick view"><x-icon name="eye" /></button><a class="project-card-action" href="{{ route('projects.show', $project) }}" aria-label="{{ request('filter') === 'review' ? 'Open project for review' : 'Open full project' }}: {{ $project->title }}" title="{{ request('filter') === 'review' ? 'Open for review' : 'Open full project' }}"><x-icon name="arrow" /></a>
                    @if(auth()->user()->role === 'admin' && $projectFolder['key'] === 'completed')
                        <form method="POST" action="{{ route('projects.archive', $project) }}">@csrf<button class="project-card-action" type="submit" data-confirm="Archive this completed project now? Its status will remain Completed." aria-label="Archive project: {{ $project->title }}" title="Archive now"><x-icon name="archive" /></button></form>
                    @endif
                    @if(auth()->user()->role === 'admin' && in_array($projectFolder['key'], ['archived', 'cancelled'], true))
                        <a class="project-card-action is-danger" href="{{ route('projects.show', $project) }}#project-remove-title" data-project-remove data-remove-url="{{ route('projects.delete', $project) }}" data-remove-title="{{ $project->title }}" aria-label="Remove project: {{ $project->title }}" aria-haspopup="dialog" aria-controls="project-remove-dialog" title="Remove project"><x-icon name="trash" /></a>
                    @endif
                </div></div>
            @endif
        </article>
    @empty
        <div class="panel empty-state full">
            <x-icon name="briefcase"/>
            @if(request('filter') === 'chairing' && !request()->filled('q'))
                <h2>No chair assignments yet</h2><p>The proposer becomes chair after Admin records the official BOD approval.</p>
            @elseif(request()->filled('q') || request()->filled('filter') || $hasDateFilters)
                <h2>No matching projects</h2><p>Try another title or filter.</p>
                <a class="btn secondary" href="{{ route($projectFolder['route'], $projectFolder['routeParams']) }}"><x-icon name="close" /> Clear filters</a>
            @elseif($projectFolder['key'] !== 'active')
                <h2>No {{ strtolower($projectFolder['title']) }}</h2>
                <p>This folder is ready when a project reaches this stage.</p>
            @else
                <h2>No projects here yet</h2>
                <p>Submitted projects will appear here for chapter monitoring. Your drafts remain in My Projects.</p>
                <a class="btn primary" href="{{ route('projects.create') }}"><x-icon name="plus" /> Create Project</a>
            @endif
        </div>
    @endforelse
</div>
<dialog class="project-preview-dialog" id="project-preview-dialog" aria-labelledby="project-preview-title">
    <div class="project-preview-content">
        <header><p class="eyebrow" data-preview-reference></p><button type="button" class="project-preview-close" data-preview-close aria-label="Close project preview"><x-icon name="close"/></button></header>
        <h2 id="project-preview-title" data-preview-title></h2>
        <p class="project-preview-status" data-preview-status></p><p class="project-preview-objective" data-preview-objective hidden></p>
        <dl class="project-preview-facts"><div><dt>Area of opportunity</dt><dd data-preview-area></dd></div><div><dt>Project chair</dt><dd data-preview-chair></dd></div><div><dt>Schedule</dt><dd data-preview-dates></dd></div><div><dt data-preview-budget-label></dt><dd data-preview-budget></dd></div></dl>
        <div class="project-preview-progress"><div><strong>Project progress</strong><span data-preview-tasks></span></div><div class="project-work-progress-track"><progress max="100" value="0" data-preview-progress aria-label="Project completion"></progress><span class="project-work-progress-marker" data-preview-time-marker hidden aria-hidden="true"></span></div><small data-preview-pace></small></div>
        <div class="project-preview-actions"><button class="btn secondary" type="button" data-preview-close>Close</button><a class="btn primary" href="{{ route('projects') }}" data-preview-link>Open full project <x-icon name="arrow"/></a></div>
    </div>
</dialog>
@if(auth()->user()->role === 'admin' && in_array($projectFolder['key'], ['archived', 'cancelled'], true))
<dialog class="project-remove-dialog" id="project-remove-dialog" aria-labelledby="project-remove-dialog-title" aria-describedby="project-remove-dialog-description">
    <div class="project-remove-dialog-content">
        <header><span class="project-remove-dialog-icon"><x-icon name="trash" /></span><button type="button" class="project-preview-close" data-remove-close aria-label="Close removal dialog"><x-icon name="close" /></button></header>
        <h2 id="project-remove-dialog-title">Remove project</h2>
        <p id="project-remove-dialog-description"><strong data-remove-name></strong> will move to Deleted Projects. Its audit and finance records stay intact, and Admin can restore it.</p>
        <form method="POST" action="" data-remove-form>
            @csrf @method('DELETE')
            <div class="project-remove-dialog-actions"><button class="btn secondary" type="button" data-remove-close>Keep project</button><button class="btn danger" type="submit">Move to Deleted Projects</button></div>
        </form>
    </div>
</dialog>
@endif
@include('chapter.partials.pagination', ['items' => $projects])
@endsection
