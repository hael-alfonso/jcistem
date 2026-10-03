@if(auth()->user()->role !== 'treasurer')
<div class="stats-grid three" aria-label="Project funding summary">
    <div class="stat-card"><span>Allocated funds</span><strong class="money">PHP {{ number_format($financeCharts['allocated'], 2) }}</strong><small>Approved projects you can view</small><x-icon name="wallet"/></div>
    <div class="stat-card"><span>Posted spending</span><strong class="money">PHP {{ number_format($financeCharts['spent'], 2) }}</strong><small>Recorded project expenses</small><x-icon name="chart"/></div>
    <div class="stat-card"><span>Remaining funds</span><strong class="money">PHP {{ number_format($financeCharts['allocated'] - $financeCharts['spent'], 2) }}</strong><small>Allocated funds minus posted spending</small><x-icon name="wallet"/></div>
</div>
@endif
    <section class="dashboard-grid chart-grid" aria-label="Financial charts">
        @include('chapter.partials.budget-expense-chart', ['charts' => $financeCharts, 'summary' => true])
        <div class="panel chart-panel">
            <div class="panel-heading"><div><h2>Project spending</h2><p>Posted expenses in each of the last six months</p></div><a class="text-link" href="{{ route('finance') }}">Financial overview &rarr;</a></div>
            <div class="monthly-chart" aria-label="Monthly posted project expenses">
                @foreach($financeCharts['months'] as $month)
                    <div class="month-column">
                        <strong class="month-value">PHP {{ number_format($month['amount'], 0) }}</strong>
                        <div class="month-track"><span class="month-bar" style="height: {{ 100 * $month['amount'] / $financeCharts['maxMonthlyAmount'] }}%" aria-hidden="true"></span></div>
                        <span class="month-label">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <p class="chart-note">Posted debit entries for approved projects, grouped by transaction date. Months without spending show zero.</p>
        </div>
    </section>
