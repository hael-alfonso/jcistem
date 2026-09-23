@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Expense Monitoring</h1>
        <p>Monitor expense date, category, description, amount, requester, reference, receipt and status.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('admin.finance') }}"><x-icon name="arrow" /> Back</a>
    </div>
</div>

<x-card title="Spend by category" subtitle="Recorded expense amounts grouped by category">
    <div class="chart-box"><canvas data-chart="bar" data-payload='@json($chart)'></canvas></div>
</x-card>

<x-card title="Expense records" subtitle="Admin monitoring view of recorded project expenses.">
    <div class="table-scroll">
        <table>
            <thead><tr><th>Reference / Date</th><th>Project / Expense</th><th>Amount</th><th>Requester</th><th>Status</th><th>Receipt</th></tr></thead>
            <tbody>
            @foreach ($expenses as $e)
                @php $p = \App\Support\JciDemoData::project($e['project']); @endphp
                <tr>
                    <td><strong>{{ $e['ref'] }}</strong><span class="muted">{{ \App\Support\JciDemoData::date($e['date']) }}</span></td>
                    <td><strong>{{ $p['title'] ?? '—' }}</strong><span class="muted">{{ $e['category'] }} • {{ $e['description'] }}</span></td>
                    <td>{{ \App\Support\JciDemoData::money($e['amount']) }}</td>
                    <td>{{ $e['requester'] }}</td>
                    <td><x-badge :text="$e['status']" /></td>
                    <td>@if($e['receipt'])<x-badge text="Receipt" tone="positive" />@else — @endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>
@endsection
