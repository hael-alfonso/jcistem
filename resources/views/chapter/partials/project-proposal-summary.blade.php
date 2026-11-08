@if($project->proposal || $project->documents->where('submission_stage', 'proposal')->isNotEmpty())
<section class="panel form-panel project-proposal-panel" id="proposal">
    <div class="panel-heading flush"><div><span class="eyebrow">{{ $project->proposal_reviewed_at ? 'APPROVED PLAN' : 'PLAN IN PREPARATION OR REVIEW' }}</span><h2>Full project proposal</h2><p>The implementation plan, organized for quick review.</p></div><div class="button-row no-print">@if($project->proposal_submission_method !== 'upload' && filled($project->proposal))<a class="btn secondary" href="{{ route('projects.word', [$project, 'proposal']) }}"><x-icon name="download" /> Download Word form</a>@endif @if($project->proposal_submission_method !== 'upload' && $project->proposal)<a class="btn secondary" href="{{ route('projects.proposal', $project) }}"><x-icon name="eye" /> Print proposal packet</a>@endif</div></div>
    @if($project->proposal_submission_method === 'upload')
        <p class="hint">The Chair submitted the official completed template. Open it to review the exact plan.</p>
        @foreach($project->documents->where('submission_stage', 'proposal')->sortByDesc('version') as $document)@include('chapter.partials.project-document-row', ['document' => $document, 'label' => 'Proposal template · Version '.$document->version, 'meta' => $document->original_name])@endforeach
    @else
        <div class="project-proposal-highlights">
            @foreach(['rationale' => 'Why this project is needed', 'objectives' => 'Goals and objectives', 'beneficiaries' => 'Who the project serves', 'timeline' => 'Timeline and milestones'] as $key => $label)
                <article><h3>{{ $label }}</h3><p class="preserve-lines">{{ $project->proposal[$key] ?? 'Not provided' }}</p></article>
            @endforeach
        </div>
        <details class="project-record-details">
            <summary><span><strong>View complete project proposal</strong><small>Team, activities, resources, budget, risks, evaluation, and sustainability</small></span><x-icon name="chevron" /></summary>
            <div class="project-record-groups">
                @foreach(\App\Support\ChapterForms::PROPOSAL_SECTIONS as $heading => $keys)
                    <section><h3>{{ $heading }}</h3><dl>@foreach($keys as $key)<div><dt>{{ \App\Support\ChapterForms::PROPOSAL[$key] }}</dt><dd class="preserve-lines">{{ $project->proposal[$key] ?? 'Not provided' }}</dd></div>@endforeach</dl></section>
                @endforeach
            </div>
        </details>
    @endif
</section>
@else
<section class="panel form-panel project-proposal-panel" id="proposal"><div class="panel-heading flush"><div><span class="eyebrow">PROJECT PLAN</span><h2>Full project proposal</h2><p>The full proposal becomes available after concept approval.</p></div></div><p class="panel-empty">No full project proposal has been submitted yet.</p></section>
@endif
