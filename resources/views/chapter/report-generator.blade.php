@extends('layouts.chapter')
@section('title', 'Budget Summary')
@section('content')
@php
    $allocated = (float) $shownProjects->sum('allocated_total');
    $spent = (float) $shownProjects->sum('spent_total');
@endphp
<div class="page-heading no-print"><div><a class="back-link" href="{{ route('records', 'reports') }}"><x-icon name="arrowLeft" /> Reports</a><div class="eyebrow">CHAPTER MONITORING</div><h1>Budget summary</h1><p>Review current approved allocations and posted expenses, then print or save as PDF.</p></div><div class="heading-actions"><button class="btn secondary" type="button" data-print @disabled($shownProjects->isEmpty())><x-icon name="print"/> Print / save PDF</button>@if(auth()->user()->role === 'treasurer')<form method="POST" action="{{ route('reports.export') }}">@csrf<input type="hidden" name="type" value="budgets"><input type="hidden" name="format" value="csv">@if($selectedId)<input type="hidden" name="project_id" value="{{ $selectedId }}">@endif<button class="btn primary" type="submit" @disabled($shownProjects->isEmpty())><x-icon name="download"/> Download Excel-compatible CSV</button></form>@endif</div></div>
<form class="list-filters no-print" method="GET" action="{{ route('reports.generate') }}">
    <label><span>Projects</span><select name="project_id"><option value="">All approved projects</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected((string) $selectedId === (string) $project->id)>{{ $project->title }}</option>@endforeach</select></label>
    <button class="btn secondary" type="submit"><x-icon name="filter"/> View summary</button>
    @if($selectedId)<a class="text-link" href="{{ route('reports.generate') }}">Show all</a>@endif
</form>
@if($shownProjects->isEmpty())
    <section class="panel empty-state"><x-icon name="chart"/><h3>No approved projects yet</h3><p>Budget figures will appear after a project is approved and the Treasurer records allocations.</p></section>
@else
<article class="panel form-panel printable-document project-document report-document budget-summary-document">
    <header class="project-letterhead"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="JCI logo" width="96" height="56"><div><strong>JCI Carmona</strong><span>Junior Chamber International</span></div></header>
    <p class="document-overline">CHAPTER FINANCIAL MONITORING</p>
    <h1 class="report-title">Budget summary</h1>
    <p class="budget-summary-meta">{{ $selectedId ? 'Selected project' : 'All approved projects' }} · Prepared {{ now()->format('F j, Y g:i A') }}</p>
    <div class="table-wrap report-table-wrap"><table class="budget-summary-table report-register-table budget-summary-table-report"><colgroup><col class="col-project"><col class="col-chair"><col class="col-money"><col class="col-money"><col class="col-money"></colgroup><thead><tr><th>Project</th><th>Project chair</th><th>Approved allocation</th><th>Posted expenses</th><th>Allocation balance</th></tr></thead><tbody>
        @foreach($shownProjects as $project)
            @php $projectAllocated = (float) $project->allocated_total; $projectSpent = (float) $project->spent_total; @endphp
            <tr><td data-label="Project"><strong>{{ $project->title }}</strong><small>{{ $project->reference }} · {{ \App\Support\ProjectFlow::statusLabel($project) }}</small></td><td data-label="Project chair">{{ $project->chair?->name ?? 'Not assigned' }}</td><td data-label="Approved allocation">PHP {{ number_format($projectAllocated, 2) }}</td><td data-label="Posted expenses">PHP {{ number_format($projectSpent, 2) }}</td><td data-label="Allocation balance">PHP {{ number_format($projectAllocated - $projectSpent, 2) }}</td></tr>
        @endforeach
    </tbody><tfoot><tr><th colspan="2">Total</th><th data-label="Approved allocation">PHP {{ number_format($allocated, 2) }}</th><th data-label="Posted expenses">PHP {{ number_format($spent, 2) }}</th><th data-label="Allocation balance">PHP {{ number_format($allocated - $spent, 2) }}</th></tr></tfoot></table></div>
    <p class="document-note">Only posted expenses count. Allocation balance is approved allocation minus posted spending; it is not the chapter's cash balance. This is a live summary, so later ledger changes may alter a new printout.</p>
</article>
@endif
@endsection
