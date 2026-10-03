    <section class="panel">
        <div class="panel-heading"><div><h2>My next steps</h2><p>Open tasks ordered by deadline</p></div><a class="text-link" href="{{ route('tasks', ['mine' => 1]) }}">View all &rarr;</a></div>
        <div class="dashboard-list-scroll" tabindex="0" aria-label="Dashboard items">
        @forelse($tasks->take(5) as $task)
            <a class="attention-row" href="{{ route('projects.show', $task->project_id) }}#tasks">
                <span class="task-dot {{ $task->deadline?->lt(today()) ? 'overdue' : '' }}"></span>
                <span><strong>{{ $task->title }}</strong><small>{{ $task->deadline?->format('M d, Y') ?? 'No deadline' }} &middot; {{ $task->status }}</small></span>
            </a>
        @empty
            <div class="empty-state compact"><x-icon name="checklist"/><h3>You're all caught up.</h3><p>Your next assigned task will appear here.</p></div>
        @endforelse
        </div>
    </section>
