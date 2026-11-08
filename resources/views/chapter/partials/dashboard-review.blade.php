    <section class="panel" id="dashboard-reviews">
        <div class="panel-heading"><div><h2>{{ auth()->user()->role === 'bod' ? 'Board agenda' : 'Review queue' }}</h2><p>{{ auth()->user()->role === 'admin' ? 'Concept decisions, verified final proposals, and submitted completion reports' : (auth()->user()->role === 'treasurer' ? 'Independent budget checks and submitted liquidations' : 'Projects ready for Board deliberation') }}</p></div><a class="text-link" href="{{ route('projects', ['filter' => 'review']) }}"><x-icon name="arrow" /> Open queue</a></div>
        <div class="dashboard-list-scroll" tabindex="0" aria-label="Dashboard items">
        @forelse($pending->take(5) as $project)
            <a class="attention-row" href="{{ route('projects.show', $project) }}">
                <span class="project-mark light"><x-icon name="fileSignature"/></span>
                <span><strong>{{ $project->title }}</strong><small>{{ $project->reference }} &middot; {{ \App\Support\ProjectFlow::statusLabel($project) }}</small></span>
                <span class="arrow">&rarr;</span>
            </a>
        @empty
            <div class="empty-state compact"><h3>No projects awaiting review</h3><p>Review requests will appear here as projects move forward.</p></div>
        @endforelse
        </div>
    </section>
