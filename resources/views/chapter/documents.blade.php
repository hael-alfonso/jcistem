@extends('layouts.chapter')
@section('title', 'File Library')
@section('content')
<div class="page-heading collection-heading">
    <div>
        <div class="eyebrow">JCI CARMONA RECORDS</div>
        <h1>File Library</h1>
        <p>Find project uploads, attendance photos, and evaluations you are allowed to view.</p>
    </div>
    <a class="btn primary" href="{{ route('projects') }}"><x-icon name="folder" /> Open projects</a>
</div>

<section class="panel form-panel" aria-labelledby="project-files-heading">
    <h2 id="project-files-heading" class="sr-only">Project files</h2>
    <form class="list-filters document-search" method="GET" action="{{ route('documents') }}">
        <label><span>Search files</span><input name="q" value="{{ request('q') }}" placeholder="Title, category, or project"></label>
        <label><span>Show</span><select name="type"><option value="all" @selected(request('type', 'all') === 'all')>All project uploads</option><option value="evidence" @selected(request('type') === 'evidence')>Attendance &amp; evaluations</option><option value="attendance" @selected(request('type') === 'attendance')>Attendance photos</option><option value="evaluation" @selected(request('type') === 'evaluation')>Evaluations</option><option value="documents" @selected(request('type') === 'documents')>Documents &amp; letters</option></select></label>
        <label>Added from<input type="date" name="from" value="{{ request('from') }}"></label><label>Added to<input type="date" name="to" value="{{ request('to') }}"></label>
        <button class="btn secondary" type="submit"><x-icon name="filter" /> Apply filters</button>
        @if(request()->filled('q') || request()->filled('type') || request()->filled('from') || request()->filled('to'))<a class="text-link" href="{{ route('documents') }}"><x-icon name="close" /> Clear filters</a>@endif
    </form>
    <div class="collection-results"><span>{{ number_format($documents->total()) }} {{ \Illuminate\Support\Str::plural('file', $documents->total()) }}</span><span>Private project records</span></div>
    @if($documents->isNotEmpty())
        <div class="table-wrap"><table>
            <thead><tr><th>File</th><th>Project</th><th>Added</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach($documents as $document)
                    @php $projectSection = match ($document->submission_stage) { 'concept' => 'overview', 'proposal' => 'proposal', 'partnership' => 'partnerships', 'attendance', 'evaluation' => 'evidence', default => 'documents' }; @endphp
                    <tr>
                        <td><strong>{{ $document->title }}</strong><small>{{ $document->category }}@if($document->original_name) · {{ $document->original_name }}@endif</small></td>
                        <td><a class="text-link" href="{{ route('projects.show', $document->project) }}#{{ $projectSection }}">{{ $document->project->title }}</a></td>
                        <td>{{ $document->activity_on?->format('M d, Y') ?? $document->created_at?->format('M d, Y') ?? '—' }}</td>
                        <td><div class="file-library-actions">@if($document->path && $document->canPreviewInline())<a class="text-link" href="{{ route(in_array($document->extension(), ['docx', 'xls', 'xlsx', 'pptx'], true) ? 'documents.original-preview' : 'documents.preview', $document) }}" target="_blank" rel="noopener"><x-icon name="eye" /> View</a>@elseif(!$document->path)<a class="text-link" href="{{ route('projects.show', $document->project) }}#evidence"><x-icon name="eye" /> Read</a>@endif @if($document->path)<a class="text-link" href="{{ route('documents.download', $document) }}"><x-icon name="download" /> Download</a>@endif</div></td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
        @include('chapter.partials.pagination', ['items' => $documents])
    @else
        <div class="empty-state">
            <h3>{{ request()->filled('q') ? 'No matching files' : 'No project files yet' }}</h3>
            <p>{{ request()->filled('q') ? 'Try another title, category, or project name.' : 'Supporting files will appear here after they are uploaded from a project.' }}</p>
            <a class="btn secondary" href="{{ request()->filled('q') ? route('documents') : route('projects') }}"><x-icon name="{{ request()->filled('q') ? 'close' : 'folder' }}" /> {{ request()->filled('q') ? 'Show all files' : 'Browse projects' }}</a>
        </div>
    @endif
</section>
@endsection
