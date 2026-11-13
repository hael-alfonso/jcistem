<div class="project-page-group" id="proposal">
@if($project->proposal || $project->documents->where('submission_stage', 'proposal')->isNotEmpty())
<section class="panel form-panel project-proposal-panel">
    <div class="panel-heading flush"><div><span class="eyebrow">{{ $project->proposal_reviewed_at ? 'APPROVED PLAN' : 'PLAN IN PREPARATION OR REVIEW' }}</span><h2>Full project proposal</h2><p>The implementation plan, organized for quick review.</p></div><div class="button-row no-print">@if($canViewProjectFiles && $project->proposal_submission_method !== 'upload' && filled($project->proposal))<a class="btn secondary" href="{{ route('projects.word', [$project, 'proposal']) }}"><x-icon name="download" /> Download Word form</a>@endif @if($canViewProjectFiles && $project->proposal_submission_method !== 'upload' && $project->proposal)<a class="btn secondary" href="{{ route('projects.proposal', $project) }}"><x-icon name="eye" /> Print proposal packet</a>@endif</div></div>
    @if($project->proposal_submission_method === 'upload')
        <p class="hint">The Chair submitted the official completed template. Open it to review the exact plan.</p>
        @foreach($project->documents->where('submission_stage', 'proposal')->sortByDesc('version') as $document)@include('chapter.partials.project-document-row', ['document' => $document, 'label' => 'Proposal template · Version '.$document->version, 'meta' => $document->original_name])@endforeach
    @else
        <div class="project-record-details project-record-details-visible">
            <div class="project-module-heading"><span><strong>Complete project proposal</strong><small>Team, activities, resources, budget, risks, evaluation, and sustainability</small></span></div>
            <div class="project-record-groups">
                @foreach(\App\Support\ChapterForms::PROPOSAL_SECTIONS as $heading => $keys)
                    <section><h3>{{ $heading }}</h3><dl>@foreach($keys as $key)<div><dt>{{ \App\Support\ChapterForms::PROPOSAL[$key] }}</dt><dd class="preserve-lines">{{ $project->proposal[$key] ?? 'Not provided' }}</dd></div>@endforeach</dl></section>
                @endforeach
            </div>
        </div>
    @endif
</section>
@else
<section class="panel form-panel project-proposal-panel"><div class="panel-heading flush"><div><span class="eyebrow">PROJECT PLAN</span><h2>Full project proposal</h2><p>The full proposal becomes available after concept approval.</p></div></div><p class="panel-empty">No full project proposal has been submitted yet.</p></section>
@endif

<section class="panel form-panel partnership-documents proposal-partnership-support" id="partnerships" aria-labelledby="partnerships-title">
    <div class="panel-heading flush">
        <div><span class="eyebrow">OPTIONAL PROPOSAL SUPPORT</span><h2 id="partnerships-title">Partner commitments and letters</h2><p>Attach signed partner commitments that support the proposal’s partner plan. An outgoing LOI is prepared separately; upload the signed or received copy here.</p></div>
        <div class="button-row no-print">
            @if($manages || $project->canReport($u))<a class="btn secondary" href="{{ route('records.create', ['kind' => 'letters', 'project_id' => $project->id]) }}"><x-icon name="filePlus" /> Prepare external LOI</a>@endif
            <a class="btn secondary" href="{{ route('templates.reference.download', 'external-partnership-letter') }}"><x-icon name="download" /> Partner LOI template</a>
        </div>
    </div>
    @forelse($partnershipDocuments as $document)
        @include('chapter.partials.project-document-row', ['document' => $document, 'label' => $document->title.' · Version '.$document->version, 'meta' => $document->original_name.' · Uploaded '.($document->submitted_at?->format('d M Y, g:i A') ?? $document->created_at?->format('d M Y, g:i A'))])
    @empty
        <p class="panel-empty">No signed partner letters yet. Add one when an external partner has confirmed support; this is optional when the project has no external partner.</p>
    @endforelse
    @if($canUploadPartnership)
        <section class="record-detail no-print" aria-labelledby="partnership-upload-title">
            <div class="project-record-heading"><div><strong id="partnership-upload-title">Upload signed partner letter</strong><small>Store the partner’s completed commitment with the proposal support documents.</small></div></div>
            <form method="POST" action="{{ route('projects.partnerships.store', $project) }}" enctype="multipart/form-data" class="form-grid">
                @csrf
                <x-field name="title" label="Partner or letter title" required placeholder="Example: ABC Foundation — Letter of Support" />
                <x-field name="file" label="Signed DOC, DOCX, or PDF (max 10 MB)" type="file" accept=".doc,.docx,.pdf" required />
                <div class="full"><p class="hint">Each upload creates a retained version. This supporting letter is optional and does not replace the proposal’s partner plan.</p><button class="btn primary"><x-icon name="upload" /> Save signed letter</button></div>
            </form>
        </section>
    @endif
</section>
</div>
