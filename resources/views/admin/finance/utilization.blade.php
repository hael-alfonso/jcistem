@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Fund Utilization</h1>
        <p>Monitor allocated, used, remaining, utilization percentage and variance-related values.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('admin.finance') }}"><x-icon name="arrow" /> Back</a>
    </div>
</div>

<x-card title="Utilization chart" subtitle="Used funds versus remaining allocation">
    <div class="chart-box chart-box-lg"><canvas data-chart="budget" data-payload='@json($chart)'></canvas></div>
</x-card>

<x-card title="Utilization by project" subtitle="Remaining Funds = Approved Allocation − Actual Expenses">
    <div class="table-scroll">
        <table>
            <thead><tr><th>Project</th><th>Target / Proposed</th><th>Approved</th><th>Actual expenses</th><th>Remaining</th><th>Utilization</th><th>Unallocated</th></tr></thead>
            <tbody>
            @foreach ($projects as $p)
                <tr>
                    <td><strong>{{ $p['title'] }}</strong><span class="muted">{{ $p['status'] }}</span></td>
                    <td>{{ \App\Support\JciDemoData::money($p['targetBudget']) }}</td>
                    <td>{{ \App\Support\JciDemoData::money($p['approvedBudget']) }}</td>
                    <td>{{ \App\Support\JciDemoData::money($p['usedFunds']) }}</td>
                    <td>{{ \App\Support\JciDemoData::money(max(0, $p['approvedBudget'] - $p['usedFunds'])) }}</td>
                    <td>{{ number_format(($p['usedFunds'] / max(1, $p['approvedBudget'])) * 100, 1) }}%</td>
                    <td>{{ \App\Support\JciDemoData::money(max(0, $p['targetBudget'] - $p['approvedBudget'])) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>
<div class="warning-note"><x-icon name="shield" /> Expenses exceeding the available allocation should be flagged. In production, totals must be server-validated.</div>
@endsection
