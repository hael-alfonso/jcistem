@extends('layouts.workspace')
@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • MEMBER WORKSPACE</div>
        <h1>Member Dashboard</h1>
        <p>Your assigned projects and personal member dues.</p>
    </div>
</div>
<div class="stats-grid">
    <x-stat label="My projects" :value="count($projects)" note="Created or chaired by you" icon="briefcase" />
    <x-stat label="Current dues" :value="\App\Support\JciDemoData::money($due['expected'] ?? 0)" note="{{ $due['period'] ?? '—' }}" icon="receipt" />
    <x-stat label="Dues status" :value="$due['status'] ?? '—'" note="Personal self-service view only" icon="check" />
    <x-stat label="Upcoming dates" :value="count(\App\Support\JciDemoData::events())" note="Chapter calendar" icon="calendar" />
</div>
<x-card title="My projects" subtitle="Open a project from My Projects for the full record">
    @forelse ($projects as $p)
        <div class="project-row">
            <span class="avatar avatar-sm">{{ $p['progress'] }}%</span>
            <div class="project-row-main"><strong>{{ $p['title'] }}</strong><span>{{ $p['status'] }}</span></div>
            <div class="row-side">{{ \App\Support\JciDemoData::money($p['approvedBudget']) }}<small>approved</small></div>
        </div>
    @empty
        <div class="empty-state"><strong>No assigned projects yet.</strong><span>Projects you create or chair will appear here.</span></div>
    @endforelse
</x-card>
@endsection
