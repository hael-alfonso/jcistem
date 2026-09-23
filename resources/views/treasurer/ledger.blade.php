@extends('layouts.workspace')
@section('content')
<div class="page-head"><div class="page-copy"><div class="eyebrow">TREASURER</div><h1>Treasurer Ledger</h1><p>Authoritative financial transaction record. Admin may monitor but cannot post here.</p></div>
<div class="page-actions"><button class="btn btn-primary" type="button" data-toast="Ledger entry saved to the demo session."><x-icon name="plus" /> Record transaction</button></div></div>
<x-card title="Ledger entries" subtitle="Collections, payments, and posted financial activity">
<div class="table-scroll"><table>
<thead><tr><th>Date</th><th>Reference</th><th>Type</th><th>Description</th><th>Category</th><th>Amount</th><th>Status</th></tr></thead>
<tbody>
@foreach ($rows as $r)
<tr>
<td>{{ \App\Support\JciDemoData::date($r['date']) }}</td>
<td><strong>{{ $r['ref'] }}</strong></td>
<td>{{ $r['type'] }}</td>
<td>{{ $r['description'] }}<span class="muted">{{ $r['payer'] }}</span></td>
<td>{{ $r['category'] }}</td>
<td>{{ \App\Support\JciDemoData::money($r['amount']) }}</td>
<td><x-badge :text="$r['status']" /></td>
</tr>
@endforeach
</tbody></table></div>
</x-card>
@endsection
