@extends('layouts.chapter')
@section('title',$proposal?'Full project proposal':'Project concept letter')
@section('content')
<div class="page-heading"><div><div class="eyebrow">{{ $proposal?'STAGE 02 · DETAILED PLANNING':'STAGE 01 · YOUR PROJECT IDEA' }}</div><h1>{{ $proposal?'Full project proposal':'Project concept letter' }}</h1><p>{{ $proposal?'Develop the endorsed idea into a complete implementation proposal.':'A short internal letter to the JCI Carmona Chapter President requesting endorsement.' }}</p></div></div>
<div class="notice">{{ $proposal?'Final approval is required before Chair assignment, implementation, or financial recording.':'Concept endorsement allows detailed planning. It does not authorize implementation or spending.' }}</div>
<form method="POST" action="{{ $project->exists?route('projects.update',$project):route('projects.store') }}">@csrf @if($project->exists) @method('PUT') @endif
<section class="panel form-panel"><div class="section-heading"><span>01</span><div><h2>Project essentials</h2><p>These details carry forward through the project journey.</p></div></div><div class="form-grid">
<x-field name="title" label="Project title" :value="$project->title" required maxlength="200"/>
<x-field name="area" label="JCI area of opportunity" type="select" :options="array_combine(\App\Support\ChapterForms::AREAS,\App\Support\ChapterForms::AREAS)" :value="$project->area" required/>
<x-field name="starts_on" label="Proposed start date" type="date" :value="$project->starts_on?->format('Y-m-d')"/>
<x-field name="ends_on" label="Proposed end date" type="date" :value="$project->ends_on?->format('Y-m-d')"/>
<x-field name="venue" label="Proposed venue / location" :value="$project->venue"/>
<x-field name="proposed_budget" label="Estimated budget (PHP)" type="number" min="0" step="0.01" :value="$project->proposed_budget??0" required/>
</div></section>
<section class="panel form-panel"><div class="section-heading"><span>02</span><div><h2>{{ $proposal?'Proposal details':'The concept letter' }}</h2><p>Save progress at any time. Complete each section before submitting.</p></div></div><div class="form-grid">
@foreach($proposal?\App\Support\ChapterForms::PROPOSAL:\App\Support\ChapterForms::CONCEPT as $key=>$label)
<x-field :name="($proposal?'proposal':'concept').'['.$key.']'" :label="$label" type="textarea" :value="($proposal?$project->proposal:$project->concept)[$key]??''" maxlength="15000"/>
@endforeach</div></section>
<div class="form-footer"><a class="btn secondary" href="{{ $project->exists?route('projects.show',$project):route('projects') }}">Cancel</a><button class="btn primary">Save {{ $proposal?'proposal':'concept' }} draft</button></div></form>
@endsection

