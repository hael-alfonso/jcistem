@extends('layouts.workspace')
@section('content')
<div class="page-head"><div class="page-copy"><div class="eyebrow">BOARD OF DIRECTORS</div><h1>Project Review</h1><p>BOD reviews proposals, budget implications, and project status.</p></div></div>
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
            <span><x-icon name="user" /> {{ $p['chair'] }}</span>
            <span><x-icon name="calendar" /> {{ \App\Support\JciDemoData::date($p['date']) }}</span>
        </div>
        <div class="project-progress">
            <div class="progress-wrap">
                <div class="progress"><span style="width:{{ $p['progress'] }}%"></span></div>
                <span class="progress-label">{{ $p['progress'] }}%</span>
            </div>
        </div>
    </article>
@endforeach
</div>
@endsection
