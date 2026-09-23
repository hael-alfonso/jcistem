@extends('layouts.workspace')
@section('content')
<div class="page-head"><div class="page-copy"><div class="eyebrow">TREASURER</div><h1>Member Dues Recording</h1><p>Official monthly dues records are maintained by the Treasurer.</p></div>
<div class="page-actions"><button class="btn btn-primary" type="button" data-toast="Dues payment recorded."><x-icon name="plus" /> Record payment</button></div></div>
<div class="grid-2">
<x-card title="Dues status" subtitle="Current period"><div class="chart-box"><canvas data-chart="doughnut" data-payload='@json($chart)'></canvas></div></x-card>
<x-card title="Recording notes" subtitle="Admin cannot edit this register"><p class="muted" style="font-size:12px;line-height:1.6">My Member Dues in other workspaces is self-service only. Official posting, adjustments, and overdue flags stay in this Treasurer register.</p></x-card>
</div>
<x-card title="Dues register" subtitle="September 2026">
<div class="table-scroll"><table>
<thead><tr><th>Member</th><th>Due date</th><th>Expected</th><th>Paid</th><th>Status</th><th>Reference</th></tr></thead>
<tbody>
@foreach ($dues as $d)
<tr>
<td>{{ $d['member'] }}</td>
<td>{{ \App\Support\JciDemoData::date($d['due']) }}</td>
<td>{{ \App\Support\JciDemoData::money($d['expected']) }}</td>
<td>{{ \App\Support\JciDemoData::money($d['paid']) }}</td>
<td><x-badge :text="$d['status']" /></td>
<td>{{ $d['ref'] ?: '—' }}</td>
</tr>
@endforeach
</tbody></table></div>
</x-card>
@endsection
