@extends('layouts.chapter')
@php
$projectPageTitle = match(request('filter')) { 'mine' => 'My Projects', 'proposals' => 'My Proposals', 'chairing' => 'My Chairmanships', 'board' => 'BOD Agenda & Decisions', 'review' => 'Project Review Queue', 'Archived' => 'Project Archive', 'concepts' => 'Project concepts', default => 'All Projects' };
@endphp
@section('title', $projectPageTitle)
@section('content')
@php
    $quickFilters = ['' => 'All projects', 'mine' => 'My projects', 'proposals' => 'My Proposals', 'chairing' => 'My Chairmanships', 'board' => 'BOD Agenda & Decisions', 'concepts' => 'Concepts', 'review' => 'Review queue'];
    $statusFilters = [
        'Planning' => ['Admin Review', 'Treasurer Review', 'BOD Agenda', 'Draft Concept', 'Submitted for President Review', 'Needs Revision', 'Endorsed for Development', 'Full Proposal Draft', 'Returned for Revision', 'Submitted for Formal Approval'],
        'Delivery' => ['Approved', 'Plan Locked', 'Ongoing', 'Completion Review'],
        'Closed' => ['Completed', 'Archived', 'Declined', 'Not Approved'],
    ];
@endphp
<div class="page-heading collection-heading">
    <div><div class="eyebrow">{{ request('filter') === 'review' ? 'REVIEW WORKFLOW' : 'PROJECT WORK' }}</div><h1>{{ $projectPageTitle }}</h1><p>{{ request('filter') === 'review' ? match(auth()->user()->role) { 'admin' => 'Review every submitted concept, including Admin submissions, then route it to Treasurer feasibility review.', 'treasurer' => 'Review the proposed budget and funding feasibility before the project reaches the BOD agenda.', default => 'Open projects ready for Board deliberation and recorded decisions.' } : 'Find a project and check its status, schedule, and progress.' }}</p></div>
    <a class="btn primary" href="{{ route('projects.create') }}"><x-icon name="plus"/> New project concept</a>
</div>
@if(request('filter') === 'review')<div class="review-route-guide"><span class="is-current"><b>1</b> Admin review</span><span><b>2</b> Treasurer review</span><span><b>3</b> BOD decision</span></div>@endif
<form class="list-filters collection-toolbar" method="GET" action="{{ route('projects') }}">
    <label>Search by title<input name="q" value="{{ request('q') }}" placeholder="Enter a project title"></label>
    <label>Show projects<select name="filter">
        @foreach($quickFilters as $value => $label)
            <option value="{{ $value }}" @selected(request('filter', '') === $value)>{{ $label }}</option>
        @endforeach
        @foreach($statusFilters as $group => $statuses)
            <optgroup label="{{ $group }}">
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(request('filter') === $status)>{{ $status }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select></label>
    <button class="btn secondary" type="submit"><x-icon name="filter" /> Apply filters</button>
    @if(request()->filled('q') || request()->filled('filter'))<a class="text-link" href="{{ route('projects') }}"><x-icon name="close" /> Clear filters</a>@endif
</form>
<div class="collection-results"><span>{{ number_format($projects->total()) }} {{ \Illuminate\Support\Str::plural('project', $projects->total()) }}</span><span>Newest first</span></div>
<div class="project-grid modern-project-grid">
    @forelse($projects as $project)
        @php
            $tone = $project->status_tone;
            $statusIcon = match ($tone) { 'draft' => 'filePlus', 'review' => 'fileSignature', 'revision' => 'info', 'approved' => 'shieldCheck', 'ongoing' => 'briefcase', 'completed' => 'check', 'declined' => 'close', 'archived' => 'folder' };
            $isFundedStage = in_array($project->status, \App\Models\Project::APPROVED, true);
            $taskProgress = $project->tasks_count ? (int) round(100 * $project->completed_tasks_count / $project->tasks_count) : 0;
        @endphp
        <article class="project-card modern-project-card status-{{ $tone }}">
            <div class="project-card-heading"><span class="project-card-icon"><x-icon :name="$statusIcon"/></span><span class="badge project-status status-{{ $tone }}">{{ $project->status }}</span></div>
            <div class="project-card-copy"><span class="project-reference">{{ $project->reference ?? 'Project #'.$project->id }}</span><h2><a href="{{ route('projects.show', $project) }}">{{ $project->title }}</a></h2><p class="project-focus">{{ $project->area }}</p>
                @if(!empty($project->concept['objective']))<p class="project-summary">{{ $project->concept['objective'] }}</p>@endif
            </div>
            <div class="project-card-facts">
                <div><small>Created by</small><strong>{{ $project->owner?->name ?? 'Unknown' }}</strong></div>
                <div><small>Project chair</small><strong>@if($project->chair)<a href="{{ route('projects.show', $project) }}#chair">{{ $project->chair->name }}</a>@else Not assigned @endif</strong></div>
                <div><small>Starts</small><strong>{{ $project->starts_on?->format('M d, Y') ?? 'Not set' }}</strong></div>
                <div><small>Ends</small><strong>{{ $project->ends_on?->format('M d, Y') ?? 'Not set' }}</strong></div>
            </div>
            <div class="project-card-progress">
                <div class="progress-label"><span>Tasks completed</span><strong>{{ $project->completed_tasks_count }} of {{ $project->tasks_count }}{{ $project->tasks_count ? ' · '.$taskProgress.'%' : '' }}</strong></div>
                <progress value="{{ $taskProgress }}" max="100" aria-label="{{ $project->title }} task completion"></progress>
            </div>
            <div class="project-card-footer"><span><small>{{ $isFundedStage ? 'Allocated budget' : 'Proposed budget' }}</small><strong>PHP {{ number_format($isFundedStage ? ($project->allocated_amount ?? 0) : $project->proposed_budget, 2) }}</strong></span><div class="project-card-actions"><button class="text-link" type="button" data-project-preview data-title="{{ $project->title }}" data-reference="{{ $project->reference ?? 'Project #'.$project->id }}" data-status="{{ $project->status }}" data-area="{{ $project->area }}" data-chair="{{ $project->chair?->name ?? 'Not assigned' }}" data-dates="{{ $project->starts_on?->format('M d, Y') ?? 'Not set' }} – {{ $project->ends_on?->format('M d, Y') ?? 'Not set' }}" data-completed="{{ $project->completed_tasks_count }}" data-total="{{ $project->tasks_count }}" data-progress="{{ $taskProgress }}" data-budget-label="{{ $isFundedStage ? 'Allocated budget' : 'Proposed budget' }}" data-budget="PHP {{ number_format($isFundedStage ? ($project->allocated_amount ?? 0) : $project->proposed_budget, 2) }}" data-href="{{ route('projects.show', $project) }}" aria-label="Quick view: {{ $project->title }}"><x-icon name="eye"/> Quick view</button><a class="text-link" href="{{ route('projects.show', $project) }}" aria-label="View project: {{ $project->title }}">{{ request('filter') === 'review' ? 'Review project' : 'View project' }} <x-icon name="arrow"/></a></div></div>
        </article>
    @empty
        <div class="panel empty-state full">
            <x-icon name="briefcase"/>
            @if(request('filter') === 'chairing' && !request()->filled('q'))
                <h2>No chair assignments yet</h2><p>The proposer becomes chair after Admin records the official BOD approval.</p>
            @elseif(request()->filled('q') || request()->filled('filter'))
                <h2>No matching projects</h2><p>Try another title or filter.</p>
                <a class="btn secondary" href="{{ route('projects') }}"><x-icon name="close" /> Clear filters</a>
            @else
                <h2>No projects yet</h2>
                <p>Create a project concept to get started.</p>
                <a class="btn primary" href="{{ route('projects.create') }}"><x-icon name="plus" /> New project concept</a>
            @endif
        </div>
    @endforelse
</div>
<dialog class="project-preview-dialog" id="project-preview-dialog" aria-labelledby="project-preview-title">
    <div class="project-preview-content">
        <header><p class="eyebrow" data-preview-reference></p><button type="button" class="project-preview-close" data-preview-close aria-label="Close project preview"><x-icon name="close"/></button></header>
        <h2 id="project-preview-title" data-preview-title></h2>
        <p class="project-preview-status" data-preview-status></p>
        <dl class="project-preview-facts"><div><dt>Area of opportunity</dt><dd data-preview-area></dd></div><div><dt>Project chair</dt><dd data-preview-chair></dd></div><div><dt>Schedule</dt><dd data-preview-dates></dd></div><div><dt data-preview-budget-label></dt><dd data-preview-budget></dd></div></dl>
        <div class="project-preview-progress"><div><strong>Task progress</strong><span data-preview-tasks></span></div><progress max="100" value="0" data-preview-progress aria-label="Project task completion"></progress></div>
        <div class="project-preview-actions"><button class="btn secondary" type="button" data-preview-close>Close</button><a class="btn primary" href="{{ route('projects') }}" data-preview-link>Open full project <x-icon name="arrow"/></a></div>
    </div>
</dialog>
@include('chapter.partials.pagination', ['items' => $projects])
@endsection
