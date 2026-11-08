@php
    $visibleCount = $projects->count();
    $allocated = (float) $financeCharts['allocated'];
    $spent = (float) $financeCharts['spent'];
    $overBudgetAmount = max(0, $spent - $allocated);
    $overBudgetProjects = $financeCharts['rows']->filter(fn ($row) => $row['spent'] > $row['allocated'])->count();
    $budgetRows = $financeCharts['rows']
        ->filter(fn ($row) => $row['allocated'] > 0 || $row['spent'] > 0)
        ->sortByDesc(fn ($row) => max($row['allocated'], $row['spent']))
        ->take(4)
        ->values();
    $budgetScale = max(1, (float) $budgetRows->max(fn ($row) => max($row['allocated'], $row['spent'])));
@endphp
<div class="dashboard-monitor-grid" aria-label="Project and funds monitor">
    <section class="panel dashboard-monitor-panel" aria-labelledby="dashboard-stage-title">
        <div class="panel-heading"><div><h2 id="dashboard-stage-title">Projects at a glance</h2><p>{{ $visibleCount }} chapter {{ \Illuminate\Support\Str::plural('project', $visibleCount) }} visible to you</p></div><a class="text-link" href="{{ route('projects') }}">Open projects <x-icon name="arrow" /></a></div>
        @if($visibleCount)
            <div class="dashboard-stage-chart">
                @include('chapter.partials.pie-chart', [
                    'segments' => $monitorStages->map(fn ($stage) => ['label' => $stage['label'], 'value' => $stage['count'], 'color' => $stage['color']]),
                    'center' => $visibleCount,
                    'caption' => 'projects',
                    'format' => 'count',
                ])
            </div>
            <p class="chart-note">Each visible project appears once in its current stage.</p>
        @else
            <div class="empty-state compact"><h3>No project data yet</h3><p>Project stages will appear here as chapter work starts.</p></div>
        @endif
    </section>
    <section class="panel dashboard-monitor-panel" aria-labelledby="dashboard-funds-title">
        <div class="panel-heading"><div><h2 id="dashboard-funds-title">Project funds at a glance</h2><p>Approved projects visible to you</p></div><a class="text-link" href="{{ route('finance') }}">Open finance <x-icon name="arrow" /></a></div>
        @if($budgetRows->isNotEmpty())
            <div class="dashboard-project-bars" role="group" aria-label="Approved allocation and posted expenses by project">
                <div class="dashboard-bar-legend" aria-hidden="true"><span class="is-allocation">Approved allocation</span><span class="is-spent">Posted expenses</span></div>
                <div class="dashboard-bar-list">
                    @foreach($budgetRows as $row)
                        @php
                            $allocationHeight = 100 * $row['allocated'] / $budgetScale;
                            $spentHeight = 100 * $row['spent'] / $budgetScale;
                        @endphp
                        <article class="dashboard-bar-row {{ $row['spent'] > $row['allocated'] ? 'is-over' : '' }}">
                            <div class="dashboard-bar-plot" role="img" aria-label="{{ $row['project']->title }}: approved allocation PHP {{ number_format($row['allocated'], 2) }}, posted expenses PHP {{ number_format($row['spent'], 2) }}">
                                <span class="dashboard-bar-column is-allocation" style="height: {{ round($allocationHeight, 2) }}%"></span>
                                <span class="dashboard-bar-column is-spent" style="height: {{ round($spentHeight, 2) }}%"></span>
                            </div>
                            <a href="{{ route('projects.show', $row['project']) }}#budget" title="{{ $row['project']->title }}">{{ $row['project']->title }}</a>
                            <div class="dashboard-bar-values"><span class="is-allocation">PHP {{ number_format($row['allocated'], 2) }}</span><span class="is-spent">PHP {{ number_format($row['spent'], 2) }}</span></div>
                        </article>
                    @endforeach
                </div>
            </div>
            @if($overBudgetAmount > 0)<p class="dashboard-fund-warning">Across approved projects, posted expenses exceed allocation by PHP {{ number_format($overBudgetAmount, 2) }}.</p>@endif
            @if($overBudgetAmount <= 0 && $overBudgetProjects > 0)<p class="dashboard-fund-warning">{{ $overBudgetProjects }} {{ \Illuminate\Support\Str::plural('project', $overBudgetProjects) }} over budget. Review project details before allocating more funds.</p>@endif
        @else
            <div class="empty-state compact"><h3>No project budgets yet</h3><p>Approved allocations and posted expenses will appear here.</p></div>
        @endif
        <p class="chart-note">Shows up to four projects with the highest budget activity. Expenses count posted debits only.</p>
    </section>
</div>
