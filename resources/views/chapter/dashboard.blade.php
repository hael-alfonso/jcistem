@extends('layouts.chapter')
@section('title', 'Dashboard')
@section('content')
@php
    $role = auth()->user()->role;
    $active = $dashboardProjects->whereIn('status', ['Approved', 'Ongoing', 'Completion Review']);
    $overdue = $tasks->filter(fn ($task) => $task->deadline?->lt(today()))->count();
    $balance = $financeCharts['allocated'] - $financeCharts['spent'];
    $overBudget = $financeCharts['rows']->filter(fn ($row) => $row['spent'] > $row['allocated'])->count();
    $hour = now()->hour;
    $greeting = match (true) {
        $hour < 12 => 'Good morning',
        $hour < 18 => 'Good afternoon',
        default => 'Good evening',
    };
    $firstName = preg_split('/\s+/', trim(auth()->user()->name))[0] ?: 'there';
@endphp
<div class="dashboard-page">
<div class="page-heading dashboard-heading">
    <div class="dashboard-heading-copy"><div class="eyebrow">{{ \App\Support\WorkspaceNav::label($role) }} workspace</div><h1>{{ $greeting }}, {{ $firstName }}.</h1><p>{{ match($role) { 'member' => 'Your projects, open tasks, and dues at a glance.', 'treasurer' => 'Project allocations and posted expenses at a glance.', 'bod' => 'Your review queue, project status, and open tasks.', default => 'Pending reviews, chapter projects, and funding at a glance.' } }}</p></div>
    @if($role === 'treasurer')<a class="btn primary" href="{{ route('ledger') }}"><x-icon name="receipt"/> Open ledger</a>
    @elseif(in_array($role, ['admin', 'bod']))<a class="btn primary" href="#dashboard-reviews"><x-icon name="fileSignature"/> Review requests</a>
    @else<a class="btn primary" href="{{ route('projects.create') }}"><x-icon name="plus"/> New project concept</a>@endif
</div>
<div class="stats-grid" aria-label="Dashboard summary">
    @if($role === 'treasurer')
        <x-dashboard-stat label="Allocated funds" :value="'PHP '.number_format($financeCharts['allocated'], 2)" note="Approved project allocations · all time" icon="wallet" tone="budget" :money="true" :href="route('finance')"/>
        <x-dashboard-stat label="Posted expenses" :value="'PHP '.number_format($financeCharts['spent'], 2)" note="Approved projects · posted debits" icon="receipt" tone="expenses" :money="true" :href="route('ledger')"/>
        <x-dashboard-stat label="Allocation balance" :value="'PHP '.number_format($balance, 2)" note="Budget minus expenses · not cash on hand" icon="wallet" :tone="$balance < 0 ? 'expenses' : 'balance'" :money="true" :href="route('finance')"/>
        <x-dashboard-stat label="Projects over budget" :value="$overBudget" note="Posted expenses exceed allocations" icon="info" tone="reviews" :href="route('finance')"/>
    @else
        <x-dashboard-stat :label="$role === 'member' ? 'My active projects' : 'Active projects'" :value="$active->count()" note="Approved, ongoing, or in completion review" icon="briefcase" tone="projects" :href="route('projects', $role === 'member' ? ['filter' => 'mine'] : [])"/>
        @if($role === 'member')
            <x-dashboard-stat label="My dues balance" :value="'PHP '.number_format($myDuesBalance, 2)" note="Outstanding after posted payments" icon="receipt" tone="expenses" :money="true" :href="route('dues')"/>
        @else
            <x-dashboard-stat :label="$role === 'bod' ? 'My review queue' : 'Awaiting review'" :value="$pending->count()" note="Requests matching your review role" icon="fileSignature" tone="reviews" href="#dashboard-reviews"/>
        @endif
        <x-dashboard-stat label="Completed projects" :value="$dashboardProjects->whereIn('status', ['Completed', 'Archived'])->count()" note="Completed and archived projects" icon="check" tone="balance" :href="route('projects', $role === 'member' ? ['filter' => 'mine'] : [])"/>
        @if($role === 'admin')
            <x-dashboard-stat label="Allocation balance" :value="'PHP '.number_format($balance, 2)" note="Approved budgets minus posted expenses" icon="wallet" :tone="$balance < 0 ? 'expenses' : 'budget'" :money="true" :href="route('finance')"/>
        @else
            <x-dashboard-stat label="My open tasks" :value="$tasks->count()" :note="$overdue.' overdue · excludes completed tasks'" icon="checklist" :tone="$overdue ? 'reviews' : 'tasks'" :href="route('tasks', ['mine' => 1])"/>
        @endif
    @endif
</div>
@if($role === 'treasurer')
    <div class="dashboard-grid chart-grid" aria-label="Financial overview">
        @include('chapter.partials.funding-utilization', ['charts' => $financeCharts])
        @include('chapter.partials.monthly-expense-chart', ['charts' => $financeCharts])
    </div>
@elseif($role === 'member')
    @include('chapter.partials.dashboard-tasks')
    @if($active->isNotEmpty())
        <section class="panel dashboard-project-list">
            <div class="panel-heading"><div><h2>My active projects</h2><p>Your current project work</p></div><a class="text-link" href="{{ route('projects', ['filter' => 'mine']) }}">All my projects &rarr;</a></div>
            @foreach($active->take(3) as $project)
                <a class="attention-row" href="{{ route('projects.show', $project) }}"><span class="project-mark"><x-icon name="briefcase"/></span><span class="row-main"><strong>{{ $project->title }}</strong><small>{{ $project->status }} · {{ $project->progress }}% tasks completed</small></span><span class="arrow" aria-hidden="true">&rarr;</span></a>
            @endforeach
        </section>
    @endif
@else
    <div class="dashboard-grid chart-grid" aria-label="Chapter overview">
        @include('chapter.partials.dashboard-review')
        @include('chapter.partials.dashboard-project-status')
    </div>
    @if($role === 'admin')
        @include('chapter.partials.budget-expense-chart', ['charts' => $financeCharts, 'summary' => true])
    @elseif($tasks->isNotEmpty())
        @include('chapter.partials.dashboard-task-list')
    @endif
@endif
</div>
@endsection
