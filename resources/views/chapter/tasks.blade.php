@extends('layouts.chapter')
@section('title', 'Tasks & milestones')
@section('content')
<div class="page-heading collection-heading">
    <div><div class="eyebrow">PROJECT WORK</div><h1>Tasks &amp; milestones</h1><p>Find a task, check its deadline, and open the project to update it.</p></div>
    <a class="btn secondary" href="{{ route('tasks', ['mine' => request()->boolean('mine') ? 0 : 1] + array_filter(['q' => request('q'), 'project' => request('project'), 'status' => request('status')])) }}">{{ request()->boolean('mine') ? 'View all tasks' : 'View my tasks' }}</a>
</div>

<section class="panel task-summary" aria-label="Task summary">
    <div class="task-summary-grid">
        <div><span>{{ request()->boolean('mine') ? 'My assigned tasks' : 'Tasks in view' }}</span><strong>{{ $taskTotal }}</strong></div>
        <div><span>Open tasks</span><strong>{{ $taskTotal - $doneTasks }}</strong></div>
        <div><span>Completed</span><strong>{{ $doneTasks }}</strong></div>
        <div><span>Overdue</span><strong @class(['danger-text' => $overdueTasks > 0])>{{ $overdueTasks }}</strong></div>
    </div>
    <p>Counts follow your search and project filters. Choose a status below to narrow the list.</p>
</section>
@php
    $baseFilters = array_filter(['mine' => request()->boolean('mine') ? 1 : 0, 'q' => request('q'), 'project' => request('project')], fn ($value) => $value !== null && $value !== '');
    $statusOptions = ['' => 'All tasks', 'To Do' => 'To do', 'In Progress' => 'In progress', 'Blocked' => 'Blocked', 'Completed' => 'Completed'];
@endphp

<form class="list-filters collection-toolbar" method="GET" action="{{ route('tasks') }}">
    <input type="hidden" name="mine" value="{{ request()->boolean('mine') ? 1 : 0 }}">
    @if(request()->filled('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
    <label>Search tasks<input name="q" value="{{ request('q') }}" placeholder="Task title"></label>
    <label>Project<select name="project"><option value="">All projects</option>@foreach($visibleProjects as $project)<option value="{{ $project->id }}" @selected((string) request('project') === (string) $project->id)>{{ $project->title }}</option>@endforeach</select></label>
    <button class="btn secondary" type="submit">Apply filters</button>
    @if(request()->filled('q') || request()->filled('project'))<a class="text-link" href="{{ route('tasks', ['mine' => request()->boolean('mine') ? 1 : 0] + array_filter(['status' => request('status')])) }}">Clear filters</a>@endif
</form>
<section class="panel task-table-panel" aria-label="Task list">
    <div class="panel-heading"><div><h2>{{ request('status') ?: (request()->boolean('mine') ? 'My tasks' : 'All tasks') }}</h2><p>{{ $tasks->total() }} matching {{ \Illuminate\Support\Str::plural('task', $tasks->total()) }} · ordered by deadline</p></div></div>
    <nav class="filter-tabs task-status-tabs" aria-label="Task status filters">
    @foreach($statusOptions as $value => $label)
        <a class="{{ request('status', '') === $value ? 'selected' : '' }}" href="{{ route('tasks', $baseFilters + ($value !== '' ? ['status' => $value] : [])) }}" @if(request('status', '') === $value) aria-current="page" @endif>{{ $label }} <span>{{ $value === '' ? $taskTotal : (int) ($statusCounts[$value] ?? 0) }}</span></a>
    @endforeach
</nav>
    @if($tasks->isNotEmpty())
    <div class="table-wrap">
        <table class="task-table">
            <caption class="sr-only">Task assignments, deadlines, priority, and current status</caption>
            <thead><tr><th scope="col">Task / project</th><th scope="col">Assigned to</th><th scope="col">Deadline</th><th scope="col">Priority</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead>
            <tbody>
                @foreach($tasks as $task)
                    @php
                        $assigned = collect($task->assignees ?? [])->map(fn ($id) => $assigneeNames[$id] ?? null)->filter()->join(', ');
                        $overdue = $task->deadline && $task->deadline->lt(today()) && $task->status !== 'Completed';
                        $tone = match($task->status) { 'Completed' => 'done', 'In Progress' => 'working', 'Blocked' => 'blocked', default => 'todo' };
                        $priorityTone = match($task->priority) { 'Urgent' => 'urgent', 'High' => 'high', 'Medium' => 'medium', 'Low' => 'low', default => 'unset' };
                    @endphp
                    <tr id="task-{{ $task->id }}">
                        <td data-label="Task" class="task-main-cell"><a href="{{ route('projects.show', $task->project_id) }}#task-{{ $task->id }}"><strong>{{ $task->title }}</strong></a><small>Project: {{ $task->project?->title ?? 'Project' }}</small>@if($task->milestone)<small>Milestone: {{ $task->milestone }}</small>@endif @if($task->description)<p class="task-description">{{ $task->description }}</p>@endif</td>
                        <td data-label="Assigned to" class="task-assignees">{{ $assigned ?: 'Unassigned' }}</td>
                        <td data-label="Deadline"><span>{{ $task->deadline?->format('M d, Y') ?? 'No deadline' }}</span>@if($overdue)<small class="danger-text">Overdue</small>@endif</td>
                        <td data-label="Priority"><span class="task-priority priority-{{ $priorityTone }}">{{ $task->priority ?: 'Not set' }}</span></td>
                        <td data-label="Status"><span class="badge task-status task-status-{{ $tone }}">{{ $task->status }}</span></td>
                        <td data-label="Action"><a class="text-link" href="{{ route('projects.show', $task->project_id) }}#task-{{ $task->id }}" aria-label="Open task: {{ $task->title }}">Open task &rarr;</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('chapter.partials.pagination', ['items' => $tasks])
    @else
        <div class="empty-state"><h3>No matching tasks</h3><p>Try another search or choose a different status.</p><a class="btn secondary" href="{{ route('tasks', ['mine' => request()->boolean('mine') ? 1 : 0]) }}">Reset filters</a></div>
    @endif
</section>
@endsection
