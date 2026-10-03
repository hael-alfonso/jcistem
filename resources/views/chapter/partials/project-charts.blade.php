<section class="dashboard-grid chart-grid" aria-label="Project charts">
    @if($showStatus ?? true)
    <div class="panel chart-panel">
        <div class="panel-heading"><div><h2>Projects by status</h2><p>Number of projects in each stage</p></div><span class="chart-count">{{ $charts['total'] }} total</span></div>
        @if($charts['total'])
            @include('chapter.partials.count-chart', ['segments' => $charts['statuses']->map(fn ($count, $label) => ['label' => $label, 'value' => $count, 'color' => \App\Models\Project::colorForStatus($label)])->values(), 'center' => $charts['total'], 'caption' => 'projects', 'format' => 'count'])
            <p class="chart-note">Each project appears once under its current status.</p>
        @else
            <div class="empty-state compact"><h3>No project data yet</h3><p>Project stages will appear when projects are added.</p></div>
        @endif
    </div>
    @endif
    <div class="panel chart-panel">
        <div class="panel-heading"><div><h2>Projects by focus area</h2><p>Where chapter work is concentrated</p></div><span class="chart-count">{{ $charts['total'] }} total</span></div>
        @if($charts['total'])
            @include('chapter.partials.count-chart', ['segments' => $charts['areas']->map(fn ($count, $label) => ['label' => $label, 'value' => $count])->values(), 'center' => $charts['total'], 'caption' => 'projects', 'format' => 'count'])
            <p class="chart-note">Colors match the categories listed beside each chart.</p>
        @else
            <div class="empty-state compact"><h3>No area data yet</h3><p>Focus area shares will appear when projects are added.</p></div>
        @endif
    </div>
</section>
