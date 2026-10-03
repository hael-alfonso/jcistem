@extends('layouts.chapter')
@section('title', 'Project documents')
@section('content')
<div class="page-heading collection-heading">
    <div>
        <div class="eyebrow">JCI CARMONA RECORDS</div>
        <h1>Project documents</h1>
        <p>Keep the files behind each JCI Carmona project easy to find, from planning through completion.</p>
    </div>
    <a class="btn primary" href="{{ route('projects') }}">Open projects</a>
</div>

<section class="document-guide" aria-label="Document types">
    <article class="panel document-guide-card">
        <span class="document-guide-label">01 · INTERNAL REQUEST</span>
        <h2>Project Concept Letters</h2>
        <p>A member's initial project idea addressed to the Chapter President. Review and endorsement happen in the project's concept workflow before a full proposal is prepared.</p>
        <a class="text-link" href="{{ route('projects', ['filter' => 'concepts']) }}">View project concepts →</a>
    </article>
    <article class="panel document-guide-card">
        <span class="document-guide-label">02 · EXTERNAL CORRESPONDENCE</span>
        <h2>External LOIs</h2>
        <p>Letters for partners, sponsors, schools, local government units, and other collaborators are linked to a project and tracked through their own review flow.</p>
        <a class="text-link" href="{{ route('records', 'letters') }}">View external LOIs →</a>
    </article>
    <article class="panel document-guide-card">
        <span class="document-guide-label">03 · PROJECT ACCOUNTABILITY</span>
        <h2>Reports &amp; evidence</h2>
        <p>Project progress and completion reports record results. Supporting files such as photos, attendance sheets, approvals, and receipts belong with the relevant project.</p>
        <a class="text-link" href="{{ route('records', 'reports') }}">View reports →</a>
    </article>
</section>

<section class="panel form-panel" aria-labelledby="project-files-heading">
    <div class="panel-heading flush">
        <div>
            <h2 id="project-files-heading">Supporting project files</h2>
            <p>Files uploaded to projects you can access. Open a project to add a file when you have editing rights.</p>
        </div>
        <span>{{ number_format($documents->total()) }} {{ \Illuminate\Support\Str::plural('file', $documents->total()) }}</span>
    </div>
    <form class="list-filters document-search" method="GET" action="{{ route('documents') }}">
        <label><span>Search files</span><input name="q" value="{{ request('q') }}" placeholder="Title, category, or project"></label>
        <button class="btn secondary" type="submit">Search</button>
        @if(request()->filled('q'))<a class="text-link" href="{{ route('documents') }}">Clear search</a>@endif
    </form>
    @if($documents->isNotEmpty())
        <div class="table-wrap"><table>
            <thead><tr><th>File</th><th>Project</th><th>Added</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach($documents as $document)
                    <tr>
                        <td><strong>{{ $document->title }}</strong><small>{{ $document->category }} · {{ $document->original_name }}</small></td>
                        <td><a class="text-link" href="{{ route('projects.show', $document->project) }}#documents">{{ $document->project->title }}</a></td>
                        <td>{{ $document->created_at?->format('M d, Y') ?? '—' }}</td>
                        <td><a class="text-link" href="{{ route('documents.download', $document) }}">Download ↓</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
        @include('chapter.partials.pagination', ['items' => $documents])
    @else
        <div class="empty-state">
            <h3>{{ request()->filled('q') ? 'No matching files' : 'No project files yet' }}</h3>
            <p>{{ request()->filled('q') ? 'Try another title, category, or project name.' : 'Supporting files will appear here after they are uploaded from a project.' }}</p>
            <a class="btn secondary" href="{{ request()->filled('q') ? route('documents') : route('projects') }}">{{ request()->filled('q') ? 'Show all files' : 'Browse projects' }}</a>
        </div>
    @endif
</section>
@endsection
