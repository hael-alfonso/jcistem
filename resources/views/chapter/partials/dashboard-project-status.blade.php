<section class="panel chart-panel">
    <div class="panel-heading"><div><h2>Projects by status</h2><p>Chapter projects by their current stage</p></div><a class="text-link" href="{{ route('projects') }}">All projects &rarr;</a></div>
    @if($charts['total'])
        @include('chapter.partials.pie-chart', ['segments' => $charts['statuses']->map(fn ($count, $label) => ['label' => $label, 'value' => $count, 'color' => \App\Models\Project::colorForStatus($label)])->values(), 'center' => $charts['total'], 'caption' => 'projects', 'format' => 'count'])
    @else
        <div class="empty-state compact"><h3>No project data yet</h3><p>Project stages will appear when projects are added.</p></div>
    @endif
</section>
