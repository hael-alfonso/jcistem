@extends('layouts.chapter')
@section('title', 'Settings')
@section('content')
<div class="page-heading"><div><div class="eyebrow">ADMINISTRATION</div><h1>Settings</h1><p>Manage chapter report settings and approved document templates.</p></div></div>
<form class="panel form-panel form-grid" method="POST" action="{{ route('pms.settings.update') }}">@csrf @method('PUT')
<x-field name="report_chapter_name" label="Chapter name on generated reports" :value="$chapterName" required/>
<div class="full"><button class="btn primary"><x-icon name="save"/> Save settings</button></div></form>
<section class="panel form-panel"><div class="button-row"><a class="btn secondary" href="{{ route('templates') }}">Templates & Forms</a><a class="btn secondary" href="{{ route('activity-log') }}">Audit Log</a><a class="btn secondary" href="{{ route('accounts') }}">User Accounts & Roles</a></div></section>
@endsection
