@extends('layouts.chapter')
@section('title','Overview')
@section('content')
<div class="page-heading dashboard-heading"><div><div class="eyebrow">YOUR CHAPTER, CONNECTED</div><h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ explode(' ',auth()->user()->name)[0] }}.</h1><p>Move ideas forward. Keep your projects and people in view.</p></div><a class="btn primary" href="{{ route('projects.create') }}">＋ Submit a project concept</a></div>
@include('chapter.partials.quick-actions')
@php $active=$projects->whereIn('status',['Approved','Ongoing','Completion Review']); $pending=$projects->whereIn('status',['Submitted for President Review','Submitted for Formal Approval','Completion Review']); @endphp
<div class="stats-grid">
<div class="stat-card"><span>Active projects</span><strong>{{ $active->count() }}</strong><small>Approved through completion review</small><x-icon name="briefcase"/></div>
<div class="stat-card"><span>Awaiting review</span><strong>{{ $pending->count() }}</strong><small>Concepts, proposals & completion</small><x-icon name="fileSignature"/></div>
<div class="stat-card"><span>My open tasks</span><strong>{{ $tasks->count() }}</strong><small>{{ $tasks->filter(fn($t)=>$t->deadline?->lt(today()))->count() }} past their deadline</small><x-icon name="checklist"/></div>
<div class="stat-card"><span>My dues balance</span><strong class="money">₱{{ number_format($dues->sum(fn($d)=>$d->balance),2) }}</strong><small>Official member dues records</small><x-icon name="wallet"/></div>
</div>
@include('chapter.partials.project-charts')
<div class="dashboard-grid"><section class="panel"><div class="panel-heading"><div><h2>Projects in motion</h2><p>Your chapter's current work</p></div><a class="text-link" href="{{ route('projects') }}">All projects →</a></div>
@forelse($active->take(5) as $project)<a class="project-row" href="{{ route('projects.show',$project) }}"><span class="project-mark">{{ strtoupper(substr($project->area,0,1)) }}</span><span class="row-main"><strong>{{ $project->title }}</strong><small>{{ $project->area }} · {{ $project->chair?->name ?? 'Chair to be assigned' }}</small></span><span class="row-progress"><span>{{ $project->progress }}%</span><progress value="{{ $project->progress }}" max="100"></progress></span><span class="badge project-status status-{{ $project->status_tone }}">{{ $project->status }}</span></a>@empty<div class="empty-state"><span class="empty-icon"><x-icon name="briefcase"/></span><h3>Great projects begin with an idea.</h3><p>Prepare a short concept letter for the Chapter President. Endorsed ideas move on to a full proposal.</p><a class="btn secondary" href="{{ route('projects.create') }}">Create your first concept</a></div>@endforelse
</section>
<section class="panel"><div class="panel-heading"><div><h2>My next steps</h2><p>Tasks that need your attention</p></div><a href="{{ route('tasks',['mine'=>1]) }}" class="text-link">View all →</a></div>
@forelse($tasks->take(5) as $task)<a class="attention-row" href="{{ route('projects.show',$task->project_id) }}#tasks"><span class="task-dot {{ $task->deadline?->lt(today()) ? 'overdue' : '' }}"></span><span><strong>{{ $task->title }}</strong><small>{{ $task->deadline?->format('M d, Y') }} · {{ $task->status }}</small></span></a>@empty<div class="empty-state compact"><x-icon name="checklist"/><h3>You're all caught up.</h3><p>Your next assigned task will appear here.</p></div>@endforelse</section></div>
<div class="dashboard-grid"><section class="panel"><div class="panel-heading"><div><h2>Review attention</h2><p>Keep the project journey moving</p></div></div>@forelse($pending->take(4) as $project)<a class="attention-row" href="{{ route('projects.show',$project) }}"><span class="project-mark light"><x-icon name="fileSignature"/></span><span><strong>{{ $project->title }}</strong><small>{{ $project->reference }} · {{ $project->status }}</small></span><span class="arrow">→</span></a>@empty<p class="panel-empty">No concepts or proposals are awaiting review.</p>@endforelse</section>
</div>
@endsection

