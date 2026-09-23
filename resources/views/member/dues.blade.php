@extends('layouts.workspace')
@section('content')
<div class="page-head"><div class="page-copy"><div class="eyebrow">MEMBER</div><h1>My Member Dues</h1><p>Personal dues status only. Official recording is done by the Treasurer.</p></div></div>
<x-card title="Current dues" subtitle="This account’s current period">
    <div class="dues-hero">
        <div>
            <span>{{ $current['period'] ?? '—' }}</span>
            <strong>{{ \App\Support\JciDemoData::money($current['expected'] ?? 0) }}</strong>
            <small>Expected monthly dues</small>
        </div>
        <div>
            <x-badge :text="$current['status'] ?? 'Unpaid'" />
            <span>{{ !empty($current['paymentDate']) ? 'Paid on '.\App\Support\JciDemoData::date($current['paymentDate']) : 'No payment recorded' }}</span>
        </div>
    </div>
</x-card>
<x-card title="History" subtitle="Only your dues records are shown">
    <div class="table-scroll"><table>
        <thead><tr><th>Period</th><th>Due date</th><th>Expected</th><th>Paid</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($history as $d)
            <tr>
                <td>{{ $d['period'] }}</td>
                <td>{{ \App\Support\JciDemoData::date($d['due']) }}</td>
                <td>{{ \App\Support\JciDemoData::money($d['expected']) }}</td>
                <td>{{ \App\Support\JciDemoData::money($d['paid']) }}</td>
                <td><x-badge :text="$d['status']" /></td>
            </tr>
        @empty
            <tr><td colspan="5">No dues records for this account.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</x-card>
@endsection
