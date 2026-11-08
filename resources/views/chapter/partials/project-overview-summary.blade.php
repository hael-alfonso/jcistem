<section class="panel form-panel project-overview-panel" id="overview">
    <div class="panel-heading flush">
        <div><span class="eyebrow">PROJECT SUMMARY</span><h2>Project overview</h2><p>Essential details and the official concept record in one clear view.</p></div>
        @if($project->concept_submission_method !== 'upload' && filled($project->concept))<a class="btn secondary no-print" href="{{ route('projects.word', [$project, 'concept']) }}"><x-icon name="download" /> Download concept letter</a>@endif
    </div>
    <div class="project-overview-letterhead"><img src="{{ asset('assets/images/brand/jci-primary.webp') }}" alt="JCI logo"><div><strong>JCI Carmona</strong><span>Internal project concept letter</span></div><small>{{ $project->reference ?? 'Project concept' }}</small></div>

    <div class="project-overview-lead">
        <span>Primary objective</span>
        <p class="preserve-lines">{{ $project->concept_submission_method === 'upload' ? 'The current project objective is in the uploaded official concept below.' : ($project->concept['objective'] ?? 'The primary project objective has not been provided.') }}</p>
    </div>
    <dl class="project-overview-facts">
        <div><dt>Area of opportunity</dt><dd>{{ $project->area }}</dd></div>
        <div><dt>Project Chair</dt><dd>{{ $project->chair?->name ?? 'Assigned after approval' }}</dd></div>
        <div><dt>Schedule</dt><dd>{{ $project->starts_on?->format('M d, Y') ?? 'To be set' }}@if($project->ends_on) – {{ $project->ends_on->format('M d, Y') }}@endif</dd></div>
        <div><dt>Venue</dt><dd>{{ $project->venue ?: 'To be set' }}</dd></div>
        <div><dt>Beneficiaries</dt><dd>{{ $project->concept_submission_method === 'upload' ? 'See uploaded official concept' : ($project->concept['beneficiaries'] ?? 'Not provided') }}</dd></div>
        <div><dt>SDG alignment</dt><dd>@if($project->concept_submission_method === 'upload')See uploaded official concept @elseif(filled($project->concept['sdg_number'] ?? null))SDG {{ $project->concept['sdg_number'] }} · {{ $project->concept['sdg_name'] ?? 'Name not provided' }}@else Not provided @endif</dd></div>
    </dl>

    @if($project->concept_submission_method === 'upload')
        <div class="project-record-block"><h3>Uploaded official concept</h3><p>Open the submitted template to review the complete signed record.</p>@foreach($project->documents->where('submission_stage', 'concept')->sortByDesc('version') as $document)@include('chapter.partials.project-document-row', ['document' => $document, 'label' => 'Concept template · Version '.$document->version, 'meta' => $document->original_name])@endforeach</div>
    @else
        <div class="project-record-details" aria-labelledby="concept-record-title">
            <div class="project-record-heading"><div><strong id="concept-record-title">Complete concept letter</strong><small>Recipient, resources, formal request, and signatory details</small></div></div>
            <p class="letter-recipient">To: JCI Carmona Chapter President<br>From: {{ $project->owner?->name }}<br>Subject: Proposed Project — {{ $project->title }}</p>
            <div class="project-record-groups">
                @foreach(\App\Support\ChapterForms::CONCEPT_SECTIONS as $heading => $keys)
                    <section><h3>{{ $heading }}</h3><dl>@foreach($keys as $key)<div><dt>{{ \App\Support\ChapterForms::CONCEPT[$key] }}</dt><dd class="preserve-lines">{{ $project->concept[$key] ?? 'Not provided' }}</dd></div>@endforeach</dl></section>
                @endforeach
            </div>
        </div>
    @endif
</section>
