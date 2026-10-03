@php $taskTotal = $taskStatuses->sum(); @endphp
<section class="dashboard-grid chart-grid" aria-label="My tasks">
    @include('chapter.partials.dashboard-task-list')
    <div class="panel chart-panel">
        <div class="panel-heading"><div><h2>My task status</h2><p>All tasks assigned to you on visible projects</p></div><span class="chart-count">{{ $taskTotal }} total</span></div>
        @if($taskTotal)
            @include('chapter.partials.pie-chart', ['segments' => $taskStatuses->map(fn ($count, $label) => ['label' => $label, 'value' => $count, 'color' => match($label) { 'Completed' => '#267344', 'Blocked' => '#ae462f', 'In Progress' => '#176a9b', default => '#7052a8' }])->values(), 'center' => $taskTotal, 'caption' => 'tasks', 'format' => 'count'])
            <p class="chart-note">Includes completed tasks across projects you can view.</p>
        @else
            <div class="empty-state compact"><h3>No assigned tasks yet</h3><p>Your task status breakdown will appear when work is assigned.</p></div>
        @endif
    </div>
</section>
