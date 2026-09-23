@extends('layouts.workspace')
@section('content')
<div class="page-head"><div class="page-copy"><div class="eyebrow">MEMBER</div><h1>My Projects</h1><p>Projects you created or chair.</p></div></div>
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
        <div class="project-progress">
            <div class="progress-wrap">
                <div class="progress"><span style="width:{{ $p['progress'] }}%"></span></div>
                <span class="progress-label">{{ $p['progress'] }}%</span>
            </div>
        </div>
    </article>
@endforeach
</div>
@else
<div class="empty-panel"><strong>No projects in this workspace.</strong><span>Create or get assigned to a project to see it here.</span></div>
@endif
@endsection
