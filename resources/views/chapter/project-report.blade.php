@extends('layouts.chapter')
@section('title', $projectFolder['title'].' report')
@section('content')
<div class="page-heading no-print">
    <div><a class="back-link" href="{{ url()->previous() }}"><x-icon name="arrowLeft" /> Back to projects</a><div class="eyebrow">ADMINISTRATIVE REPORT</div><h1>{{ $projectFolder['title'] }}</h1><p>{{ $projects->count() }} project{{ $projects->count() === 1 ? '' : 's' }} · Generated {{ $generatedAt->format('F j, Y g:i A') }}</p></div>
    <button class="btn primary" type="button" data-print @disabled($projects->isEmpty())><x-icon name="print"/> Print / save PDF</button>
</div>
<article class="panel printable-document project-document report-document project-register-report">
    <header class="project-letterhead"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="JCI Carmona logo" width="96" height="56"><div><strong>JCI Carmona</strong><span>JCI Philippines · Carmona City, Cavite</span></div></header>
    <p class="document-overline">PROJECT REGISTER · ADMIN COPY</p>
    <h1 class="report-title">{{ $projectFolder['title'] }}</h1>
    <p class="budget-summary-meta">Generated {{ $generatedAt->format('F j, Y g:i A') }} · {{ $projects->count() }} project{{ $projects->count() === 1 ? '' : 's' }}</p>
    <div class="table-wrap report-table-wrap"><table class="budget-summary-table report-register-table project-register-table"><colgroup><col class="col-project"><col class="col-status"><col class="col-chair"><col class="col-dates"><col class="col-money"><col class="col-money"></colgroup><thead><tr><th>Project</th><th>Status</th><th>Project chair</th><th>Project dates</th><th>Proposed budget</th><th>Allocated</th></tr></thead><tbody>
        @forelse($projects as $project)
            <tr>
                <td data-label="Project"><strong>{{ $project->title }}</strong><small>{{ $project->reference ?? 'Project #'.$project->id }} · {{ $project->area }}</small></td>
                <td data-label="Status">{{ \App\Support\ProjectFlow::statusLabel($project) }}</td>
                <td data-label="Project chair">{{ $project->chair?->name ?? 'Not assigned' }}</td>
                <td data-label="Project dates"><span class="report-date-range">{{ $project->starts_on?->format('M j, Y') ?? 'Not set' }} – {{ $project->ends_on?->format('M j, Y') ?? 'Not set' }}</span></td>
                <td data-label="Proposed budget" class="report-money">PHP {{ number_format((float) $project->proposed_budget, 2) }}</td>
                <td data-label="Allocated" class="report-money">PHP {{ number_format((float) ($project->allocated_amount ?? 0), 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No projects match the selected filters.</td></tr>
        @endforelse
    </tbody></table></div>
    <p class="document-note">This register reflects the project records visible to the administrator when generated. Budget allocation is reported separately from proposed budget.</p>
</article>
@endsection
