@extends('layouts.workspace')
@section('content')
<div class="page-head"><div class="page-copy"><div class="eyebrow">TREASURER</div><h1>Disbursements</h1><p>Record project expenses, payments, payees, receipts, and posting status.</p></div>
<div class="page-actions"><button class="btn btn-primary" type="button" data-toast="Disbursement draft saved."><x-icon name="plus" /> Record disbursement</button></div></div>
<x-card title="Expense and payment records" subtitle="Treasurer recording view">
<div class="table-scroll"><table>
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
</tbody></table></div>
</x-card>
@endsection
