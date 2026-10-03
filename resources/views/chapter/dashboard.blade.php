@extends('layouts.chapter')
@section('title', 'Overview')
@section('content')
@php
    $role = auth()->user()->role;
    $active = $dashboardProjects->whereIn('status', ['Approved', 'Ongoing', 'Completion Review']);
@endphp
<div class="dashboard-page">
<div class="page-heading dashboard-heading">
    <div>
        <div class="eyebrow">YOUR CHAPTER, CONNECTED</div>
        <h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
        <p>{{ match($role) { 'member' => 'Your projects, assigned work, and membership dues.', 'treasurer' => 'Project allocations and posted spending at a glance.', 'bod' => 'Chapter delivery and reviews assigned to your role.', default => 'Chapter activity, review queues, and funding at a glance.' } }}</p>
    </div>
    <a class="btn primary" href="{{ route('projects.create') }}"><x-icon name="plus"/> Submit a project concept</a>
</div>

<div class="stats-grid" aria-label="Dashboard summary">
    @if($role === 'treasurer')
    <div class="stat-card"><span>Allocated funds</span><strong class="money">PHP {{ number_format($financeCharts['allocated'], 2) }}</strong><small>Approved project allocations</small><x-icon name="wallet"/></div>
    @else
    <div class="stat-card"><span>{{ $role === 'member' ? 'My active projects' : 'Active projects' }}</span><strong>{{ $active->count() }}</strong><small>Approved through completion review</small><x-icon name="briefcase"/></div>
    @endif
    @if($role === 'member')
    <a class="stat-card" href="{{ route('dues') }}"><span>My dues balance</span><strong class="money">PHP {{ number_format($myDuesBalance, 2) }}</strong><small>Outstanding after posted payments</small><x-icon name="wallet"/></a>
    @elseif($role === 'treasurer')
    <a class="stat-card" href="{{ route('ledger') }}"><span>Posted expenses</span><strong class="money">PHP {{ number_format($financeCharts['spent'], 2) }}</strong><small>Posted project debit entries</small><x-icon name="wallet"/></a>
    @else
    <div class="stat-card"><span>{{ $role === 'bod' ? 'My review queue' : 'Awaiting review' }}</span><strong>{{ $pending->count() }}</strong><small>Requests matching your role</small><x-icon name="fileSignature"/></div>
    @endif
    @if($role === 'treasurer')
    <div class="stat-card"><span>Remaining funds</span><strong class="money">PHP {{ number_format($financeCharts['allocated'] - $financeCharts['spent'], 2) }}</strong><small>Allocation minus posted spending</small><x-icon name="wallet"/></div>
    @else
    <div class="stat-card"><span>Completed projects</span><strong>{{ $dashboardProjects->whereIn('status', ['Completed', 'Archived'])->count() }}</strong><small>Finished and archived work</small><x-icon name="checklist"/></div>
    @endif
    <div class="stat-card"><span>My open tasks</span><strong>{{ $tasks->count() }}</strong><small>{{ $tasks->filter(fn ($task) => $task->deadline?->lt(today()))->count() }} past their deadline</small><x-icon name="checklist"/></div>
</div>

@if($role === 'member')
    @include('chapter.partials.dashboard-tasks')
@elseif($role === 'treasurer')
    @include('chapter.partials.dashboard-finance')
@else
    <section class="dashboard-grid chart-grid" aria-label="Chapter overview">
        <section class="panel chart-panel">
            <div class="panel-heading"><div><h2>Projects by status</h2><p>Chapter projects in each stage</p></div><a class="text-link" href="{{ route('projects') }}">All projects &rarr;</a></div>
            @if($charts['total'])
                @include('chapter.partials.pie-chart', ['segments' => $charts['statuses']->map(fn ($count, $label) => ['label' => $label, 'value' => $count, 'color' => \App\Models\Project::colorForStatus($label)])->values(), 'center' => $charts['total'], 'caption' => 'projects', 'format' => 'count'])
            @else
                <div class="empty-state compact"><h3>No project data yet</h3><p>Projects will appear here when added.</p></div>
            @endif
        </section>
        @include('chapter.partials.dashboard-review')
    </section>
    @if($role === 'admin')
        @include('chapter.partials.budget-expense-chart', ['charts' => $financeCharts, 'summary' => true])
    @endif
@endif
<details class="dashboard-more panel">
    <summary>More dashboard details <span>Project breakdowns, assigned tasks, and finances</span></summary>
    @include('chapter.partials.project-charts')
    @if($role !== 'member') @include('chapter.partials.dashboard-tasks') @endif
    @if($role === 'member' || $role === 'bod') @include('chapter.partials.dashboard-finance') @endif
</details>
</div>
@endsection
