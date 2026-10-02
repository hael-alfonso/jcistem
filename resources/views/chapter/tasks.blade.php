@extends('layouts.chapter')
@section('title', 'Tasks & milestones')
@section('content')
<div class="tasks-page">
    <div class="page-heading tasks-heading">
        <div>
            <div class="eyebrow">PROJECT WORKSPACE</div>
            <h1>Tasks &amp; milestones</h1>
            <p>Keep work moving with clear owners, deadlines, and project progress.</p>
        </div>
        <a class="btn {{ request()->boolean('mine') ? 'secondary' : 'primary' }}" href="{{ route('tasks', array_filter(['mine' => request()->boolean('mine') ? null : 1, 'q' => request('q'), 'project' => request('project'), 'status' => request('status')])) }}">
            <x-icon name="user"/>{{ request()->boolean('mine') ? 'View all tasks' : 'View my tasks' }}
        </a>
    </div>

    <section class="tasks-overview" aria-label="Task overview">
        <div class="tasks-overview-main">
            <div class="tasks-overview-copy">
                <span class="tasks-overview-eyebrow">WORK IN VIEW</span>
                <h2>{{ $taskTotal }} {{ \Illuminate\Support\Str::plural('task', $taskTotal) }} across your projects</h2>
                <p>{{ $doneTasks }} completed{{ $overdueTasks ? ' &middot; '.$overdueTasks.' past due' : ' &middot; Nothing past due' }}</p>
                <div class="tasks-overview-progress"><progress max="100" value="{{ $progress }}" aria-label="Overall task completion"></progress><span>{{ $progress }}% complete</span></div>
            </div>
            <div class="tasks-completion-ring" style="--task-percent: {{ $progress }}%" aria-label="{{ $progress }} percent complete">
                <div><strong>{{ $progress }}%</strong><span>complete</span></div>
            </div>
        </div>
        @php
            $baseFilters = array_filter(['mine' => request()->boolean('mine') ? 1 : null, 'q' => request('q'), 'project' => request('project')], fn ($value) => $value !== null && $value !== '');
            $statusOptions = [
                ['value' => '', 'label' => 'All tasks', 'count' => $taskTotal, 'tone' => 'all', 'icon' => 'checklist'],
                ['value' => 'To Do', 'label' => 'To do', 'count' => (int) ($statusCounts['To Do'] ?? 0), 'tone' => 'todo', 'icon' => 'clipboardCheck'],
                ['value' => 'In Progress', 'label' => 'In progress', 'count' => (int) ($statusCounts['In Progress'] ?? 0), 'tone' => 'working', 'icon' => 'clock'],
                ['value' => 'Blocked', 'label' => 'Blocked', 'count' => (int) ($statusCounts['Blocked'] ?? 0), 'tone' => 'blocked', 'icon' => 'info'],
                ['value' => 'Completed', 'label' => 'Completed', 'count' => (int) ($statusCounts['Completed'] ?? 0), 'tone' => 'done', 'icon' => 'check'],
            ];
        @endphp
        <nav class="tasks-status-nav" aria-label="Task status">
            @foreach($statusOptions as $option)
                <a class="tasks-status-tile tone-{{ $option['tone'] }} {{ request('status', '') === $option['value'] ? 'is-active' : '' }}"
                    href="{{ route('tasks', $baseFilters + ($option['value'] !== '' ? ['status' => $option['value']] : [])) }}"
                    @if(request('status', '') === $option['value']) aria-current="page" @endif>
                    <span class="tasks-status-icon"><x-icon :name="$option['icon']"/></span>
                    <span class="tasks-status-label">{{ $option['label'] }}</span>
                    <strong>{{ $option['count'] }}</strong>
                </a>
            @endforeach
        </nav>
    </section>

    <form class="tasks-toolbar" method="GET" action="{{ route('tasks') }}">
        @if(request()->boolean('mine'))<input type="hidden" name="mine" value="1">@endif
        @if(request()->filled('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
        <label class="tasks-search"><span class="sr-only">Search tasks</span><x-icon name="search"/><input name="q" value="{{ request('q') }}" placeholder="Search task title"></label>
        <label class="tasks-project-filter"><span class="sr-only">Filter by project</span><select name="project"><option value="">All projects</option>@foreach($visibleProjects as $project)<option value="{{ $project->id }}" @selected((string) request('project') === (string) $project->id)>{{ $project->title }}</option>@endforeach</select></label>
        <button class="btn secondary" type="submit">Apply</button>
        @if(request()->filled('q') || request()->filled('project'))<a class="tasks-clear" href="{{ route('tasks', array_filter(['mine' => request()->boolean('mine') ? 1 : null, 'status' => request('status')])) }}">Clear filters</a>@endif
    </form>

    <section class="tasks-results" aria-label="Task list">
        <div class="tasks-results-heading"><div><h2>{{ request('status') ?: 'All tasks' }}</h2><p>{{ $tasks->total() }} matching {{ \Illuminate\Support\Str::plural('task', $tasks->total()) }} &middot; Open a card for details</p></div></div>
        <div class="tasks-card-grid">
            @forelse($tasks as $task)
                @php
                    $assigned = collect($task->assignees ?? [])->map(fn ($id) => $assigneeNames[$id] ?? null)->filter()->join(', ');
                    $overdue = $task->deadline && $task->deadline->lt(today()) && $task->status !== 'Completed';
                    $soon = $task->deadline && $task->deadline->between(today(), today()->addDays(7)) && $task->status !== 'Completed';
                    $tone = match($task->status) { 'Completed' => 'done', 'In Progress' => 'working', 'Blocked' => 'blocked', default => 'todo' };
                @endphp
                <details class="tasks-card tone-{{ $tone }}" id="task-{{ $task->id }}">
                    <summary>
                        <div class="tasks-card-top"><span class="tasks-card-project">{{ $task->project?->title ?? 'Project' }}</span><span class="tasks-card-status">{{ $task->status }}</span></div>
                        <h3>{{ $task->title }}</h3>
                        <p class="tasks-card-description">{{ \Illuminate\Support\Str::limit($task->description ?: 'Open to see task details and progress.', 110) }}</p>
                        <div class="tasks-card-meta"><span class="{{ $overdue ? 'is-overdue' : ($soon ? 'is-soon' : '') }}"><x-icon name="calendar"/>{{ $overdue ? 'Past due &middot; ' : ($soon ? 'Due soon &middot; ' : 'Due &middot; ') }}{{ $task->deadline?->format('M d, Y') ?? 'No date' }}</span><span><x-icon name="user"/>{{ $assigned ?: 'Unassigned' }}</span></div>
                        <span class="tasks-card-expand">View details <span aria-hidden="true">&darr;</span></span>
                    </summary>
                    <div class="tasks-card-body">
                        <div class="tasks-card-divider"></div>
                        <p class="preserve-lines tasks-full-description">{{ $task->description ?: 'No description has been added yet.' }}</p>
                        <div class="tasks-detail-facts">
                            <div><span>Assigned to</span><strong>{{ $assigned ?: 'Unassigned' }}</strong></div>
                            <div><span>Project chair</span><strong>{{ $task->project?->chair?->name ?? 'Unassigned' }}</strong></div>
                            <div><span>Priority</span><strong>{{ $task->priority }}</strong></div>
                            <div><span>Milestone</span><strong>{{ $task->milestone ?: 'Not set' }}</strong></div>
                        </div>
                        <div class="tasks-project-progress"><div><strong>Project task completion</strong><span>{{ $task->project?->progress ?? 0 }}%</span></div><progress max="100" value="{{ $task->project?->progress ?? 0 }}" aria-label="Project task completion"></progress></div>
                        @if($task->notes)<div class="tasks-detail-note"><strong>Progress notes</strong><p class="preserve-lines">{{ $task->notes }}</p></div>@endif
                        @if($task->evidence)<div class="tasks-detail-note"><strong>Completion evidence</strong><p class="preserve-lines">{{ $task->evidence }}</p></div>@endif
                        <div class="tasks-card-actions"><a class="btn secondary" href="{{ route('projects.show', $task->project_id) }}#tasks">Open project task &rarr;</a></div>
                    </div>
                </details>
            @empty
                <div class="panel empty-state tasks-empty"><h3>No matching tasks</h3><p>Try another search or choose a different status.</p><a class="btn secondary" href="{{ route('tasks') }}">View all tasks</a></div>
            @endforelse
        </div>
        @include('chapter.partials.pagination', ['items' => $tasks])
    </section>
</div>
@endsection