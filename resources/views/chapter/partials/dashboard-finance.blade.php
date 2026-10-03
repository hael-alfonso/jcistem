@if(auth()->user()->role !== 'treasurer')
<div class="stats-grid three" aria-label="Approved project funding summary">
    <x-dashboard-stat label="Allocated funds" :value="'PHP '.number_format($financeCharts['allocated'], 2)" note="Approved projects you can view · all time" icon="wallet" tone="budget" :money="true"/>
    <x-dashboard-stat label="Posted expenses" :value="'PHP '.number_format($financeCharts['spent'], 2)" note="Approved projects · posted debits · all time" icon="receipt" tone="expenses" :money="true"/>
    <x-dashboard-stat label="Allocation balance" :value="'PHP '.number_format($financeCharts['allocated'] - $financeCharts['spent'], 2)" note="Budget minus expenses · not cash on hand" icon="wallet" :tone="$financeCharts['spent'] > $financeCharts['allocated'] ? 'expenses' : 'balance'" :money="true"/>
</div>
@endif
<section class="dashboard-grid chart-grid" aria-label="Financial charts">
    @include('chapter.partials.budget-expense-chart', ['charts' => $financeCharts, 'summary' => true])
    @include('chapter.partials.monthly-expense-chart', ['charts' => $financeCharts])
</section>
