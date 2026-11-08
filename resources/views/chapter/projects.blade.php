@extends('layouts.chapter')
@section('title', $projectFolder['title'])
@section('content')
@php
    $quickFilters = ['' => 'All Current Projects', 'mine' => 'My Current Projects', 'concepts' => 'Ideas & Reviews', 'active' => 'Planning & Implementation', 'approved' => 'Approved Plans', 'ongoing' => 'Implementation & Reports'];
    if (in_array(auth()->user()->role, ['admin', 'treasurer', 'bod'], true)) $quickFilters['review'] = 'Needs my review';
    $hasDateFilters = request()->filled('from') || request()->filled('to');
@endphp
<div class="page-heading collection-heading">
    <div><div class="eyebrow">{{ $projectFolder['eyebrow'] }}</div><h1>{{ $projectFolder['title'] }}</h1><p>{{ $projectFolder['description'] }}</p></div>
    <a class="btn primary" href="{{ route('projects.create') }}"><x-icon name="plus"/> Create Project</a>
</div>
<nav class="project-folder-nav" aria-label="Project folders">
    <a href="{{ route('projects') }}" @if(request()->routeIs('projects')) aria-current="page" @endif><x-icon name="briefcase" /><span><strong>Current</strong><small>Concepts and implementation</small></span><b>{{ $projectFolderCounts['active'] }}</b></a>
    <a href="{{ route('projects.completed') }}" @if(request()->routeIs('projects.completed')) aria-current="page" @endif><x-icon name="folder" /><span><strong>Project Library</strong><small>Completed and archived records</small></span><b>{{ $projectFolderCounts['library'] }}</b></a>
    <a href="{{ route('projects.cancelled') }}" @if(request()->routeIs('projects.cancelled')) aria-current="page" @endif><x-icon name="close" /><span><strong>Cancelled</strong><small>Cancelled and declined</small></span><b>{{ $projectFolderCounts['cancelled'] }}</b></a>
    @if(auth()->user()->role === 'admin')<a href="{{ route('projects.removed') }}" @if(request()->routeIs('projects.removed')) aria-current="page" @endif><x-icon name="archive" /><span><strong>Removed</strong><small>Restorable closed records</small></span><b>{{ $projectFolderCounts['removed'] }}</b></a>@endif
</nav>
@if($projectFolder['key'] === 'library')
<div class="notice project-library-note"><x-icon name="archive" /><span><strong>Chapter project library.</strong> Every active member can open these records for reference. Use <em>Reuse as new concept</em> to start a fresh draft from a stored plan.</span></div>
@endif
@if($projectFolder['key'] === 'active' && request('filter') === 'review')
<div class="notice">{{ match(auth()->user()->role) {
    'admin' => 'Review submitted concepts, record Board decisions with signed evidence, and follow completion reports.',
    'treasurer' => 'Review concept and full proposal budgets independently, then verify submitted liquidations.',
    default => 'Review concepts on the Board agenda. Admin records the official Board decision.'
} }}</div>
@endif
<form class="list-filters collection-toolbar" method="GET" action="{{ route($projectFolder['route']) }}">
    <label>Find project<input name="q" value="{{ request('q') }}" placeholder="Title, reference, or Chair"></label>
    @if($projectFolder['key'] === 'active')
    <label>Show projects<select name="filter">
        @foreach($quickFilters as $value => $label)
            <option value="{{ $value }}" @selected(request('filter', '') === $value)>{{ $label }}</option>
        @endforeach
    </select></label>
    @endif
    <label>Sort<select name="sort"><option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest first</option><option value="oldest" @selected(request('sort') === 'oldest')>Oldest first</option><option value="title" @selected(request('sort') === 'title')>Title A–Z</option></select></label>
    <button class="btn secondary" type="submit"><x-icon name="filter" /> Apply filters</button>
    @if(request()->filled('q') || request()->filled('filter') || request()->filled('from') || request()->filled('to') || request('sort', 'newest') !== 'newest')<a class="text-link" href="{{ route($projectFolder['route']) }}"><x-icon name="close" /> Clear filters</a>@endif
    <details class="task-advanced-filters project-advanced-filters" @if($hasDateFilters) open @endif><summary><x-icon name="calendar" /> Date filters @if($hasDateFilters)<span>Active</span>@endif <x-icon name="chevron" class="disclosure-icon" /></summary><div class="project-advanced-filter-grid"><label>Created from<input type="date" name="from" value="{{ request('from') }}"></label><label>Created to<input type="date" name="to" value="{{ request('to') }}"></label></div></details>
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
                    @if(auth()->user()->role === 'admin' && in_array($projectFolder['key'], ['library', 'cancelled'], true))
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
                <a class="btn secondary" href="{{ route($projectFolder['route']) }}"><x-icon name="close" /> Clear filters</a>
            @elseif($projectFolder['key'] !== 'active')
                <h2>No {{ strtolower($projectFolder['title']) }}</h2>
                <p>This folder is ready when a project reaches this stage.</p>
            @else
                <h2>No current projects yet</h2>
                <p>Create a project concept to get started.</p>
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
@if(auth()->user()->role === 'admin' && in_array($projectFolder['key'], ['library', 'cancelled'], true))
<dialog class="project-remove-dialog" id="project-remove-dialog" aria-labelledby="project-remove-dialog-title" aria-describedby="project-remove-dialog-description">
    <div class="project-remove-dialog-content">
        <header><span class="project-remove-dialog-icon"><x-icon name="trash" /></span><button type="button" class="project-preview-close" data-remove-close aria-label="Close removal dialog"><x-icon name="close" /></button></header>
        <h2 id="project-remove-dialog-title">Remove project</h2>
        <p id="project-remove-dialog-description"><strong data-remove-name></strong> will move to Removed Projects. Its audit and finance records stay intact, and Admin can restore it.</p>
        <form method="POST" action="" data-remove-form>
            @csrf @method('DELETE')
            <label class="field"><span>Reason for removal <em aria-hidden="true">*</em></span><textarea name="reason" rows="3" maxlength="2000" required placeholder="Enter a brief reason for the project record"></textarea></label>
            <div class="project-remove-dialog-actions"><button class="btn secondary" type="button" data-remove-close>Keep project</button><button class="btn danger" type="submit">Move to Removed Projects</button></div>
        </form>
    </div>
</dialog>
@endif
@include('chapter.partials.pagination', ['items' => $projects])
@endsection
