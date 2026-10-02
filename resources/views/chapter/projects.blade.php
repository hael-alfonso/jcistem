@extends('layouts.chapter')
@section('title', request('filter') === 'concepts' ? 'Project concepts' : 'Projects')
@section('content')
<div class="page-heading">
    <div><div class="eyebrow">FROM IDEA TO IMPACT</div><h1>{{ request('filter') === 'concepts' ? 'Project concepts' : 'Projects' }}</h1><p>Find a project, review its status, and open it to see the full plan.</p></div>
    <a class="btn primary" href="{{ route('projects.create') }}">+ New project concept</a>
</div>
<nav class="filter-tabs" aria-label="Project status filters">
    @foreach(['' => 'All projects', 'mine' => 'My projects', 'concepts' => 'Concepts', 'review' => 'Pending review', 'Approved' => 'Approved', 'Ongoing' => 'Ongoing', 'Completed' => 'Completed', 'Archived' => 'Archived'] as $value => $label)
        <a class="{{ request('filter', '') === $value ? 'selected' : '' }}" href="{{ route('projects', ['filter' => $value]) }}" @if(request('filter', '') === $value) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
<div class="status-legend" aria-label="Project status colors"><strong>Status colors</strong>
    @foreach(['draft' => 'Draft', 'review' => 'In review', 'revision' => 'Needs changes', 'approved' => 'Approved', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'declined' => 'Declined', 'archived' => 'Archived'] as $tone => $label)
        <span><i class="status-dot status-{{ $tone }}"></i>{{ $label }}</span>
    @endforeach
</div>
<form class="search-bar" method="GET" action="{{ route('projects') }}"><input type="hidden" name="filter" value="{{ request('filter') }}"><label class="sr-only" for="project-search">Search projects</label><input id="project-search" name="q" value="{{ request('q') }}" placeholder="Search by project title"><button class="btn secondary" type="submit">Search</button>@if(request()->filled('q'))<a class="btn secondary" href="{{ route('projects', ['filter' => request('filter')]) }}">Clear</a>@endif</form>
<div class="project-grid">
    @forelse($projects as $project)
        <article class="project-card">
            <div class="card-top"><span class="eyebrow">{{ $project->reference }}</span><span class="badge project-status status-{{ $project->status_tone }}">{{ $project->status }}</span></div>
            <p class="area-label">{{ $project->area }}</p>
            <h2><a href="{{ route('projects.show', $project) }}">{{ $project->title }}</a></h2>
            <p class="project-summary">{{ \Illuminate\Support\Str::limit($project->concept['objective'] ?? '', 120) }}</p>
            <div class="project-meta"><span>Chair <strong>{{ $project->chair?->name ?? 'Not assigned' }}</strong></span><span>Date <strong>{{ $project->starts_on?->format('M d, Y') ?? 'To be set' }}</strong></span></div>
            <div class="progress-label"><span>Task completion</span><strong>{{ $project->progress }}%</strong></div><progress value="{{ $project->progress }}" max="100"></progress>
            <div class="card-bottom"><span>Allocation <strong>&#8369;{{ number_format($project->allocated, 2) }}</strong></span><a class="text-link" href="{{ route('projects.show', $project) }}">Open project &rarr;</a></div>
        </article>
    @empty
        <div class="panel empty-state full"><h2>No projects found.</h2><p>Try another search or create a project concept.</p><a class="btn primary" href="{{ route('projects.create') }}">Create a concept</a></div>
    @endforelse
</div>
@include('chapter.partials.pagination', ['items' => $projects])
@endsection
