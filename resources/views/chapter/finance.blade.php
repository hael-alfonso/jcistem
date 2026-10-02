@extends('layouts.chapter')
@section('title', 'Financial monitoring')
@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">RESOURCES WITH PURPOSE</div>
        <h1>Financial monitoring</h1>
        <p>Track the chapter funding plan and recorded project use.</p>
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
    <div class="stat-card"><span>Estimated total funds</span><strong class="money">₱{{ number_format($allocated, 2) }}</strong><small>Budget planned across projects</small></div>
    <div class="stat-card"><span>Used funds</span><strong class="money">₱{{ number_format($spent, 2) }}</strong><small>Recorded project use</small></div>
    <div class="stat-card"><span>Remaining funds</span><strong class="money">₱{{ number_format($allocated - $spent, 2) }}</strong><small>{{ $allocated > 0 ? number_format(100 * $spent / $allocated, 1) : '0.0' }}% of allocation spent</small></div>
</div>

<div class="dashboard-grid chart-grid finance-chart-grid">
    <section class="panel chart-panel">
        <div class="panel-heading"><div><h2>Allocation by project</h2><p>Share of the total project funding plan</p></div></div>
        @php $funded = $rows->filter(fn ($row) => $row['allocated'] > 0); @endphp
        @if($funded->isNotEmpty())
            @include('chapter.partials.pie-chart', ['segments' => $funded->sortByDesc('allocated')->map(fn ($row) => ['label' => $row['project']->title, 'value' => $row['allocated']])->values(), 'center' => 'PHP '.number_format($allocated, 0), 'caption' => 'allocated', 'format' => 'money'])
            <p class="chart-note">The pie shows each project's share of the funding plan. Open a project below for its spending details.</p>
        @else
            <div class="empty-state compact"><h3>No allocations recorded yet</h3><p>The Treasurer can add allocations for each project.</p></div>
        @endif
    </section>
    <section class="panel chart-panel">
        <div class="panel-heading">
            <div><h2>Expenses over time</h2><p>Posted spending in each of the last six months</p></div>
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
        <p class="chart-note">Grouped by transaction date. Recorded project use only; zero months are shown.</p>
    </section>
</div>

<section class="panel">
    <div class="panel-heading"><div><h2>Project fund utilization</h2><p>Exact amounts behind the charts</p></div></div>
    <div class="table-wrap">
        <table>
            <caption class="sr-only">Project budgets and recorded use</caption>
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
                    <tr><td colspan="6"><div class="empty-state"><h3>No project budgets yet.</h3><p>The Treasurer can record allocations after final project approval.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
