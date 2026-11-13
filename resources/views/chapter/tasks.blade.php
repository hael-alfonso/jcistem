@extends('layouts.chapter')
@section('title', 'Tasks')
@section('content')
@php
    $mine = request()->boolean('mine');
    $statusOptions = ['' => 'All', 'To Do' => 'To do', 'In Progress' => 'In progress', 'Blocked' => 'Blocked', 'Completed' => 'Completed'];
    $createProjects = request()->filled('project') ? $managedProjects->where('id', request()->integer('project')) : $managedProjects;
    $setupProjects = request()->filled('project') ? $taskSetupProjects->where('id', request()->integer('project')) : $taskSetupProjects;
    $hasFilters = request()->filled('q') || request()->filled('project') || request()->filled('status') || request()->filled('assignee') || request()->filled('from') || request()->filled('to');
@endphp

<div class="page-heading collection-heading">
    <div>
        <div class="eyebrow">PROJECT WORK</div>
        <h1>{{ $mine ? 'My Tasks' : 'Task Monitoring' }}</h1>
        <p>{{ $mine ? 'See your assignments, deadlines, and next steps across projects.' : 'See project assignments and progress in one place.' }}</p>
    </div>
    @if($createProjects->isNotEmpty())<button class="btn primary" type="button" data-task-dialog-open="{{ $createProjects->count() === 1 ? 'task-create-'.$createProjects->first()->id : 'task-project-picker' }}"><x-icon name="plus" /> New Task</button>@endif
</div>

@if($selectedProject)
<div class="task-chair-guide task-selected-project" role="note">
    <span class="task-chair-guide-icon"><x-icon name="briefcase" /></span>
    <div><strong>{{ $selectedProject->title }}</strong><p>Showing this project's tasks and deadlines.</p></div>
    <a class="btn secondary" href="{{ route('projects.show', $selectedProject) }}#tasks">Open project workspace</a>
</div>
@elseif($createProjects->isEmpty() && $setupProjects->isNotEmpty())
<div class="task-chair-guide task-chair-guide-pending" role="note">
    <span class="task-chair-guide-icon"><x-icon name="shield" /></span>
    <div><strong>Task creation is not open yet</strong><p>Lock the approved project plan before assigning tasks.</p></div>
    <a class="btn secondary" href="{{ route('projects.show', $setupProjects->first()) }}"><x-icon name="arrow" /> Open project</a>
</div>
@endif

@if($taskTotal > 0)
<section class="task-health-strip" aria-label="Task progress">
    <div class="task-health-progress">
        <span>Overall completion</span>
        <progress max="100" value="{{ $progress }}" aria-label="{{ $progress }} percent of tasks completed"></progress>
        <strong>{{ $progress }}%</strong>
    </div>
    <div class="task-health-overdue"><span>Overdue</span><strong @class(['danger-text' => $overdueTasks > 0])>{{ $overdueTasks }}</strong></div>
</section>
@endif

<form class="list-filters collection-toolbar task-toolbar" method="GET" action="{{ route('tasks') }}">
    <input type="hidden" name="mine" value="{{ $mine ? 1 : 0 }}">
    <label class="task-search-field">Find task<input name="q" value="{{ request('q') }}" placeholder="Task or project title"></label>
    <label>Project<select name="project"><option value="">All projects</option>@foreach($visibleProjects as $project)<option value="{{ $project->id }}" @selected((string) request('project') === (string) $project->id)>{{ $project->title }}</option>@endforeach</select></label>
    <label>Task status<select name="status">@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected((string) request('status', '') === (string) $value)>{{ $label }}</option>@endforeach</select></label>
    @unless($mine)<label>Assigned to<select name="assignee"><option value="">Anyone</option>@foreach($assignableMembers as $person)<option value="{{ $person->id }}" @selected((string) request('assignee') === (string) $person->id)>{{ $person->name }}</option>@endforeach</select></label>@endunless
    <label>Due from month<input type="month" name="from" value="{{ request('from') }}"></label>
    <label>Due to month<input type="month" name="to" value="{{ request('to') }}"></label>
    <div class="task-filter-actions"><button class="btn secondary" type="submit"><x-icon name="filter" /> Apply</button>
    @if(request()->filled('q') || request()->filled('project') || request()->filled('status') || request()->filled('assignee') || request()->filled('from') || request()->filled('to'))
        <a class="text-link" href="{{ route('tasks', ['mine' => $mine ? 1 : 0]) }}"><x-icon name="close" /> Clear filters</a>
    @endif</div>
</form>

<section class="panel task-board task-table-panel" aria-label="Task list">
    <div class="panel-heading task-board-heading">
        <div>
            <h2>Task list</h2>
            <p>{{ number_format($tasks->total()) }} {{ \Illuminate\Support\Str::plural('task', $tasks->total()) }} {{ $mine ? 'assigned to you' : 'in this view' }}</p>
        </div>
        <a class="btn secondary" href="{{ route('tasks', ['mine' => $mine ? 0 : 1] + array_filter(['q' => request('q'), 'project' => request('project'), 'status' => request('status'), 'from' => request('from'), 'to' => request('to')])) }}"><x-icon name="{{ $mine ? 'users' : 'user' }}" /> {{ $mine ? 'View All Tasks' : 'View My Tasks' }}</a>
    </div>
    @if($tasks->isNotEmpty())
        <div class="task-list-table-wrap">
            <table class="task-list-table" aria-label="Tasks">
                <thead><tr><th scope="col">Task and project</th><th scope="col">Assigned to</th><th scope="col">Due</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                @foreach($tasks as $task)
                    @php
                        $overdue = $task->deadline && $task->deadline->lt(today()) && $task->status !== 'Completed';
                        $tone = match($task->status) { 'Completed' => 'done', 'In Progress' => 'working', 'Blocked' => 'blocked', default => 'todo' };
                        $priorityTone = match($task->priority) { 'Urgent' => 'urgent', 'High' => 'high', 'Medium' => 'medium', 'Low' => 'low', default => 'unset' };
                        $assigneeNames = collect($task->assignees ?? [])->map(fn ($id) => $assignedMembers[$id]->name ?? 'Former member')->implode(', ');
                        $isMyTask = in_array(auth()->id(), $task->assignees ?? [], true);
                        $myResponse = $task->assignment_responses[auth()->id()]['response'] ?? null;
                        $readyToFinish = $isMyTask && $myResponse === 'accepted' && $task->project?->status === 'Ongoing'
                            && $task->project->plan_locked_at && $task->status !== 'Completed'
                            && $task->project->tasks->whereIn('id', $task->dependencies ?? [])->where('status', 'Completed')->count() === count($task->dependencies ?? []);
                        $canRemoveTask = auth()->user()->role === 'admin' && $task->project?->plan_locked_at
                            && in_array($task->project->status, ['Plan Locked', 'Ongoing'], true)
                            && !$task->project->tasks->contains(fn ($other) => $other->id !== $task->id && in_array($task->id, array_map('intval', $other->dependencies ?? []), true));
                    @endphp
                    <tr id="task-{{ $task->id }}">
                        <td data-label="Task"><div class="task-row-main"><a class="task-row-title" href="{{ route('projects.show', $task->project_id) }}#task-{{ $task->id }}">{{ $task->title }}</a><a class="task-row-project" href="{{ route('projects.show', $task->project_id) }}"><x-icon name="briefcase" />{{ $task->project?->title ?? 'Project details' }}</a><span class="task-row-next">{{ $task->nextStep(auth()->user()) }}</span></div></td>
                        <td data-label="Assigned to"><div class="task-assignee-identity"><div class="task-assignee-avatars" role="img" aria-label="Assigned members: {{ $assigneeNames ?: 'Unassigned' }}">
                            @forelse(array_slice($task->assignees ?? [], 0, 3) as $assigneeId)
                                @php $person = $assignedMembers[$assigneeId] ?? null; @endphp
                                <span class="task-assignee-avatar" title="{{ $person?->name ?? 'Former member' }}" aria-hidden="true">@if($person && $person->status === 'active' && !empty($person->profile['photo_path']))<img src="{{ route('members.photo', $person) }}?v={{ $person->updated_at?->timestamp }}" alt="" loading="lazy" decoding="async">@else{{ strtoupper(mb_substr($person?->name ?? '?', 0, 1)) }}@endif</span>
                            @empty
                                <span class="task-assignee-avatar is-empty" aria-hidden="true"><x-icon name="user" /></span>
                            @endforelse
                            @if(count($task->assignees ?? []) > 3)<span class="task-assignee-extra" aria-hidden="true">+{{ count($task->assignees) - 3 }}</span>@endif
                            </div><span class="task-assignee-name">{{ $assigneeNames ?: 'Unassigned' }}</span></div></td>
                        <td data-label="Due"><span @class(['task-row-date', 'danger-text' => $overdue])>{{ $task->deadline?->format('M d, Y') ?? 'No deadline' }}@if($overdue)<small>Overdue</small>@endif</span></td>
                        <td data-label="Status"><span class="badge task-status task-status-{{ $tone }}">{{ $task->status }}</span><small class="task-row-priority priority-{{ $priorityTone }}">{{ $task->priority ?: 'No' }} priority</small></td>
                        <td data-label="Actions">
                            <div class="task-row-actions">
                                @if($readyToFinish)
                                    <a class="task-row-finish" href="{{ route('projects.show', $task->project_id) }}#task-{{ $task->id }}" aria-label="Open completion form for {{ $task->title }}"><x-icon name="check" /> Complete task</a>
                                @endif
                                @include('chapter.partials.task-preview-link')
                                @if($canRemoveTask)<form method="POST" action="{{ route('tasks.destroy', [$task->project, $task]) }}">@csrf @method('DELETE')<button class="task-row-remove" type="submit" aria-label="Remove task: {{ $task->title }}" title="Remove task" data-confirm="Remove this task? It will disappear from active work and project progress, but remain in the audit history and can be restored while the project is active."><x-icon name="trash" /></button></form>@endif
                            </div>
                        </td>
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
    <div class="task-modal-body"><div class="task-preview-tags"><span class="badge" data-task-preview-status></span><span class="task-priority" data-task-preview-priority></span></div><dl class="task-preview-facts"><div><dt>Assigned to</dt><dd><span class="task-preview-assignee-avatars" data-task-preview-avatars></span><span data-task-preview-assignees></span></dd></div><div><dt>Project Chair</dt><dd data-task-preview-chair></dd></div><div><dt>Deadline</dt><dd data-task-preview-deadline></dd></div><div><dt>Milestone</dt><dd data-task-preview-milestone></dd></div></dl><div class="task-preview-copy"><section><h3>Instructions and deliverable</h3><p data-task-preview-description></p></section><section><h3>Progress update</h3><p data-task-preview-notes></p></section><section><h3>Completion evidence</h3><p data-task-preview-evidence></p></section></div></div>
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
