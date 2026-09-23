@extends('layouts.workspace')
@section('content')
<div class="page-head"><div class="page-copy"><div class="eyebrow">TREASURER</div><h1>Liquidation</h1><p>Maintain liquidation of project funds against receipts and posted expenses.</p></div></div>
<div class="grid-2">
<x-card title="Awaiting liquidation" subtitle="Pending expense records">
<div class="table-scroll"><table><thead><tr><th>Reference</th><th>Amount</th><th>Status</th></tr></thead><tbody>
@forelse ($pending as $e)
<tr><td><strong>{{ $e['ref'] }}</strong><span class="muted">{{ $e['description'] }}</span></td><td>{{ \App\Support\JciDemoData::money($e['amount']) }}</td><td><x-badge :text="$e['status']" /></td></tr>
@empty
<tr><td colspan="3">No pending liquidations.</td></tr>
@endforelse
</tbody></table></div>
</x-card>
<x-card title="Posted" subtitle="Liquidated and posted to the ledger">
<div class="table-scroll"><table><thead><tr><th>Reference</th><th>Amount</th><th>Status</th></tr></thead><tbody>
@foreach ($posted as $e)
<tr><td><strong>{{ $e['ref'] }}</strong><span class="muted">{{ $e['description'] }}</span></td><td>{{ \App\Support\JciDemoData::money($e['amount']) }}</td><td><x-badge :text="$e['status']" /></td></tr>
@endforeach
</tbody></table></div>
</x-card>
</div>
@endsection
