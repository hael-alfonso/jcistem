@extends('layouts.chapter')
@section('title', 'Templates & Forms')
@section('content')
<div class="page-heading"><div><div class="eyebrow">DOCUMENTS & REPORTS</div><h1>Templates & Forms</h1><p>Download reference forms and chapter-approved templates.</p></div></div>
<section class="panel form-panel">
<h2>Reference Forms</h2>
<p>Editable Word files supplied by the chapter. Complete the placeholders and obtain the required signatures.</p>
@foreach($referenceForms as $key => $form)
<a class="list-row" href="{{ route('templates.reference.download', $key) }}"><span><strong>{{ $form['title'] }}</strong><small>{{ $form['description'] }}</small></span><span class="text-link"><x-icon name="download"/> Download Word</span></a>
@endforeach
</section>
@if(auth()->user()->role === 'admin')
<details class="panel form-panel"><summary class="large-summary">Publish approved template</summary>
<form class="form-grid" method="POST" enctype="multipart/form-data" action="{{ route('templates.store') }}">@csrf
<x-field name="title" label="Template title" required/><x-field name="version" label="Version / approval date" required/>
<x-field name="file" label="Approved PDF, Word or Excel form (max 10 MB)" type="file" required/>
<div class="full"><button class="btn primary"><x-icon name="upload"/> Publish template</button></div></form></details>
@endif
<section class="panel form-panel"><h2>Published Templates</h2>@forelse($templates as $template)<a class="list-row" href="{{ route('templates.download',$template) }}"><span><strong>{{ $template->title }}</strong><small>{{ $template->version }} · {{ $template->created_at->format('d M Y') }}</small></span><span class="text-link"><x-icon name="download"/> Download</span></a>@empty<div class="empty-state"><h2>No additional approved templates published yet</h2><p>The chapter administrator can publish approved forms here.</p></div>@endforelse
@include('chapter.partials.pagination',['items'=>$templates])</section>
@endsection
