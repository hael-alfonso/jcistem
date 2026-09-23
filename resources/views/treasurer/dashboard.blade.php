@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • TREASURER WORKSPACE</div>
        <h1>Treasurer Dashboard</h1>
        <p>Record dues, post disbursements, maintain the ledger, and prepare the Overall Financial Report.</p>
    </div>
</div>
<div class="stats-grid">
    <x-stat label="Dues collected" :value="\App\Support\JciDemoData::money($collected)" note="Posted collections" icon="receipt" />
    <x-stat label="Disbursed" :value="\App\Support\JciDemoData::money($paidOut)" note="Posted expense / payment" icon="wallet" />
    <x-stat label="Remaining funds" :value="\App\Support\JciDemoData::money($remaining)" note="Approved minus used" icon="chart" />
    <x-stat label="Pending liquidation" :value="$pendingLiquidation" note="Expenses not yet posted" icon="clock" />
</div>
<div class="grid-2">
    <x-card title="Member dues status" subtitle="Official dues recording for the current period">
        <div class="chart-box"><canvas data-chart="doughnut" data-payload='@json($duesChart)'></canvas></div>
    </x-card>
    <x-card title="Disbursements by category" subtitle="Posted and pending expense amounts">
        <div class="chart-box"><canvas data-chart="bar" data-payload='@json($expenseChart)'></canvas></div>
    </x-card>
</div>
@endsection
