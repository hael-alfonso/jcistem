@extends('layouts.workspace')

@section('content')
@php
    $tabs = [
        'all' => 'All Projects',
        'pending' => 'Pending Review',
        'approved' => 'Approved',
        'ongoing' => 'Ongoing',
        'completed' => 'Completed',
        'archived' => 'Archived',
        'my' => 'My Projects',
    ];
@endphp
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>{{ $pageTitle }}</h1>
        <p>View projects, filter by lifecycle stage, and open the project record.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="{{ route('admin.projects.create') }}"><x-icon name="plus" /> Create Project</a>
    </div>
</div>

<div class="subnav-wrap">
    @foreach ($tabs as $key => $label)
        <a class="subnav {{ $filter === $key ? 'active' : '' }}" href="{{ route('admin.projects', ['filter' => $key]) }}">{{ $label }}</a>
    @endforeach
</div>

<div class="toolbar-row">
    <div class="search-input">
        <x-icon name="search" />
        <input id="projectSearch" placeholder="Search project title, area, Chair…">
    </div>
    <div class="view-note">{{ count($projects) }} visible project{{ count($projects) === 1 ? '' : 's' }}</div>
</div>

@if (count($projects))
    <div class="project-grid">
        @foreach ($projects as $p)
            <article class="project-card">
                <div class="project-card-top">
                    <span class="area-chip">{{ $p['area'] }}</span>
                    <x-badge :text="$p['status']" />
                </div>
                <h3>{{ $p['title'] }}</h3>
                <p>{{ $p['needs'] }}</p>
                <div class="project-meta">
                    <span><x-icon name="calendar" /> {{ \App\Support\JciDemoData::date($p['date']) }}</span>
                    <span><x-icon name="user" /> {{ $p['chair'] }}</span>
                </div>
                <div class="project-progress">
                    <div class="progress-wrap">
                        <div class="progress"><span style="width:{{ $p['progress'] }}%"></span></div>
                        <span class="progress-label">{{ $p['progress'] }}%</span>
                    </div>
                </div>
                <div class="project-money">
                    <div><small>Approved budget</small><strong>{{ \App\Support\JciDemoData::money($p['approvedBudget']) }}</strong></div>
                    <div><small>Used funds</small><strong>{{ \App\Support\JciDemoData::money($p['usedFunds']) }}</strong></div>
                    <div><small>Remaining</small><strong>{{ \App\Support\JciDemoData::money(max(0, $p['approvedBudget'] - $p['usedFunds'])) }}</strong></div>
                </div>
                <div class="project-actions">
                    <a class="btn btn-secondary" href="{{ route('admin.projects.show', $p['id']) }}"><x-icon name="eye" /> View project</a>
                    @if(str_contains($p['status'], 'Review'))
                        <a class="btn btn-primary" href="{{ route('admin.projects', ['filter' => 'pending']) }}">Review</a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@else
    <div class="empty-panel">
        <strong>No projects in this view.</strong>
        <span>Change the project status filter or return to All Projects.</span>
    </div>
@endif
@endsection
