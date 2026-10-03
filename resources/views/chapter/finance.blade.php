@extends('layouts.chapter')
@section('title', 'Financial monitoring')
@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">PROJECT FINANCES</div>
        <h1>Financial monitoring</h1>
        <p>All visible projects. Budgets and posted expenses are all-time totals; monthly spending covers the last six months.</p>
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
<div class="stats-grid three" aria-label="Project financial summary">
    <x-dashboard-stat label="Allocated budget" :value="'PHP '.number_format($allocated, 2)" note="Recorded allocations · all visible projects" icon="wallet" tone="budget" :money="true"/>
    <x-dashboard-stat label="Posted expenses" :value="'PHP '.number_format($spent, 2)" note="Posted debit entries · all time" icon="receipt" tone="expenses" :money="true"/>
    <x-dashboard-stat label="Allocation balance" :value="'PHP '.number_format($allocated - $spent, 2)" note="Budget minus expenses · not cash on hand" icon="wallet" :tone="$spent > $allocated ? 'expenses' : 'balance'" :money="true"/>
</div>
<div class="dashboard-grid chart-grid finance-chart-grid">
    @include('chapter.partials.budget-expense-chart', ['summary' => false])
    @include('chapter.partials.monthly-expense-chart')
</div>
<section class="panel">
    <div class="panel-heading"><div><h2>Project fund utilization</h2><p>Exact amounts behind the charts</p></div></div>
    <div class="table-wrap">
        <table>
            <caption class="sr-only">Project budgets and recorded use</caption>
            <thead><tr><th>Project</th><th>Proposed</th><th>Allocated</th><th>Spent</th><th>Allocation balance</th><th>Utilization</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><a href="{{ route('projects.show', $row['project']) }}#budget"><strong>{{ $row['project']->title }}</strong><small>{{ $row['project']->reference }}</small></a></td>
                        <td>₱{{ number_format($row['project']->proposed_budget, 2) }}</td>
                        <td>₱{{ number_format($row['allocated'], 2) }}</td>
                        <td>₱{{ number_format($row['spent'], 2) }}</td>
                        <td @class(['danger-text' => $row['remaining'] < 0])>PHP {{ number_format($row['remaining'], 2) }}</td>
                        <td>@if($row['allocated'] > 0)<progress aria-label="{{ $row['project']->title }} budget utilization" max="100" value="{{ min(100, $row['utilization']) }}"></progress>{{ number_format($row['utilization'], 1) }}%@else<span>No allocation</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state"><h3>No project budgets yet.</h3><p>The Treasurer can record allocations after final project approval.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
