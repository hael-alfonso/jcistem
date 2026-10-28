    <section class="panel" id="dashboard-reviews">
        <div class="panel-heading"><div><h2>Review queue</h2><p>{{ auth()->user()->role === 'admin' ? 'All submitted concepts, including your own Admin submissions' : (auth()->user()->role === 'treasurer' ? 'Projects waiting for budget feasibility review' : 'Projects ready for BOD deliberation') }}</p></div><a class="text-link" href="{{ route('projects', ['filter' => auth()->user()->role === 'bod' ? 'board' : 'review']) }}"><x-icon name="arrow" /> Open queue</a></div>
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
