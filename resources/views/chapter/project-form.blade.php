@extends('layouts.chapter')
@section('title', $proposal ? 'Project proposal form' : 'Submit project proposal')
@section('content')
@php
    $sectionLabels = [
        'Background and rationale' => 'Explain the need, local priorities, and relevant SDG alignment.',
        'Goals and objectives' => 'State clear, measurable objectives for this project.',
        'Target beneficiaries' => 'Identify the community, location, and expected number of people served.',
        'Estimated JCI members and their functions' => 'List the chair, co-chair, committee members, and their responsibilities.',
        'Plan of action' => 'Describe survey, planning, implementation, and evaluation phases.',
        'Estimated budget and funding source' => 'Itemize expected income and expenditures in Philippine pesos.',
        'Potential benefits and general comments' => 'Explain member development, community impact, and follow-through.',
    ];
    $fieldHints = [
        'letter_date' => 'Use the date that should appear on the official internal letter.',
        'president_name' => 'Enter the full name of the current JCI Carmona Local Organization President.',
        'chapter_address' => 'Enter the chapter mailing address shown beneath the recipient.',
        'sdg_number' => 'Example: 4',
        'sdg_name' => 'Example: Quality Education',
        'objective' => 'State the core project goal in one clear, action-focused statement.',
        'beneficiaries' => 'Identify the target group and expected number of participants or recipients.',
        'committee_size' => 'Enter the number of active JCI members needed for the committee.',
        'funding_source' => 'Example: chapter allocation, fundraising, and approved partner support.',
        'resources' => 'Add any venue, equipment, materials, permits, or volunteer requirements.',
        'request' => 'State the documents attached and formally request Board review and approval.',
        'proponent_position' => 'Example: General Member, Commission Vice President, or Project Chair.',
        'contact_info' => 'Enter the email address or mobile number that should appear in the letter.',
        'objectives' => "a. Specific, measurable objective\nb. Specific, measurable objective\nc. Specific, measurable objective",
        'team' => "Project Chair | Member name | Overall coordination\nCo-Chair | Member name | Documentation and reporting\nCommittee Member | Member name | Main responsibility",
        'activities' => "1. Survey and analysis | Community assessment and project scope\n2. Planning and organization | Board approvals, permits, and partners\n3. Implementation | Activities and delivery\n4. Evaluation and post-activity | Feedback, liquidation, and reporting",
        'funding' => 'Explain the income sources and any conditions attached to sponsorships.',
        'budget' => 'Describe the quantities, unit costs, and assumptions behind the expenditure lines below.',
    ];
    $proposalData = $project->proposal ?? [];
    $budgetRows = old('budget_items');
    if (!is_array($budgetRows)) $budgetRows = $project->budget_items ?? [];
    if (!$budgetRows && (float) ($project->proposed_budget ?? 0) > 0) $budgetRows = [['category' => 'other', 'description' => 'General project expenses (add the specific breakdown)', 'quantity' => '1.00', 'unit_cost' => $project->proposed_budget, 'amount' => $project->proposed_budget]];
    if (!$budgetRows) $budgetRows = [['category' => '', 'description' => '', 'quantity' => '1.00', 'unit_cost' => '', 'amount' => '0.00']];
    $displayedBudgetTotal = collect($budgetRows)->sum(fn ($item) => (float) ($item['amount'] ?? ((float) ($item['quantity'] ?? 0) * (float) ($item['unit_cost'] ?? 0))));
    $submissionStage = $proposal ? 'proposal' : 'concept';
    $submissionField = $submissionStage.'_submission_method';
    $submissionMethod = old($submissionField, $project->{$submissionField} ?: 'online');
    $submissionVersions = $project->exists
        ? $project->documents()->where('submission_stage', $submissionStage)->latest('version')->get()
        : collect();
@endphp
<div class="project-entry-layout">
<div class="page-heading chapter-form-heading">
    <div>
        <a class="back-link" href="{{ $project->exists ? route('projects.show', $project) : route('projects') }}"><x-icon name="arrowLeft" /> Projects</a>
        <div class="eyebrow">{{ $proposal ? 'STAGE 02 · DETAILED PLANNING' : 'STAGE 01 · PROJECT IDEA' }}</div>
        <h1>{{ $proposal ? 'Project proposal form' : 'Internal project proposal letter' }}</h1>
        <p>{{ $proposal ? 'Complete the official form, save your draft, then lock the plan from the project workspace.' : 'Start with the essential details, then complete the internal project letter online or upload the official template.' }}</p>
    </div>
    @if($proposal && $project->exists && $project->proposal)<a class="btn secondary" href="{{ route('projects.proposal', $project) }}"><x-icon name="eye" /> View &amp; print proposal</a>@endif
</div>
<div class="project-entry-steps" aria-label="Form progress">
    <span><b>1</b> Project details</span><span><b>2</b> Choose a method</span><span><b>3</b> Complete the {{ $proposal ? 'proposal' : 'letter' }}</span>
</div>
<div class="notice project-entry-notice">{{ $proposal ? 'The BOD-approved budget is locked. Review the printable packet before locking the plan. Paper signatures do not replace system approvals.' : 'Saving creates a draft for Admin, Treasurer, and BOD review. Implementation and spending still require a recorded BOD resolution and locked project plan.' }}</div>
<form class="project-entry-form" method="POST" enctype="multipart/form-data" action="{{ $project->exists ? route('projects.update', $project) : route('projects.store') }}" data-project-submission-form>
    @csrf
    @if($project->exists) @method('PUT') @endif
    <section class="panel form-panel chapter-form-section" id="proposal-general">
        <div class="section-heading"><span>01</span><div><h2>{{ $proposal ? 'General information' : 'Project essentials' }}</h2><p>{{ $proposal ? 'These details appear at the top of the official proposal form.' : 'These details carry forward from the concept to the proposal and printable packet.' }}</p></div></div>
        <div class="proposal-form-meta">
            @if($proposal)<div><span>Project chair</span><strong>{{ $project->chair?->name ?? $project->owner?->name ?? 'To be designated' }}</strong></div><div><span>Proposal reference</span><strong>{{ $project->reference ?? 'Assigned after saving' }}</strong></div>
            @else<div><span>Addressed to</span><strong>JCI Carmona President and Board of Directors</strong></div><div><span>Project proponent</span><strong>{{ auth()->user()->name }}</strong></div>@endif
        </div>
        <div class="form-grid project-entry-details">
            <div class="full"><x-field name="title" label="Project title" :value="$project->title" required maxlength="200" placeholder="Enter a clear, specific project name"/></div>
            <x-field name="area" label="JCI area of opportunity" type="select" :options="array_combine(\App\Support\ChapterForms::AREAS, \App\Support\ChapterForms::AREAS)" :value="$project->area" required/>
            <x-field name="starts_on" label="Proposed start date" type="date" :value="$project->starts_on?->format('Y-m-d')"/>
            <x-field name="ends_on" label="Proposed end date" type="date" :value="$project->ends_on?->format('Y-m-d')"/>
            <div class="full"><x-field name="venue" label="Proposed venue / location" :value="$project->venue" placeholder="Barangay, venue, or community location"/></div>
        </div>
        @include('chapter.partials.project-budget-builder')
    </section>
    <section class="panel form-panel chapter-form-section submission-method-section">
        <div class="section-heading"><span>02</span><div><h2>How to complete this form</h2><p>{{ $proposal ? 'Use the online fields or upload the completed official proposal form.' : 'Both methods enter the same Admin, Treasurer, and BOD review workflow.' }}</p></div></div>
        <div class="submission-method-options" role="radiogroup" aria-label="Submission method">
            <label><input type="radio" name="{{ $submissionField }}" value="online" @checked($submissionMethod === 'online')> <span><strong>Fill out online</strong><small>Complete and revise the structured form in the PMS.</small></span></label>
            <label><input type="radio" name="{{ $submissionField }}" value="upload" @checked($submissionMethod === 'upload')> <span><strong>Upload completed template</strong><small>Upload a completed DOCX or PDF while keeping the project details searchable.</small></span></label>
        </div>
    </section>
    <div data-submission-panel="online" @if($submissionMethod !== 'online') hidden @endif>
    <div class="online-document-tools no-print">
        <div><span class="eyebrow">OFFICIAL WORD FORMAT</span><strong>Your answers download in the official {{ $proposal ? 'Project Proposal Form' : 'Internal Project Proposal Letter' }} layout.</strong><small>Save the draft first, then download a filled editable DOCX. You can also download the blank template at any time.</small></div>
        <div><a class="btn secondary" href="{{ route('templates.reference.download', $proposal ? 'project-proposal-form' : 'internal-project-letter') }}"><x-icon name="download" /> Blank template</a>@if($project->exists && filled($proposal ? $project->proposal : $project->concept))<a class="btn primary" href="{{ route('projects.word', [$project, $proposal ? 'proposal' : 'concept']) }}"><x-icon name="filePlus" /> Download filled Word copy</a>@endif</div>
    </div>
    @if($proposal)
        <nav class="proposal-form-toc" aria-label="Proposal form sections">
            <strong>FORM SECTIONS</strong>
            @foreach(\App\Support\ChapterForms::PROPOSAL_SECTIONS as $heading => $keys)
                <a href="#proposal-section-{{ $loop->iteration }}">{{ $loop->iteration }}. {{ $heading }}</a>
            @endforeach
        </nav>
        @foreach(\App\Support\ChapterForms::PROPOSAL_SECTIONS as $heading => $keys)
            @php
                $primaryKeys = array_values(array_intersect($keys, array_keys(\App\Support\ChapterForms::PROPOSAL_REQUIRED)));
                $supportingKeys = array_values(array_diff($keys, $primaryKeys));
                $hasSupportingValues = collect($supportingKeys)->contains(fn ($key) => filled(old('proposal.'.$key, $proposalData[$key] ?? null)));
            @endphp
            <section class="panel form-panel chapter-form-section proposal-form-section" id="proposal-section-{{ $loop->iteration }}">
                <div class="section-heading"><span>{{ str_pad((string) ($loop->iteration + 2), 2, '0', STR_PAD_LEFT) }}</span><div><small>OFFICIAL FORM - SECTION {{ $loop->iteration }}</small><h2>{{ $heading }}</h2><p>{{ $sectionLabels[$heading] }}</p></div></div>
                <div class="form-grid">
                    @foreach($primaryKeys as $key)
                        <div class="full"><x-field :name="'proposal['.$key.']'" :label="\App\Support\ChapterForms::PROPOSAL[$key]" type="textarea" :value="$proposalData[$key] ?? ''" :placeholder="$fieldHints[$key] ?? ''" maxlength="15000"/></div>
                    @endforeach
                </div>
                @if($supportingKeys)
                    <details class="proposal-supporting-fields" @if($hasSupportingValues) open @endif><summary>Additional planning details <span>Optional</span></summary><div class="form-grid">
                        @foreach($supportingKeys as $key)
                            <div class="full"><x-field :name="'proposal['.$key.']'" :label="\App\Support\ChapterForms::PROPOSAL[$key]" type="textarea" :value="$proposalData[$key] ?? ''" :placeholder="$fieldHints[$key] ?? ''" maxlength="15000"/></div>
                        @endforeach
                    </div></details>
                @endif
                @if($heading === 'Estimated budget and funding source')
                    <div class="proposal-finance-grid">
                        <div class="proposal-finance-card">
                            <h3>Estimated income</h3><p>Enter each expected source from the official form.</p>
                            @foreach(\App\Support\ChapterForms::PROPOSAL_INCOME_LINES as $key => $label)
                                <x-field :name="'proposal[income_lines]['.$key.']'" :label="$label.' (PHP)'" type="number" min="0" step="0.01" :value="data_get($proposalData, 'income_lines.'.$key, '')" />
                            @endforeach
                            <div class="proposal-finance-total"><span>Total estimated income</span><strong data-proposal-income-total>PHP 0.00</strong></div>
                        </div>
                        <div class="proposal-finance-card proposal-budget-reference">
                            <h3>Estimated expenditures</h3><p>The detailed expense list in Project essentials is the single budget source for this proposal.</p>
                            @foreach(\App\Support\ProjectBudget::officialExpenseLines($budgetRows) as $key => $amount)
                                <div><span>{{ \App\Support\ChapterForms::PROPOSAL_EXPENSE_LINES[$key] }}</span><strong>PHP {{ number_format((float) $amount, 2) }}</strong></div>
                            @endforeach
                            <div class="proposal-finance-total"><span>Total estimated expenditures</span><strong>PHP {{ number_format($displayedBudgetTotal, 2) }}</strong></div>
                        </div>
                    </div>
                    <p class="hint">The expenditure estimate should match the BOD-approved budget of PHP {{ number_format((float) $project->proposed_budget, 2) }}. A budget amendment needs separate authorization.</p>
                @endif
            </section>
        @endforeach
        <section class="panel form-panel chapter-form-section">
            <div class="section-heading"><span>10</span><div><h2>Board transmittal and signatures</h2><p>The printable packet includes prepared, reviewed, and approved signature lines.</p></div></div>
            <div class="form-grid">
                <x-field name="proposal[committee_director]" label="Committee director (if assigned)" :value="$proposalData['committee_director'] ?? ''" maxlength="200"/>
                <x-field name="proposal[president_name]" label="Local Organization President" :value="$proposalData['president_name'] ?? ''" maxlength="200"/>
                <x-field name="proposal[board_meeting_date]" label="Requested Board meeting date" type="date" :value="$proposalData['board_meeting_date'] ?? ''"/>
            </div>
            <p class="hint">Paper signatures do not change PMS permissions or approval status.</p>
        </section>
    @else
        @foreach(\App\Support\ChapterForms::CONCEPT_SECTIONS as $heading => $keys)
            <section class="panel form-panel chapter-form-section proposal-form-section">
                <div class="section-heading"><span>{{ str_pad((string) ($loop->iteration + 2), 2, '0', STR_PAD_LEFT) }}</span><div><small>INTERNAL LETTER - SECTION {{ $loop->iteration }}</small><h2>{{ $heading }}</h2><p>{{ $loop->first ? 'Explain why the project matters and how it supports active citizenship.' : ($loop->last ? 'State the proposed resources and formal request to the Board.' : 'Summarize the proposed date, location, beneficiaries, and partners.') }}</p></div></div>
                <div class="form-grid">
                    @foreach($keys as $key)
                        @php
                            $conceptTextarea = in_array($key, ['objective', 'beneficiaries', 'resources', 'request'], true);
                            $conceptType = $key === 'letter_date' ? 'date' : ($conceptTextarea ? 'textarea' : 'text');
                            $conceptRequired = array_key_exists($key, \App\Support\ChapterForms::CONCEPT_REQUIRED);
                        @endphp
                        <div class="{{ $conceptTextarea ? 'full' : '' }}"><x-field :name="'concept['.$key.']'" :label="\App\Support\ChapterForms::CONCEPT[$key]" :type="$conceptType" :value="($project->concept ?? [])[$key] ?? ''" :placeholder="$fieldHints[$key] ?? ''" :required="$conceptRequired" maxlength="15000"/></div>
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif
    </div>
    <section class="panel form-panel chapter-form-section" data-submission-panel="upload" @if($submissionMethod !== 'upload') hidden @endif>
        <div class="section-heading"><span>03</span><div><h2>Completed template</h2><p>Use the official form from Form Templates. New uploads are retained as a version history.</p></div></div>
        <a class="btn secondary proposal-template-download" href="{{ route('templates.reference.download', $proposal ? 'project-proposal-form' : 'internal-project-letter') }}"><x-icon name="download" /> Download {{ $proposal ? 'Project Proposal Form' : 'Internal Project Letter' }}</a>
        <div class="form-grid">
            <x-field name="submission_file" label="Completed DOCX or PDF (max 10 MB)" type="file" accept=".doc,.docx,.pdf"/>
            <x-field name="revision_note" label="Version or revision note" type="textarea" maxlength="2000"/>
        </div>
        @if($submissionVersions->isNotEmpty())
            <div class="submission-version-list">
                <h3>Uploaded versions</h3>
                @foreach($submissionVersions as $document)
                    <a class="list-row" href="{{ route('documents.download', $document) }}"><span><strong>Version {{ $document->version }}</strong><small>{{ $document->original_name }} · {{ $document->submitted_at?->format('d M Y, g:i A') ?? $document->created_at->format('d M Y, g:i A') }}</small></span><span class="text-link"><x-icon name="download"/> Download</span></a>
                @endforeach
            </div>
        @else
            <p class="hint">A completed template is required before this draft can be submitted for review.</p>
        @endif
    </section>
    <div class="form-footer project-entry-actions"><span>Save now and continue editing from the project workspace.</span><div><a class="btn secondary" href="{{ $project->exists ? route('projects.show', $project) : route('projects') }}"><x-icon name="close" /> Cancel</a><button class="btn primary"><x-icon name="save" /> Save {{ $proposal ? 'proposal' : 'concept' }} draft</button></div></div>
</form>
</div>
@endsection
