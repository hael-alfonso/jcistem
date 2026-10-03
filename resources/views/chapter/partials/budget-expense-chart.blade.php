@php
    $budgetSummary = $summary ?? false;
    $budgetRows = $budgetSummary
        ? ($charts['rows']->isNotEmpty() ? collect([['label' => 'All approved projects', 'allocated' => $charts['allocated'], 'spent' => $charts['spent']]]) : collect())
        : $charts['rows']->map(fn ($row) => array_merge($row, ['label' => $row['project']->title]));
    $budgetMaximum = $budgetSummary ? max(1, $charts['allocated'], $charts['spent']) : $charts['maxProjectAmount'];
@endphp
<section class="panel chart-panel">
    <div class="panel-heading"><div><h2>Budget vs. expenses</h2><p>{{ $budgetSummary ? 'Total allocation compared with posted project expenses' : 'Allocated budget and posted expenses for each project' }}</p></div></div>
    @if($budgetRows->isNotEmpty())
        <div class="chart-key"><span><i style="background: #0876a8"></i>Allocated budget</span><span><i style="background: #36b3af"></i>Posted expenses</span></div>
        <div class="budget-chart-scroll" tabindex="0" aria-label="Budget and expense comparison">
        <div class="budget-columns" style="--budget-groups: {{ $budgetRows->count() }}">
            @foreach($budgetRows as $row)
                <div class="budget-group">
                    <div class="chart-row-label">
                        @if(!$budgetSummary)<a href="{{ route('projects.show', $row['project']) }}#budget">{{ $row['label'] }}</a>@else<strong>{{ $row['label'] }}</strong>@endif
                        @if($row['spent'] > $row['allocated'])<small>Over budget by PHP {{ number_format($row['spent'] - $row['allocated'], 2) }}</small>@endif
                    </div>
                    <div class="budget-bar-pair">
                        <div class="budget-bar-column"><strong>PHP {{ number_format($row['allocated'], 2) }}</strong><div class="budget-bar-track" role="img" aria-label="{{ $row['label'] }} allocated budget: PHP {{ number_format($row['allocated'], 2) }}"><span class="budget-standing-bar blue" style="height: {{ 100 * $row['allocated'] / $budgetMaximum }}%"></span></div><span>Budget</span></div>
                        <div class="budget-bar-column"><strong>PHP {{ number_format($row['spent'], 2) }}</strong><div class="budget-bar-track" role="img" aria-label="{{ $row['label'] }} posted expenses: PHP {{ number_format($row['spent'], 2) }}"><span class="budget-standing-bar teal" style="height: {{ 100 * $row['spent'] / $budgetMaximum }}%"></span></div><span>Expenses</span></div>
                    </div>
                </div>
            @endforeach
        </div>
        </div>
        <p class="chart-note">Both bars use the same scale. Expenses include posted debit entries only.</p>
    @else
        <div class="empty-state compact"><h3>No project budgets yet</h3><p>Budget and expense comparisons will appear when projects are available.</p></div>
    @endif
</section>
