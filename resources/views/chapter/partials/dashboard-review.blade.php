    <section class="panel">
        <div class="panel-heading"><div><h2>{{ auth()->user()->role === 'bod' ? 'My review queue' : 'Review attention' }}</h2><p>{{ auth()->user()->role === 'admin' ? 'Chapter review queues for oversight' : 'Requests matching your reviewer permissions' }}</p></div><a class="text-link" href="{{ route('projects', ['filter' => 'review']) }}">View all &rarr;</a></div>
        <div class="dashboard-list-scroll" tabindex="0" aria-label="Dashboard items">
        @forelse($pending->take(5) as $project)
            <a class="attention-row" href="{{ route('projects.show', $project) }}">
                <span class="project-mark light"><x-icon name="fileSignature"/></span>
                <span><strong>{{ $project->title }}</strong><small>{{ $project->reference }} &middot; {{ $project->status }}</small></span>
                <span class="arrow">&rarr;</span>
            </a>
        @empty
            <div class="empty-state compact"><h3>No projects awaiting review</h3><p>Review requests will appear here as projects move forward.</p></div>
        @endforelse
        </div>
    </section>
