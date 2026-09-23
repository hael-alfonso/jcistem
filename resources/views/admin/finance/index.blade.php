@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Financial Monitoring</h1>
        <p>Admin read-only view of project-level financial information.</p>
    </div>
</div>

<div class="permission-banner"><x-icon name="shield" /> Treasurer retains authoritative financial recording and maintenance. Admin can monitor the four financial source modules below.</div>

<div class="stats-grid">
    <x-stat label="Approved allocation" :value="\App\Support\JciDemoData::money($approved)" note="Current project allocations" icon="wallet" />
    <x-stat label="Used funds" :value="\App\Support\JciDemoData::money($used)" note="Recorded project expenses" icon="chart" />
    <x-stat label="Remaining funds" :value="\App\Support\JciDemoData::money($remaining)" note="Approved minus actual expenses" icon="wallet" />
    <x-stat label="Expense records" :value="$expenseCount" note="Project expenses monitored" icon="receipt" />
</div>

<x-card title="Allocation vs use" subtitle="Used funds compared with remaining approved budget">
    <div class="chart-box chart-box-lg"><canvas data-chart="budget" data-payload='@json($budgetChart)'></canvas></div>
</x-card>

<div class="finance-tiles-simple">
    <a href="{{ route('admin.finance.budget') }}">
        <span class="tile-icon"><x-icon name="chart" /></span>
        <div><strong>Budget Allocation</strong><span>Approved project allocations</span></div>
        <x-icon name="chevron" />
    </a>
    <a href="{{ route('admin.finance.utilization') }}">
        <span class="tile-icon"><x-icon name="chart" /></span>
        <div><strong>Fund Utilization</strong><span>Used, remaining and utilization</span></div>
        <x-icon name="chevron" />
    </a>
    <a href="{{ route('admin.finance.expenses') }}">
        <span class="tile-icon"><x-icon name="receipt" /></span>
        <div><strong>Expense Monitoring</strong><span>Project expenses and receipts</span></div>
        <x-icon name="chevron" />
    </a>
    <a href="{{ route('admin.finance.reports') }}">
        <span class="tile-icon"><x-icon name="report" /></span>
        <div><strong>Financial Reports</strong><span>Submitted financial reports</span></div>
        <x-icon name="chevron" />
    </a>
</div>
@endsection
