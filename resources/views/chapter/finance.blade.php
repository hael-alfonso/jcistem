@extends('layouts.chapter')
@section('title', 'Financial monitoring')
@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">RESOURCES WITH PURPOSE</div>
        <h1>Financial monitoring</h1>
        <p>Track approved allocations and posted project expenses.</p>
    </div>
    @if(auth()->user()->role === 'treasurer')
        <a class="btn primary" href="{{ route('ledger') }}">Open Treasurer ledger →</a>
    @endif
</div>

@php
    $allocated = $charts['allocated'];
    $spent = $charts['spent'];
    $rows = $charts['rows'];
@endphp
<div class="stats-grid three">
    <div class="stat-card"><span>Total allocation</span><strong class="money">₱{{ number_format($allocated, 2) }}</strong><small>Approved project budget allocations</small></div>
    <div class="stat-card"><span>Actual expenses</span><strong class="money">₱{{ number_format($spent, 2) }}</strong><small>Posted project ledger debits</small></div>
    <div class="stat-card"><span>Remaining funds</span><strong class="money">₱{{ number_format($allocated - $spent, 2) }}</strong><small>{{ $allocated > 0 ? number_format(100 * $spent / $allocated, 1) : '0.0' }}% of allocation spent</small></div>
</div>

<div class="dashboard-grid chart-grid finance-chart-grid">
    <section class="panel chart-panel">
        <div class="panel-heading">
            <div><h2>Allocation and spending by project</h2><p>Every approved project on a shared peso scale</p></div>
        </div>
        @if($rows->isNotEmpty())
            <div class="chart-key"><span><i class="key-blue"></i>Allocation</span><span><i class="key-teal"></i>Posted expenses</span></div>
            <div class="project-finance-chart">
                @foreach($rows->sortByDesc('allocated') as $row)
                    <div class="finance-chart-row">
                        <div class="chart-row-label">
                            <a href="{{ route('projects.show', $row['project']) }}#budget">{{ $row['project']->title }}</a>
                            <small>{{ $row['project']->reference }}</small>
                        </div>
                        <div class="finance-bar-line"><span>Allocation</span><div class="chart-track"><span class="chart-fill blue" style="width: {{ 100 * $row['allocated'] / $charts['maxProjectAmount'] }}%" aria-hidden="true"></span></div><strong>₱{{ number_format($row['allocated'], 2) }}</strong></div>
                        <div class="finance-bar-line"><span>Expenses</span><div class="chart-track"><span class="chart-fill teal" style="width: {{ 100 * $row['spent'] / $charts['maxProjectAmount'] }}%" aria-hidden="true"></span></div><strong>₱{{ number_format($row['spent'], 2) }}</strong></div>
                    </div>
                @endforeach
            </div>
            <p class="chart-note">Only posted debit entries count as expenses. Void entries are excluded.</p>
        @else
            <div class="empty-state compact"><h3>No approved projects yet</h3><p>Allocations and spending will appear after a project is approved.</p></div>
        @endif
    </section>
    <section class="panel chart-panel">
        <div class="panel-heading">
            <div><h2>Expense trend</h2><p>Last six calendar months, through today</p></div>
        </div>
        <div class="monthly-chart" aria-label="Posted project expenses by transaction month">
            @foreach($charts['months'] as $month)
                <div class="month-column">
                    <strong class="month-value">₱{{ number_format($month['amount'], 0) }}</strong>
                    <div class="month-track"><span class="month-bar" style="height: {{ 100 * $month['amount'] / $charts['maxMonthlyAmount'] }}%" aria-hidden="true"></span></div>
                    <span class="month-label">{{ $month['label'] }}</span>
                </div>
            @endforeach
        </div>
        <p class="chart-note">Grouped by transaction date. Posted project ledger debits only; zero months are shown.</p>
    </section>
</div>

<section class="panel">
    <div class="panel-heading"><div><h2>Project fund utilization</h2><p>Exact amounts behind the charts</p></div></div>
    <div class="table-wrap">
        <table>
            <caption class="sr-only">Approved project budgets and posted expenses</caption>
            <thead><tr><th>Project</th><th>Proposed</th><th>Allocated</th><th>Spent</th><th>Remaining</th><th>Utilization</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><a href="{{ route('projects.show', $row['project']) }}#budget"><strong>{{ $row['project']->title }}</strong><small>{{ $row['project']->reference }}</small></a></td>
                        <td>₱{{ number_format($row['project']->proposed_budget, 2) }}</td>
                        <td>₱{{ number_format($row['allocated'], 2) }}</td>
                        <td>₱{{ number_format($row['spent'], 2) }}</td>
                        <td>₱{{ number_format($row['remaining'], 2) }}</td>
                        <td><progress max="100" value="{{ min(100, $row['utilization']) }}"></progress>{{ number_format($row['utilization'], 1) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state"><h3>No approved project budgets yet.</h3><p>The Treasurer can record allocations after final project approval.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
