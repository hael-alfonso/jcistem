@extends('layouts.chapter')
@php
$projectPageTitle = match(request('filter')) { 'Archived' => 'Project Archive', 'mine' => 'My Projects', 'review' => 'Project Review Queue', 'approved' => 'Approved Projects', 'ongoing' => 'Ongoing Projects', 'completed' => 'Completed Projects', 'cancelled' => 'Canceled or Declined Projects', default => 'All Projects' };
@endphp
@section('title', $projectPageTitle)
@section('content')
@php
    $quickFilters = ['' => 'All projects', 'mine' => 'My projects', 'approved' => 'Approved', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Canceled / declined'];
    if (request('filter') === 'Archived') $quickFilters['Archived'] = 'Archived';
    if (in_array(auth()->user()->role, ['admin', 'treasurer', 'bod'], true)) $quickFilters['review'] = 'Needs my review';
@endphp
<div class="page-heading collection-heading">
    <div><div class="eyebrow">{{ request('filter') === 'review' ? 'REVIEW WORKFLOW' : 'PROJECT WORK' }}</div><h1>{{ $projectPageTitle }}</h1><p>{{ request('filter') === 'review' ? match(auth()->user()->role) { 'admin' => 'Review every submitted concept, including Admin submissions, then route it to Treasurer feasibility review.', 'treasurer' => 'Review the proposed budget and funding feasibility before the project reaches the BOD agenda.', default => 'Open projects ready for Board deliberation and recorded decisions.' } : 'Find a project and check its status, schedule, and progress.' }}</p></div>
    <a class="btn primary" href="{{ route('projects.create') }}"><x-icon name="plus"/> New project concept</a>
</div>
@if(request('filter') === 'review')
<div class="notice">{{ match(auth()->user()->role) {
    'admin' => 'Review submitted concepts and record Board decisions with the required resolution and evidence.',
    'treasurer' => 'Review funding feasibility after Admin endorsement, then pass the concept to the BOD agenda.',
    default => 'Review concepts on the Board agenda. Admin records the official resolution and decision.'
} }}</div>
@endif
<form class="list-filters collection-toolbar" method="GET" action="{{ route('projects') }}">
    <label>Search by title<input name="q" value="{{ request('q') }}" placeholder="Enter a project title"></label>
    <label>Show projects<select name="filter">
        @foreach($quickFilters as $value => $label)
            <option value="{{ $value }}" @selected(request('filter', '') === $value)>{{ $label }}</option>
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
            $workProgress = \App\Support\ProjectProgress::summarize($project, (int) $project->completed_tasks_count, (int) $project->tasks_count);
        @endphp
        <article class="project-card modern-project-card status-{{ $tone }}">
            <div class="project-card-heading"><span class="project-card-icon"><x-icon :name="$statusIcon"/></span><span class="badge project-status status-{{ $tone }}">{{ $project->status }}</span></div>
            <div class="project-card-copy"><span class="project-reference">{{ $project->reference ?? 'Project #'.$project->id }}</span><h2><a href="{{ route('projects.show', $project) }}">{{ $project->title }}</a></h2><p class="project-focus">{{ $project->area }}</p></div>
            <div class="project-card-facts">
                <div><small>Created by</small><strong>{{ $project->owner?->name ?? 'Unknown' }}</strong></div>
                <div><small>Project chair</small><strong>@if($project->chair)<a href="{{ route('projects.show', $project) }}#chair">{{ $project->chair->name }}</a>@else Not assigned @endif</strong></div>
            </div>
            @include('chapter.partials.project-progress', ['completedTasks' => $project->completed_tasks_count, 'totalTasks' => $project->tasks_count])
            <div class="project-card-footer"><span><small>{{ $isFundedStage ? 'Allocated budget' : 'Proposed budget' }}</small><strong>PHP {{ number_format($isFundedStage ? ($project->allocated_amount ?? 0) : $project->proposed_budget, 2) }}</strong></span><div class="project-card-actions"><button class="text-link" type="button" data-project-preview data-title="{{ $project->title }}" data-reference="{{ $project->reference ?? 'Project #'.$project->id }}" data-status="{{ $project->status }}" data-area="{{ $project->area }}" data-chair="{{ $project->chair?->name ?? 'Not assigned' }}" data-dates="{{ $project->starts_on?->format('M d, Y') ?? 'Not set' }} – {{ $project->ends_on?->format('M d, Y') ?? 'Not set' }}" data-completed="{{ $project->completed_tasks_count }}" data-total="{{ $project->tasks_count }}" data-progress="{{ $workProgress['percent'] }}" data-pace="{{ $workProgress['pace'] }}" data-elapsed="{{ $workProgress['elapsed'] }}" data-objective="{{ $project->concept['objective'] ?? '' }}" data-budget-label="{{ $isFundedStage ? 'Allocated budget' : 'Proposed budget' }}" data-budget="PHP {{ number_format($isFundedStage ? ($project->allocated_amount ?? 0) : $project->proposed_budget, 2) }}" data-href="{{ route('projects.show', $project) }}" aria-label="Quick view: {{ $project->title }}"><x-icon name="eye"/> Quick view</button><a class="text-link" href="{{ route('projects.show', $project) }}" aria-label="View project: {{ $project->title }}">{{ request('filter') === 'review' ? 'Review project' : 'View project' }} <x-icon name="arrow"/></a></div></div>
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
        <p class="project-preview-status" data-preview-status></p><p class="project-preview-objective" data-preview-objective hidden></p>
        <dl class="project-preview-facts"><div><dt>Area of opportunity</dt><dd data-preview-area></dd></div><div><dt>Project chair</dt><dd data-preview-chair></dd></div><div><dt>Schedule</dt><dd data-preview-dates></dd></div><div><dt data-preview-budget-label></dt><dd data-preview-budget></dd></div></dl>
        <div class="project-preview-progress"><div><strong>Project progress</strong><span data-preview-tasks></span></div><div class="project-work-progress-track"><progress max="100" value="0" data-preview-progress aria-label="Project completion"></progress><span class="project-work-progress-marker" data-preview-time-marker hidden aria-hidden="true"></span></div><small data-preview-pace></small></div>
        <div class="project-preview-actions"><button class="btn secondary" type="button" data-preview-close>Close</button><a class="btn primary" href="{{ route('projects') }}" data-preview-link>Open full project <x-icon name="arrow"/></a></div>
    </div>
</dialog>
@include('chapter.partials.pagination', ['items' => $projects])
@endsection
