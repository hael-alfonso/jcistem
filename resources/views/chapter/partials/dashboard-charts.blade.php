@php
    $progressProjects = $projects->whereIn('status', \App\Models\Project::APPROVED)->sortBy('progress');
@endphp
<section class="dashboard-grid chart-grid" aria-label="Project progress and reviews">
    <div class="panel chart-panel">
        <div class="panel-heading"><div><h2>Project progress</h2><p>Approved projects, from least to most complete</p></div></div>
        @if($progressProjects->isNotEmpty())
            <div class="chart-list">
                @foreach($progressProjects as $project)
                    <div class="chart-row">
                        <div class="chart-row-label"><a href="{{ route('projects.show', $project) }}">{{ $project->title }}</a><strong>{{ $project->progress }}%</strong></div>
                        <div class="chart-track" role="progressbar" aria-label="{{ $project->title }} progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $project->progress }}"><span class="chart-fill blue" style="width: {{ $project->progress }}%"></span></div>
                    </div>
                @endforeach
            </div>
            <p class="chart-note">Progress reflects completed tasks on each project.</p>
        @else
            <div class="empty-state compact"><h3>No approved projects yet</h3><p>Progress bars will appear after project approval.</p></div>
        @endif
    </div>
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
</section>
