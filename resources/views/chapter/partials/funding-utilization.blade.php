@php
    $fundAllocation = $charts['allocated'];
    $fundSpending = $charts['spent'];
    $fundRemaining = max(0, $fundAllocation - $fundSpending);
@endphp
<section class="panel chart-panel funding-utilization-panel">
    <div class="panel-heading"><div><h2>Budget utilization</h2><p>Approved project allocations · all time</p></div><a class="text-link" href="{{ route('finance') }}">Details &rarr;</a></div>
    @if($fundAllocation > 0)
        @include('chapter.partials.pie-chart', ['segments' => collect([
            ['label' => 'Used allocation', 'value' => min($fundSpending, $fundAllocation), 'color' => '#c46045'],
            ['label' => 'Unspent allocation', 'value' => $fundRemaining, 'color' => '#267344'],
        ]), 'center' => number_format(100 * $fundSpending / $fundAllocation, 1).'%', 'caption' => 'budget spent', 'format' => 'money'])
    @else
        <div class="empty-state compact"><h3>No project budgets yet</h3><p>No approved project allocations have been recorded.</p></div>
    @endif
    <p class="chart-note">Posted expenses: PHP {{ number_format($fundSpending, 2) }}.
        @if($fundSpending > $fundAllocation)<strong class="danger-text">Over budget by PHP {{ number_format($fundSpending - $fundAllocation, 2) }}.</strong> The pie represents the allocation; excess spending is shown separately.
        @else Balance is allocation minus expenses, not cash on hand.@endif
    </p>
</section>
