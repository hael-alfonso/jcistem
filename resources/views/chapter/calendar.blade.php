@extends('layouts.chapter')
@section('title','Calendar')
@section('content')
<div class="page-heading"><div><div class="eyebrow">MAKE ROOM FOR IMPACT</div><h1>Chapter calendar</h1><p>Project dates, activities, milestones, and deadlines in one agenda.</p></div></div>
@php
$agenda=collect();
foreach($events as $event)$agenda->push(['date'=>$event->starts_on,'title'=>$event->title,'type'=>$event->type,'context'=>$event->project?->title??'Chapter activity','url'=>$event->project_id?route('projects.show',$event->project_id).'#timeline':null]);
foreach($projects as $p)if($p->starts_on)$agenda->push(['date'=>$p->starts_on,'title'=>$p->title,'type'=>'Project','context'=>$p->venue,'url'=>route('projects.show',$p)]);
foreach($tasks as $t)if($t->deadline)$agenda->push(['date'=>$t->deadline,'title'=>$t->title,'type'=>'Task deadline','context'=>$t->project?->title,'url'=>route('projects.show',$t->project_id).'#tasks']);
foreach(\App\Models\MemberDue::where('member_id',auth()->id())->get() as $due)$agenda->push(['date'=>$due->due_date,'title'=>'Member dues · '.$due->period,'type'=>$due->status,'context'=>'PHP '.number_format($due->balance,2).' outstanding','url'=>route('dues')]);
@endphp
<section class="panel">@forelse($agenda->sortBy('date')->groupBy(fn($a)=>$a['date']?->format('F Y')??'Unscheduled') as $month=>$rows)<div class="panel-heading"><h2>{{ $month }}</h2></div>@foreach($rows as $item)<div class="agenda-row"><div class="date-block"><strong>{{ $item['date']?->format('d') }}</strong><small>{{ $item['date']?->format('D') }}</small></div><div class="row-main">@if($item['url'])<a href="{{ $item['url'] }}"><strong>{{ $item['title'] }}</strong></a>@else<strong>{{ $item['title'] }}</strong>@endif<small>{{ $item['context'] }}</small></div><span class="badge">{{ $item['type'] }}</span></div>@endforeach @empty<div class="empty-state"><h3>Your chapter agenda starts here.</h3><p>Project dates and task deadlines appear automatically. Chairs can schedule milestones from their project.</p></div>@endforelse</section>
@if(auth()->user()->role==='admin')<section class="panel form-panel no-print"><h2>Schedule a chapter activity</h2><form method="POST" action="{{ route('calendar.store') }}" class="form-grid">@csrf<x-field name="title" label="Activity title" required/><x-field name="type" label="Activity type" type="select" :options="array_combine(['Activity','Meeting','Review'],['Activity','Meeting','Review'])" required/><x-field name="starts_on" label="Start date" type="date" required/><x-field name="ends_on" label="End date" type="date"/><x-field name="venue" label="Venue"/><x-field name="description" label="Details" type="textarea"/><div class="full"><button class="btn primary">Schedule activity</button></div></form></section>@endif
@endsection
