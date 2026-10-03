<div class="dashboard-grid" aria-label="Projects and tasks requiring attention">
    <section class="panel">
        <div class="panel-heading"><div><h2>Review attention</h2><p>Concepts, proposals, and completion requests awaiting review</p></div><a class="text-link" href="{{ route('projects', ['filter' => 'review']) }}">View all &rarr;</a></div>
        @forelse($pending->take(5) as $project)
            <a class="attention-row" href="{{ route('projects.show', $project) }}">
                <span class="project-mark light"><x-icon name="fileSignature"/></span>
                <span><strong>{{ $project->title }}</strong><small>{{ $project->reference }} &middot; {{ $project->status }}</small></span>
                <span class="arrow">&rarr;</span>
            </a>
        @empty
            <div class="empty-state compact"><h3>No projects awaiting review</h3><p>Review requests will appear here as projects move forward.</p></div>
        @endforelse
    </section>
    <section class="panel">
        <div class="panel-heading"><div><h2>My next steps</h2><p>Open tasks ordered by deadline</p></div><a class="text-link" href="{{ route('tasks', ['mine' => 1]) }}">View all &rarr;</a></div>
        @forelse($tasks->take(5) as $task)
            <a class="attention-row" href="{{ route('projects.show', $task->project_id) }}#tasks">
                <span class="task-dot {{ $task->deadline?->lt(today()) ? 'overdue' : '' }}"></span>
                <span><strong>{{ $task->title }}</strong><small>{{ $task->deadline?->format('M d, Y') ?? 'No deadline' }} &middot; {{ $task->status }}</small></span>
            </a>
        @empty
            <div class="empty-state compact"><x-icon name="checklist"/><h3>You're all caught up.</h3><p>Your next assigned task will appear here.</p></div>
        @endforelse
    </section>
</div>
