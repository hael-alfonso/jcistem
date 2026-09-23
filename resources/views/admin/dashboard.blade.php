@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Admin Dashboard</h1>
        <p>A clear view of project status, progress, reviews, and fund use.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="{{ route('admin.projects.create') }}"><x-icon name="plus" /> Create Project</a>
    </div>
</div>

<div class="stats-grid">
    <x-stat label="Active projects" :value="$open" note="Current active or setup projects" icon="folder" />
    <x-stat label="Needs review" :value="$pending" note="Projects waiting for review" icon="clock" />
    <x-stat label="Approved funds" :value="\App\Support\JciDemoData::money($approved)" note="Current approved allocation" icon="wallet" />
    <x-stat label="Remaining funds" :value="\App\Support\JciDemoData::money($remaining)" note="Approved minus recorded expenses" icon="chart" />
</div>

<div class="grid-2">
    <x-card title="Project status" subtitle="Share of projects in each lifecycle stage">
        <div class="chart-box"><canvas data-chart="doughnut" data-payload='@json($statusChart)'></canvas></div>
    </x-card>
    <x-card title="Progress by project" subtitle="Average {{ $avg }}% complete">
        <div class="chart-box"><canvas data-chart="hbar" data-payload='@json($progressChart)'></canvas></div>
    </x-card>
</div>

<div class="grid-2">
    <x-card title="Budget use" subtitle="Used funds versus remaining allocation">
        <div class="chart-box chart-box-lg"><canvas data-chart="budget" data-payload='@json($budgetChart)'></canvas></div>
    </x-card>
    <x-card title="Needs attention" subtitle="Items that may need an Admin action">
        <div class="attention-list">
            <a class="attention-row" href="{{ route('admin.projects', ['filter' => 'pending']) }}">
                <div class="attention-icon"><x-icon name="clock" /></div>
                <div>
                    <strong>Projects needing review</strong>
                    <span>{{ $pending }} project{{ $pending === 1 ? '' : 's' }} waiting for review.</span>
                </div>
                <em>Review <x-icon name="chevron" /></em>
            </a>
            <a class="attention-row" href="{{ route('admin.reports') }}">
                <div class="attention-icon"><x-icon name="report" /></div>
                <div>
                    <strong>Reports to check</strong>
                    <span>{{ $submittedReports }} submitted report{{ $submittedReports === 1 ? '' : 's' }} available.</span>
                </div>
                <em>Reports <x-icon name="chevron" /></em>
            </a>
            <a class="attention-row" href="{{ route('admin.calendar') }}">
                <div class="attention-icon"><x-icon name="calendar" /></div>
                <div>
                    <strong>Next activity</strong>
                    <span>BOD project review • Oct 5, 2026.</span>
                </div>
                <em>Calendar <x-icon name="chevron" /></em>
            </a>
        </div>
    </x-card>
</div>
@endsection
