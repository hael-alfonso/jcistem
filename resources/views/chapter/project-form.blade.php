@extends('layouts.chapter')
@section('title', $proposal ? 'Full project proposal' : 'Project concept letter')
@section('content')
@php
    $sectionLabels = [
        'Community need and intended impact' => 'Explain the need, who the project serves, and what success should look like.',
        'Delivery plan and people' => 'Describe the activities, schedule, committee, members, and partners.',
        'Resources and funding' => 'Give a line-item cost breakdown and identify realistic funding sources.',
        'Safeguards and follow-through' => 'Plan for risks, evidence, evaluation, and continued impact.',
    ];
    $proposalData = $project->proposal ?? [];
@endphp
<div class="page-heading chapter-form-heading">
    <div>
        <a class="back-link" href="{{ $project->exists ? route('projects.show', $project) : route('projects') }}"><x-icon name="arrowLeft" /> Projects</a>
        <div class="chapter-form-brand"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="JCI logo" width="92" height="52"><span>JCI Carmona<small>JCI Philippines</small></span></div>
        <div class="eyebrow">{{ $proposal ? 'STAGE 02 · DETAILED PLANNING' : 'STAGE 01 · PROJECT IDEA' }}</div>
        <h1>{{ $proposal ? 'Full project proposal' : 'Project concept letter' }}</h1>
        <p>{{ $proposal ? 'Build the complete plan for formal chapter review. The internal transmittal letter will use these details.' : 'Present your idea to the Chapter President for endorsement before preparing a full proposal.' }}</p>
    </div>
    @if($proposal && $project->exists)<a class="btn secondary" href="{{ route('projects.proposal', $project) }}"><x-icon name="eye" /> View &amp; print proposal</a>@endif
</div>
<div class="notice">{{ $proposal ? 'Save a draft before previewing. Formal approval is required before Chair assignment, implementation, or spending.' : 'Concept endorsement allows detailed planning. It does not authorize implementation or spending.' }}</div>
<form method="POST" action="{{ $project->exists ? route('projects.update', $project) : route('projects.store') }}">
    @csrf
    @if($project->exists) @method('PUT') @endif
    <section class="panel form-panel chapter-form-section">
        <div class="section-heading"><span>01</span><div><h2>Project essentials</h2><p>These details carry forward from the concept to the proposal and printable packet.</p></div></div>
        <div class="form-grid">
            <x-field name="title" label="Project title" :value="$project->title" required maxlength="200"/>
            <x-field name="area" label="JCI area of opportunity" type="select" :options="array_combine(\App\Support\ChapterForms::AREAS, \App\Support\ChapterForms::AREAS)" :value="$project->area" required/>
            <x-field name="starts_on" label="Proposed start date" type="date" :value="$project->starts_on?->format('Y-m-d')"/>
            <x-field name="ends_on" label="Proposed end date" type="date" :value="$project->ends_on?->format('Y-m-d')"/>
            <x-field name="venue" label="Proposed venue / location" :value="$project->venue"/>
            <x-field name="proposed_budget" label="Estimated total budget (PHP)" type="number" min="0" step="0.01" :value="$project->proposed_budget ?? 0" required/>
        </div>
    </section>
    @if($proposal)
        @foreach(\App\Support\ChapterForms::PROPOSAL_SECTIONS as $heading => $keys)
            <section class="panel form-panel chapter-form-section">
                <div class="section-heading"><span>{{ str_pad((string) ($loop->iteration + 1), 2, '0', STR_PAD_LEFT) }}</span><div><h2>{{ $heading }}</h2><p>{{ $sectionLabels[$heading] }}</p></div></div>
                <div class="form-grid">
                    @foreach($keys as $key)
                        <div class="full"><x-field :name="'proposal['.$key.']'" :label="\App\Support\ChapterForms::PROPOSAL[$key]" type="textarea" :value="$proposalData[$key] ?? ''" maxlength="15000"/></div>
                    @endforeach
                    @if($heading === 'Resources and funding')<x-field name="proposal[projected_income]" label="Projected income (PHP, if applicable)" type="number" min="0" step="0.01" :value="$proposalData['projected_income'] ?? ''"/>@endif
                </div>
            </section>
        @endforeach
        <section class="panel form-panel chapter-form-section">
            <div class="section-heading"><span>06</span><div><h2>Internal Board transmittal</h2><p>Optional routing details for the Internal Chapter Project Proposal Transmittal Letter.</p></div></div>
            <div class="form-grid">
                <x-field name="proposal[committee_director]" label="Committee director (if assigned)" :value="$proposalData['committee_director'] ?? ''" maxlength="200"/>
                <x-field name="proposal[board_meeting_date]" label="Requested Board meeting date" type="date" :value="$proposalData['board_meeting_date'] ?? ''"/>
            </div>
            <p class="hint">The proposal owner appears as project proponent. A Project Chair is formally assigned only after final approval.</p>
        </section>
    @else
        <section class="panel form-panel chapter-form-section">
            <div class="section-heading"><span>02</span><div><h2>The concept letter</h2><p>Keep this initial request concise. Detailed plans come after endorsement.</p></div></div>
            <div class="form-grid">
                @foreach(\App\Support\ChapterForms::CONCEPT as $key => $label)
                    <div class="full"><x-field :name="'concept['.$key.']'" :label="$label" type="textarea" :value="($project->concept ?? [])[$key] ?? ''" maxlength="15000"/></div>
                @endforeach
            </div>
        </section>
    @endif
    <div class="form-footer"><a class="btn secondary" href="{{ $project->exists ? route('projects.show', $project) : route('projects') }}"><x-icon name="close" /> Cancel</a><button class="btn primary"><x-icon name="save" /> Save {{ $proposal ? 'proposal' : 'concept' }} draft</button></div>
</form>
@endsection
