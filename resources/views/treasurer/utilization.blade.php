@extends('layouts.workspace')
@section('content')
<div class="page-head"><div class="page-copy"><div class="eyebrow">TREASURER</div><h1>Fund Utilization</h1><p>Track used, remaining, and utilization against approved allocations.</p></div></div>
<x-card title="Utilization chart" subtitle="Used funds versus remaining allocation">
    <div class="chart-box chart-box-lg"><canvas data-chart="budget" data-payload='@json($chart)'></canvas></div>
</x-card>
<x-card title="By project" subtitle="Remaining = Approved − Actual expenses">
    <div class="table-scroll"><table>
        <thead><tr><th>Project</th><th>Approved</th><th>Used</th><th>Remaining</th><th>Utilization</th></tr></thead>
        <tbody>
        @foreach ($projects as $p)
            <tr>
                <td><strong>{{ $p['title'] }}</strong><span class="muted">{{ $p['status'] }}</span></td>
                <td>{{ \App\Support\JciDemoData::money($p['approvedBudget']) }}</td>
                <td>{{ \App\Support\JciDemoData::money($p['usedFunds']) }}</td>
                <td>{{ \App\Support\JciDemoData::money(max(0, $p['approvedBudget'] - $p['usedFunds'])) }}</td>
                <td>{{ number_format(($p['usedFunds'] / max(1, $p['approvedBudget'])) * 100, 1) }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</x-card>
@endsection
