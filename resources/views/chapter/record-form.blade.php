@extends('layouts.chapter')
@section('title',$kind==='letters'?'Prepare JCI LOI':'Prepare report')
@section('content')
@php $letterTypeOptions = $record->type === 'External Partner Letter' ? ['External Partner Letter' => 'JCI Letter of Intent (LOI)'] : ['JCI LOI' => 'JCI Letter of Intent (LOI)']; @endphp
<div class="page-heading"><div><div class="eyebrow">{{ $project?->reference??'CHAPTER FINANCES' }}</div><h1>{{ $kind==='letters'?'Prepare JCI LOI':'Prepare report' }}</h1><p>{{ $project?->title??'Overall Financial Report' }}</p></div></div>
@if($kind==='letters')<div class="notice">Project details are captured with this LOI version. Issued LOIs retain their original project details.</div>@endif
<form method="POST" action="{{ $record->exists?route('records.update',[$kind,$record->id]):route('records.store',$kind) }}" enctype="multipart/form-data">@csrf @if($record->exists)@method('PUT')@endif<input type="hidden" name="project_id" value="{{ $project?->id }}">
<section class="panel form-panel"><div class="form-grid"><x-field name="title" label="Document title / subject" :value="$record->title" required/>
<x-field name="type" label="Document type" type="select" :options="$kind==='letters'?$letterTypeOptions:($project?['Progress'=>'Progress report','Completion'=>'Project completion report']:['Overall Financial'=>'Overall Financial Report'])" :value="$record->type" required/>
@foreach($kind==='letters'?\App\Support\ChapterForms::LETTER:($project?\App\Support\ChapterForms::REPORT:\App\Support\ChapterForms::FINANCIAL_REPORT) as $key=>$label)<x-field :name="'data['.$key.']'" :label="$label" :type="str_starts_with($key,'period_')?'date':'textarea'" :value="$record->data[$key]??''"/>@endforeach
<x-field name="attachment" label="Supporting report / letter file (max 10 MB)" type="file"/>
<div class="full"><p class="hint">Save a draft, then submit the completed document for Admin review. Previous versions are retained.</p><button class="btn primary">Save draft</button></div></div></section></form>
@endsection
