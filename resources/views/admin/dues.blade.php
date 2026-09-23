@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>My Member Dues</h1>
        <p>Personal self-service view. Admin can see only this account’s own dues record here.</p>
    </div>
</div>

<x-card title="Current dues" subtitle="Current period status and payment details">
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

<x-card title="Dues history" subtitle="Only this account’s monthly dues records are visible.">
    <div class="table-scroll">
        <table>
            <thead><tr><th>Period</th><th>Due date</th><th>Expected</th><th>Paid</th><th>Status</th><th>Payment reference</th></tr></thead>
            <tbody>
            @foreach ($history as $d)
                <tr>
                    <td>{{ $d['period'] }}</td>
                    <td>{{ \App\Support\JciDemoData::date($d['due']) }}</td>
                    <td>{{ \App\Support\JciDemoData::money($d['expected']) }}</td>
                    <td>{{ \App\Support\JciDemoData::money($d['paid']) }}</td>
                    <td><x-badge :text="$d['status']" /></td>
                    <td>{{ $d['ref'] ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>
<div class="permission-banner"><x-icon name="shield" /> My Member Dues does not grant access to other members’ dues or Treasurer Ledger editing.</div>
@endsection
