@extends('layouts.workspace')
@section('content')
<div class="page-head"><div class="page-copy"><div class="eyebrow">TREASURER</div><h1>Overall Financial Report</h1><p>Treasurer prepares and maintains the official Overall Financial Report.</p></div>
<div class="page-actions"><button class="btn btn-primary" type="button" data-toast="Financial report draft saved.">Prepare report</button></div></div>
<x-card title="Submitted financial reports" subtitle="Chapter-level financial reporting">
    <div class="table-scroll"><table>
        <thead><tr><th>Report</th><th>Submitted by</th><th>Submitted</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($reports as $r)
            <tr>
                <td><strong>{{ $r['title'] }}</strong><span class="muted">{{ $r['type'] }}</span></td>
                <td>{{ $r['submittedBy'] }}</td>
                <td>{{ $r['submitted'] }}</td>
                <td><x-badge :text="$r['status']" /></td>
            </tr>
        @empty
            <tr><td colspan="4">No financial reports yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</x-card>
<x-card title="Supporting ledger totals" subtitle="Posted activity used to prepare the report">
    <div class="table-scroll"><table>
        <thead><tr><th>Date</th><th>Reference</th><th>Type</th><th>Amount</th></tr></thead>
        <tbody>
        @foreach ($ledger as $r)
            <tr>
                <td>{{ \App\Support\JciDemoData::date($r['date']) }}</td>
                <td>{{ $r['ref'] }}</td>
                <td>{{ $r['type'] }}</td>
                <td>{{ \App\Support\JciDemoData::money($r['amount']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</x-card>
@endsection
