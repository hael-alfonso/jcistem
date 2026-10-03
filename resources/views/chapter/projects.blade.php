@extends('layouts.chapter')
@section('title', request('filter') === 'concepts' ? 'Project concepts' : 'Projects')
@section('content')
@php
    $quickFilters = ['' => 'All projects', 'mine' => 'My projects', 'concepts' => 'Concepts', 'review' => 'Under review'];
    $statusFilters = [
        'Planning' => ['Draft Concept', 'Submitted for President Review', 'Needs Revision', 'Endorsed for Development', 'Full Proposal Draft', 'Returned for Revision', 'Submitted for Formal Approval'],
        'Delivery' => ['Approved', 'Ongoing', 'Completion Review'],
        'Closed' => ['Completed', 'Archived', 'Declined', 'Not Approved'],
    ];
@endphp
<div class="page-heading collection-heading">
    <div><div class="eyebrow">PROJECT WORK</div><h1>{{ request('filter') === 'concepts' ? 'Project concepts' : 'Projects' }}</h1><p>Find a project and check its status, schedule, and progress.</p></div>
    <a class="btn primary" href="{{ route('projects.create') }}"><x-icon name="plus"/> New project concept</a>
</div>
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
    <button class="btn secondary" type="submit">Apply filters</button>
    @if(request()->filled('q') || request()->filled('filter'))<a class="text-link" href="{{ route('projects') }}">Clear filters</a>@endif
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
                <div><small>Project chair</small><strong>{{ $project->chair?->name ?? 'Not assigned' }}</strong></div>
                <div><small>Starts</small><strong>{{ $project->starts_on?->format('M d, Y') ?? 'Not set' }}</strong></div>
                <div><small>Ends</small><strong>{{ $project->ends_on?->format('M d, Y') ?? 'Not set' }}</strong></div>
            </div>
            <div class="project-card-progress">
                <div class="progress-label"><span>Tasks completed</span><strong>{{ $project->completed_tasks_count }} of {{ $project->tasks_count }}{{ $project->tasks_count ? ' · '.$taskProgress.'%' : '' }}</strong></div>
                <progress value="{{ $taskProgress }}" max="100" aria-label="{{ $project->title }} task completion"></progress>
            </div>
            <div class="project-card-footer"><span><small>{{ $isFundedStage ? 'Allocated budget' : 'Proposed budget' }}</small><strong>PHP {{ number_format($isFundedStage ? ($project->allocated_amount ?? 0) : $project->proposed_budget, 2) }}</strong></span><a class="text-link" href="{{ route('projects.show', $project) }}" aria-label="View project: {{ $project->title }}">View project <x-icon name="arrow"/></a></div>
        </article>
    @empty
        <div class="panel empty-state full">
            <x-icon name="briefcase"/>
            @if(request()->filled('q') || request()->filled('filter'))
                <h2>No matching projects</h2><p>Try another title or filter.</p>
                <a class="btn secondary" href="{{ route('projects') }}">Clear filters</a>
            @else
                <h2>No projects yet</h2>
                <p>Create a project concept to get started.</p>
                <a class="btn primary" href="{{ route('projects.create') }}">New project concept</a>
            @endif
        </div>
    @endforelse
</div>
@include('chapter.partials.pagination', ['items' => $projects])
@endsection
