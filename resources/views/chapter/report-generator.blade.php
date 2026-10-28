@extends('layouts.chapter')
@section('title', 'Generate Reports')
@section('content')
<div class="page-heading"><div><div class="eyebrow">DOCUMENTS & REPORTS</div><h1>Generate Reports</h1><p>Download approved project progress and budget summaries for chapter monitoring.</p></div></div>
<form class="panel form-panel form-grid" method="POST" action="{{ route('reports.export') }}">@csrf
<x-field name="type" label="Report" type="select" :options="['progress'=>'Project progress','budgets'=>'Budget and expense summary']" required/>
<x-field name="project_id" label="Project (leave blank for all approved projects)" type="select" :options="$projects->pluck('title','id')->all()"/>
<x-field name="format" label="Download format" type="select" :options="['pdf'=>'PDF','docx'=>'Word document (DOCX)']" required/>
<div class="full"><button class="btn primary"><x-icon name="download"/> Generate report</button></div>
</form>
@endsection
