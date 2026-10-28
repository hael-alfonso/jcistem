@extends('layouts.chapter')
@section('title', 'Tasks & milestones')
@section('content')
@php
    $mine = request()->boolean('mine');
    $baseFilters = array_filter([
        'mine' => $mine ? 1 : 0,
        'q' => request('q'),
        'project' => request('project'),
    ], fn ($value) => $value !== null && $value !== '');
    $statusOptions = ['' => 'All', 'To Do' => 'To do', 'In Progress' => 'In progress', 'Blocked' => 'Blocked', 'Completed' => 'Completed'];
    $hasFilters = request()->filled('q') || request()->filled('project') || request()->filled('status');
@endphp

<div class="page-heading collection-heading">
    <div>
        <div class="eyebrow">PROJECT WORK</div>
        <h1>Tasks &amp; milestones</h1>
        <p>Track assignments, deadlines, and project progress in one place.</p>
    </div>
    @if($managedProjects->isNotEmpty())<button class="btn primary" type="button" data-task-dialog-open="{{ $managedProjects->count() === 1 ? 'task-create-'.$managedProjects->first()->id : 'task-project-picker' }}"><x-icon name="plus" /> Create task</button>@endif
</div>

@if($managedProjects->isNotEmpty())
<div class="task-chair-guide" role="note">
    <span class="task-chair-guide-icon"><x-icon name="checklist" /></span>
    <div><strong>Project Chair tools are active</strong><p>Create a task, assign active members, set the deadline, then monitor acceptance and progress from the project workspace.</p></div>
</div>
@elseif($taskSetupProjects->isNotEmpty())
<div class="task-chair-guide task-chair-guide-pending" role="note">
    <span class="task-chair-guide-icon"><x-icon name="shield" /></span>
    <div><strong>Task creation is not open yet</strong><p>As Project Chair, validate and lock the approved project plan first. Task tools become available when the project reaches Plan Locked or Ongoing.</p></div>
    <a class="btn secondary" href="{{ route('projects.show', $taskSetupProjects->first()) }}"><x-icon name="arrow" /> Open project</a>
</div>
@endif

@if($taskTotal > 0)
<section class="panel task-summary" aria-label="Task summary">
    <div class="task-summary-grid">
        <div><span>{{ $mine ? 'My tasks' : 'Tasks in view' }}</span><strong>{{ $taskTotal }}</strong></div>
        <div><span>Open</span><strong>{{ $taskTotal - $doneTasks }}</strong></div>
        <div><span>Completed</span><strong>{{ $doneTasks }}</strong></div>
        <div><span>Overdue</span><strong @class(['danger-text' => $overdueTasks > 0])>{{ $overdueTasks }}</strong></div>
    </div>
    <div class="task-summary-progress">
        <span>Completion</span>
        <progress max="100" value="{{ $progress }}" aria-label="{{ $progress }} percent of tasks completed"></progress>
        <strong>{{ $progress }}%</strong>
    </div>
</section>
@endif

<form class="list-filters collection-toolbar task-toolbar" method="GET" action="{{ route('tasks') }}">
    <input type="hidden" name="mine" value="{{ $mine ? 1 : 0 }}">
    @if(request()->filled('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
    <label>Search tasks<input name="q" value="{{ request('q') }}" placeholder="Search task titles"></label>
    <label>Project<select name="project"><option value="">All projects</option>@foreach($visibleProjects as $project)<option value="{{ $project->id }}" @selected((string) request('project') === (string) $project->id)>{{ $project->title }}</option>@endforeach</select></label>
    <button class="btn secondary" type="submit"><x-icon name="filter" /> Apply filters</button>
    @if(request()->filled('q') || request()->filled('project'))
        <a class="text-link" href="{{ route('tasks', ['mine' => $mine ? 1 : 0] + array_filter(['status' => request('status')])) }}"><x-icon name="close" /> Clear filters</a>
    @endif
</form>

<section class="panel task-board task-table-panel" aria-label="Task list">
    <div class="panel-heading task-board-heading">
        <div>
            <h2>{{ $mine ? 'My tasks' : 'All tasks' }}</h2>
            <p>{{ number_format($tasks->total()) }} {{ \Illuminate\Support\Str::plural('task', $tasks->total()) }}{{ $tasks->isNotEmpty() ? ' · open work first, then by deadline' : '' }}</p>
        </div>
        <a class="btn secondary" href="{{ route('tasks', ['mine' => $mine ? 0 : 1] + array_filter(['q' => request('q'), 'project' => request('project'), 'status' => request('status')])) }}"><x-icon name="{{ $mine ? 'users' : 'user' }}" /> {{ $mine ? 'View all tasks' : 'View my tasks' }}</a>
    </div>
    @if($taskTotal > 0)
    <nav class="filter-tabs task-status-tabs" aria-label="Filter tasks by status">
        @foreach($statusOptions as $value => $label)
            <a class="{{ request('status', '') === $value ? 'selected' : '' }}" href="{{ route('tasks', $baseFilters + ($value !== '' ? ['status' => $value] : [])) }}" @if(request('status', '') === $value) aria-current="page" @endif>
                {{ $label }} <span>{{ $value === '' ? $taskTotal : (int) ($statusCounts[$value] ?? 0) }}</span>
            </a>
        @endforeach
    </nav>
    @endif

    @if($tasks->isNotEmpty())
        <div class="table-wrap task-table-wrap">
            <table class="task-table">
                <caption class="sr-only">Tasks with project, assignees, chair, deadline, priority, and status</caption>
                <colgroup><col class="task-col-title"><col class="task-col-project"><col class="task-col-assigned"><col class="task-col-chair"><col class="task-col-deadline"><col class="task-col-priority"><col class="task-col-status"><col class="task-col-action"></colgroup>
                <thead>
                    <tr><th scope="col">Task</th><th scope="col">Project</th><th scope="col">Assigned to</th><th scope="col">Chair</th><th scope="col">Deadline</th><th scope="col">Priority</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Action</span></th></tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                        @php
                            $overdue = $task->deadline && $task->deadline->lt(today()) && $task->status !== 'Completed';
                            $tone = match($task->status) { 'Completed' => 'done', 'In Progress' => 'working', 'Blocked' => 'blocked', default => 'todo' };
                            $priorityTone = match($task->priority) { 'Urgent' => 'urgent', 'High' => 'high', 'Medium' => 'medium', 'Low' => 'low', default => 'unset' };
                            $assigneeNames = collect($task->assignees ?? [])->map(fn ($id) => $assignedMembers[$id]->name ?? null)->filter()->implode(', ');
                        @endphp
                        <tr id="task-{{ $task->id }}">
                            <td data-label="Task" class="task-title-cell">
                                <a href="{{ route('projects.show', $task->project_id) }}#task-{{ $task->id }}"><strong>{{ $task->title }}</strong></a>
                                @if($task->milestone)<small>{{ $task->milestone }}</small>@endif
                            </td>
                            <td data-label="Project"><a class="task-project-link" href="{{ route('projects.show', $task->project_id) }}">{{ $task->project?->title ?? 'Project details' }}</a></td>
                            <td data-label="Assigned to">
                                <div class="task-people-list">
                                    @forelse($task->assignees ?? [] as $assigneeId)
                                        @if($assignedMembers->has($assigneeId))
                                            @include('chapter.partials.person-chip', ['person' => $assignedMembers[$assigneeId], 'project' => $task->project])
                                        @endif
                                    @empty
                                        <span class="person-chip-empty">Unassigned</span>
                                    @endforelse
                                </div>
                            </td>
                            <td data-label="Chair">@include('chapter.partials.person-chip', ['person' => $task->project?->chair, 'project' => $task->project])</td>
                            <td data-label="Deadline"><span @class(['danger-text' => $overdue])>{{ $task->deadline?->format('M d, Y') ?? 'No deadline' }}</span>@if($overdue)<small class="task-overdue-label">Overdue</small>@endif</td>
                            <td data-label="Priority"><span class="task-priority priority-{{ $priorityTone }}">{{ $task->priority ?: 'Not set' }}</span></td>
                            <td data-label="Status"><span class="badge task-status task-status-{{ $tone }}">{{ $task->status }}</span></td>
                            <td data-label="Action"><a class="task-open-link" href="{{ route('projects.show', $task->project_id) }}#task-{{ $task->id }}" aria-label="View {{ $task->title }}" data-task-preview data-task-title="{{ $task->title }}" data-task-project="{{ $task->project?->title ?? 'Project' }}" data-task-status="{{ $task->status }}" data-task-tone="{{ $tone }}" data-task-priority="{{ $task->priority ?: 'Not set' }}" data-task-priority-tone="{{ $priorityTone }}" data-task-deadline="{{ $task->deadline?->format('M d, Y') ?? 'Not set' }}" data-task-milestone="{{ $task->milestone ?: 'None' }}" data-task-assignees="{{ $assigneeNames ?: 'Unassigned' }}" data-task-description="{{ $task->description ?: 'No instructions added.' }}" data-task-notes="{{ $task->notes ?: 'No progress note yet.' }}" data-task-evidence="{{ $task->evidence ?: 'No completion evidence yet.' }}"><x-icon name="eye" /></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('chapter.partials.pagination', ['items' => $tasks])
    @else
        <div class="empty-state task-empty-state">
            <span class="task-empty-icon"><x-icon name="checklist" /></span>
            @if($hasFilters)
                <h3>No tasks match these filters</h3>
                <p>Try a different search, project, or status.</p>
                <a class="btn secondary" href="{{ route('tasks', ['mine' => $mine ? 1 : 0]) }}"><x-icon name="close" /> Clear filters</a>
            @elseif($mine)
                <h3>No tasks assigned to you</h3>
                <p>Your assignments will appear here when you are added to a project task.</p>
                <a class="btn secondary" href="{{ route('tasks', ['mine' => 0]) }}"><x-icon name="users" /> View all tasks</a>
            @else
                <h3>No chapter tasks yet</h3>
                <p>Tasks will appear here as chapter projects are planned.</p>
                <a class="btn secondary" href="{{ route('projects') }}"><x-icon name="folder" /> View projects</a>
            @endif
        </div>
    @endif
</section>
<dialog class="task-modal task-preview-modal" id="task-preview-dialog" aria-labelledby="task-preview-title">
    <div class="task-modal-header"><div><span class="eyebrow">TASK DETAILS</span><h2 id="task-preview-title" data-task-preview-title></h2><p data-task-preview-project></p></div><button class="task-modal-close" type="button" data-task-dialog-close aria-label="Close task details"><x-icon name="close" /></button></div>
    <div class="task-modal-body"><div class="task-preview-tags"><span class="badge" data-task-preview-status></span><span class="task-priority" data-task-preview-priority></span></div><dl class="task-preview-facts"><div><dt>Assigned to</dt><dd data-task-preview-assignees></dd></div><div><dt>Deadline</dt><dd data-task-preview-deadline></dd></div><div><dt>Milestone</dt><dd data-task-preview-milestone></dd></div></dl><div class="task-preview-copy"><h3>Instructions and deliverable</h3><p data-task-preview-description></p><h3>Progress update</h3><p data-task-preview-notes></p><h3>Completion evidence</h3><p data-task-preview-evidence></p></div></div>
    <div class="task-modal-actions"><button class="btn secondary" type="button" data-task-dialog-close>Close</button><a class="btn primary" href="{{ route('projects') }}" data-task-preview-link>Open in project <x-icon name="arrow" /></a></div>
</dialog>
@if($managedProjects->count() > 1)<dialog class="task-modal task-picker-modal" id="task-project-picker" aria-labelledby="task-picker-title"><div class="task-modal-header"><div><span class="eyebrow">NEW TASK</span><h2 id="task-picker-title">Choose a project</h2><p>Tasks belong to a project you chair.</p></div><button class="task-modal-close" type="button" data-task-dialog-close aria-label="Close project chooser"><x-icon name="close" /></button></div><div class="task-modal-body task-project-options">@foreach($managedProjects as $managedProject)<button type="button" data-task-dialog-open="task-create-{{ $managedProject->id }}"><x-icon name="briefcase" /><span>{{ $managedProject->title }}</span><x-icon name="arrow" /></button>@endforeach</div></dialog>@endif
@foreach($managedProjects as $project)
    <dialog class="task-modal task-create-modal" id="task-create-{{ $project->id }}" aria-labelledby="task-create-title-{{ $project->id }}">
        <div class="task-modal-header"><div><span class="eyebrow">{{ $project->title }}</span><h2 id="task-create-title-{{ $project->id }}">Create task</h2><p>Set a clear deliverable, owner, deadline, and priority.</p></div><button class="task-modal-close" type="button" data-task-dialog-close aria-label="Close create task form"><x-icon name="close" /></button></div>
        <div class="task-modal-body">@if(old('_task_dialog') === 'task-create-'.$project->id && $errors->any())<div class="alert error" role="alert"><strong>Check the task details:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif @include('chapter.partials.task-form', ['task' => new \App\Models\Task])</div>
    </dialog>
@endforeach
@if(old('_task_dialog'))<span hidden data-task-reopen="{{ old('_task_dialog') }}"></span>@endif
@endsection
