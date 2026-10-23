@extends('layouts.chapter')
@section('title', 'Internal Chapter Project Proposal Transmittal Letter')
@section('content')
@php
    $proposal = $project->proposal ?? [];
    $submittedReview = $project->reviews->where('action', 'submit_proposal')->sortByDesc('id')->first();
    $documentDate = $submittedReview?->created_at ?? $project->updated_at;
    $draft = in_array($project->status, ['Full Proposal Draft', 'Returned for Revision'], true);
    $objectives = array_values(array_filter(array_map(
        fn ($line) => trim(preg_replace('/^\s*(?:[-*]|\d+[.)])\s*/', '', $line)),
        preg_split('/\r\n|\r|\n/', $proposal['objectives'] ?? '') ?: []
    )));
@endphp
<div class="page-heading no-print"><div><a class="back-link" href="{{ route('projects.show', $project) }}"><x-icon name="arrowLeft" /> {{ $project->title }}</a><div class="eyebrow">INTERNAL CHAPTER DOCUMENT · {{ $project->reference }}</div><h1>Project proposal packet</h1><p>Read the transmittal and full proposal together. Print or save this packet as a PDF for the Board.</p></div><button class="btn primary" type="button" data-print><x-icon name="print" /> Print / save PDF</button></div>
@if($draft)<div class="notice no-print">Draft preview. Save and submit the full proposal for formal review before treating this as an official Board submission.</div>@endif
<div class="project-document">
    <article class="document-page">
        <header class="project-letterhead"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="JCI logo" width="140" height="78"><div><strong>JCI Carmona</strong><span>JCI Philippines · Carmona City, Cavite</span></div></header>
        <p class="document-overline">INTERNAL LOM MEMORANDUM @if($draft)<span>· DRAFT</span>@endif</p>
        <h2>Internal Chapter Project Proposal Transmittal Letter</h2>
        <dl class="document-routing">
            <div><dt>To</dt><dd>Board of Directors, JCI Carmona</dd></div>
            <div><dt>From</dt><dd>{{ $project->owner?->name ?? 'Project proponent' }}, Project Proponent (proposed Project Chairperson)@if($proposal['committee_director'] ?? null)<br>{{ $proposal['committee_director'] }}, Committee Director @endif</dd></div>
            <div><dt>Date</dt><dd>{{ $documentDate?->format('F j, Y') ?? 'To be confirmed' }}</dd></div>
            <div><dt>Subject</dt><dd>Official Project Proposal Submission – {{ $project->title }}</dd></div>
        </dl>
        <div class="document-copy">
            <p>Dear Board of Directors,</p>
            <p>I am presenting the full proposal for <strong>{{ $project->title }}</strong> for the Board's formal review, deliberation, and approval. It follows the endorsed project concept and supports JCI Carmona's work in leadership development and community action.</p>
            <h3>Project overview</h3>
            <dl class="document-facts">
                <div><dt>Area of opportunity</dt><dd>{{ $project->area }}</dd></div>
                <div><dt>Target community / beneficiaries</dt><dd>{{ $proposal['beneficiaries'] ?? 'To be completed' }}</dd></div>
                <div><dt>Proposed execution</dt><dd>{{ $project->starts_on?->format('F j, Y') ?? 'Date to be confirmed' }}@if($project->ends_on && (!$project->starts_on || !$project->ends_on->equalTo($project->starts_on))) – {{ $project->ends_on->format('F j, Y') }}@endif · {{ $project->venue ?: 'Venue to be confirmed' }}</dd></div>
            </dl>
            <h3>Core objectives</h3>
            @if($objectives)<ol>@foreach($objectives as $objective)<li>{{ $objective }}</li>@endforeach</ol>@else<p>Objectives will be completed in the proposal below.</p>@endif
            <h3>Financial and logistics summary</h3>
            <p>The proposed total budget is <strong>₱{{ number_format((float) $project->proposed_budget, 2) }}</strong>. @if(isset($proposal['projected_income']) && $proposal['projected_income'] !== '')Projected income is ₱{{ number_format((float) $proposal['projected_income'], 2) }}. @endif Proposed funding sources: {{ $proposal['funding'] ?? 'To be completed' }}</p>
            <p>Resources and logistics: {{ $proposal['resources'] ?? 'To be completed' }}</p>
            <p>Risks and contingencies: {{ $proposal['risks'] ?? 'To be completed' }}</p>
            <p>The complete proposal below includes the action plan, committee structure, proposed line-item budget, partner plan, evaluation approach, and other supporting details. @if($proposal['board_meeting_date'] ?? null) I request that it be placed on the agenda for the Board meeting on {{ \Carbon\Carbon::parse($proposal['board_meeting_date'])->format('F j, Y') }}. @else I request that it be placed on an upcoming Board meeting agenda for discussion. @endif</p>
            <p>Thank you for your guidance and consideration in advancing JCI Carmona's mission to create positive change.</p>
            <p>Respectfully submitted,</p>
            <div class="document-signature"><span>Signature</span><strong>{{ $project->owner?->name ?? 'Project proponent' }}</strong><small>Project Proponent (proposed Project Chairperson)<br>JCI Carmona</small></div>
        </div>
        <footer class="document-page-footer">{{ $project->reference }} · Internal chapter review · {{ $project->status }}</footer>
    </article>
    <article class="document-page proposal-attachment">
        <header class="project-letterhead"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="JCI logo" width="140" height="78"><div><strong>JCI Carmona</strong><span>JCI Philippines · Carmona City, Cavite</span></div></header>
        <p class="document-overline">ATTACHMENT A · PROJECT PLANNING FORM</p>
        <h2>Full Project Proposal</h2>
        <dl class="document-facts proposal-summary">
            <div><dt>Project / reference</dt><dd>{{ $project->title }} · {{ $project->reference }}</dd></div>
            <div><dt>Area of opportunity</dt><dd>{{ $project->area }}</dd></div>
            <div><dt>Proponent</dt><dd>{{ $project->owner?->name ?? 'Not assigned' }}</dd></div>
            <div><dt>Execution date / venue</dt><dd>{{ $project->starts_on?->format('F j, Y') ?? 'To be confirmed' }} · {{ $project->venue ?: 'To be confirmed' }}</dd></div>
            <div><dt>Estimated total budget</dt><dd>₱{{ number_format((float) $project->proposed_budget, 2) }}</dd></div>
            @if(isset($proposal['projected_income']) && $proposal['projected_income'] !== '')<div><dt>Projected income</dt><dd>₱{{ number_format((float) $proposal['projected_income'], 2) }}</dd></div>@endif
            <div><dt>Review status</dt><dd>{{ $project->status }}</dd></div>
        </dl>
        @foreach(\App\Support\ChapterForms::PROPOSAL_SECTIONS as $heading => $keys)
            <section class="proposal-document-section">
                <h3>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} · {{ $heading }}</h3>
                @foreach($keys as $key)
                    <div class="proposal-document-field"><h4>{{ \App\Support\ChapterForms::PROPOSAL[$key] }}</h4><p class="preserve-lines">{{ $proposal[$key] ?? 'To be completed' }}</p></div>
                @endforeach
            </section>
        @endforeach
        @if($project->documents->isNotEmpty())<section class="proposal-document-section"><h3>Supporting documents in JCISTEM</h3><ul>@foreach($project->documents as $document)<li>{{ $document->title }}</li>@endforeach</ul><p class="document-note">These files are stored separately in the project workspace; include them when distributing this packet.</p></section>@endif
        <footer class="document-page-footer">{{ $project->reference }} · Full project proposal · {{ $project->status }}</footer>
    </article>
</div>
@endsection
