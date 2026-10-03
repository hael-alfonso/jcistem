@php
    $budgetSummary = $summary ?? false;
    $budgetRows = $budgetSummary
        ? ($charts['rows']->isNotEmpty() ? collect([['label' => 'All approved projects', 'allocated' => $charts['allocated'], 'spent' => $charts['spent']]]) : collect())
        : $charts['rows']->sortByDesc('spent')->map(fn ($row) => array_merge($row, ['label' => $row['project']->title]));
    $budgetMaximum = $budgetSummary ? max(1, $charts['allocated'], $charts['spent']) : $charts['maxProjectAmount'];
@endphp
<section class="panel chart-panel budget-panel">
    <div class="panel-heading"><div><h2>Budget vs. expenses</h2><p>{{ $budgetSummary ? 'All-time totals for approved projects you can view' : 'All visible projects · highest spending first' }}</p></div></div>
    @if($budgetRows->isNotEmpty())
        <div class="chart-key"><span><i class="key-blue"></i>Allocated budget</span><span><i class="key-expense"></i>Posted expenses</span></div>
        <div class="budget-comparison" tabindex="0" aria-label="Budget and expense comparison">
            <div class="budget-scale"><span>PHP 0</span><span>PHP {{ number_format($budgetMaximum / 2, 2) }}</span><span>PHP {{ number_format($budgetMaximum, 2) }}</span></div>
            @foreach($budgetRows as $row)
                <div class="budget-comparison-row">
                    <div class="budget-project-label">
                        @if(!$budgetSummary)<a href="{{ route('projects.show', $row['project']) }}#budget">{{ $row['label'] }}</a>@else<strong>{{ $row['label'] }}</strong>@endif
                        @if($row['spent'] > $row['allocated'])<small class="danger-text">Over budget by PHP {{ number_format($row['spent'] - $row['allocated'], 2) }}</small>
                        @endif
                        @if($row['allocated'] > 0)<small>{{ number_format(100 * $row['spent'] / $row['allocated'], 1) }}% spent</small>
                        @else<small>No allocation recorded</small>@endif
                    </div>
                    <div class="budget-measure"><span>Budget</span><div class="budget-measure-track" role="img" aria-label="{{ $row['label'] }} allocated budget: PHP {{ number_format($row['allocated'], 2) }}"><span class="budget-measure-bar allocation" style="width: {{ 100 * $row['allocated'] / $budgetMaximum }}%"></span></div><strong>PHP {{ number_format($row['allocated'], 2) }}</strong></div>
                    <div class="budget-measure"><span>Expenses</span><div class="budget-measure-track" role="img" aria-label="{{ $row['label'] }} posted expenses: PHP {{ number_format($row['spent'], 2) }}"><span class="budget-measure-bar expense" style="width: {{ 100 * $row['spent'] / $budgetMaximum }}%"></span></div><strong>PHP {{ number_format($row['spent'], 2) }}</strong></div>
                    @if($budgetSummary)<div class="budget-balance {{ $row['spent'] > $row['allocated'] ? 'danger-text' : '' }}"><span>Allocation balance</span><strong>PHP {{ number_format($row['allocated'] - $row['spent'], 2) }}</strong></div>@endif
                </div>
            @endforeach
        </div>
        <p class="chart-note">Shared scale from zero. Posted debit entries only. Allocation balance is budget minus expenses, not cash on hand.</p>
    @else
        <div class="empty-state compact"><h3>No project budgets yet</h3><p>Budget and expense comparisons will appear when projects are available.</p></div>
    @endif
</section>
