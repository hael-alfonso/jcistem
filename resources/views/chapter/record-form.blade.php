@extends('layouts.chapter')
@section('title', $kind === 'letters' ? 'External partnership LOI' : 'Prepare report')
@section('content')
@php
    $letterTypeOptions = $record->type === 'External Partner Letter'
        ? ['External Partner Letter' => 'External partnership LOI']
        : ['JCI LOI' => 'External partnership LOI'];
    $letterData = $record->data ?? [];
    $reportMethod = old('data.submission_method', $record->data['submission_method'] ?? 'digital');
    $supportsReportUpload = $kind === 'reports' && $project && in_array($record->type, ['Completion', 'Liquidation'], true);
@endphp
<div class="page-heading">
    <div>
        <a class="back-link" href="{{ $project ? route('projects.show', $project).'#completion' : route('records', $kind) }}"><x-icon name="arrowLeft" /> {{ $project ? $project->title : ($kind === 'letters' ? 'External LOIs' : 'Project Reports') }}</a>
        <div class="chapter-form-brand"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="JCI logo" width="92" height="52"><span>JCI Carmona<small>JCI Philippines</small></span></div>
        <div class="eyebrow">{{ $project?->reference ?? 'CHAPTER FINANCES' }}</div>
        <h1>{{ $kind === 'letters' ? 'External partnership LOI' : 'Prepare report' }}</h1>
        <p>{{ $kind === 'letters' ? 'Introduce the project and make a clear, specific request to an outside partner.' : ($project?->title ?? 'Overall Financial Report') }}</p>
    </div>
    @if($kind === 'letters' && $project?->proposal)<a class="btn secondary" href="{{ route('projects.proposal', $project) }}"><x-icon name="eye" /> View project proposal</a>@endif
</div>
@if($kind === 'letters')<div class="notice">This letter goes to an external organization after Admin review. The internal Board transmittal belongs to the project proposal packet.</div>@endif
<form method="POST" action="{{ $record->exists ? route('records.update', [$kind, $record->id]) : route('records.store', $kind) }}" enctype="multipart/form-data">
    @csrf
    @if($record->exists) @method('PUT') @endif
    <input type="hidden" name="project_id" value="{{ $project?->id }}">
    @if($kind === 'letters')
        <section class="panel form-panel chapter-form-section">
            <div class="section-heading"><span>01</span><div><h2>Letter and recipient</h2><p>Use the recipient's official name, title, and organization details.</p></div></div>
            <div class="form-grid">
                <div class="full"><x-field name="title" label="Letter subject / document title" :value="$record->title" required maxlength="200"/></div>
                <x-field name="type" label="Document type" type="select" :options="$letterTypeOptions" :value="$record->type" required/>
                <x-field name="data[letter_date]" label="Letter date" type="date" :value="$letterData['letter_date'] ?? ''"/>
                <x-field name="data[recipient]" label="Recipient name" :value="$letterData['recipient'] ?? ''"/>
                <x-field name="data[recipient_title]" label="Recipient title / position" :value="$letterData['recipient_title'] ?? ''"/>
                <x-field name="data[organization]" label="Company, organization, or LGU" :value="$letterData['organization'] ?? ''"/>
                <x-field name="partner_id" label="Existing partner (optional)" type="select" :options="['' => 'New partner / enter organization below'] + $partners->pluck('name', 'id')->all()" :value="$record->partner_id"/>
                <div class="full"><x-field name="data[address]" label="Mailing address" type="textarea" :value="$letterData['address'] ?? ''"/></div>
            </div>
        </section>
        <section class="panel form-panel chapter-form-section">
            <div class="section-heading"><span>02</span><div><h2>Project case</h2><p>State the need, the action, and who benefits. Project dates and venue come from the saved project snapshot.</p></div></div>
            <div class="form-grid">
                <div class="full"><x-field name="data[purpose]" label="Community problem or need" type="textarea" :value="$letterData['purpose'] ?? ''"/></div>
                <div class="full"><x-field name="data[project_action]" label="Proposed project action or solution" type="textarea" :value="$letterData['project_action'] ?? ''"/></div>
                <x-field name="data[target_location]" label="Target community or location" :value="$letterData['target_location'] ?? ''"/>
                <x-field name="data[beneficiary_count]" label="Estimated number of beneficiaries" :value="$letterData['beneficiary_count'] ?? ''"/>
            </div>
        </section>
        <section class="panel form-panel chapter-form-section">
            <div class="section-heading"><span>03</span><div><h2>Partnership request</h2><p>Specify the support sought and offer only recognition the chapter can actually deliver.</p></div></div>
            <div class="form-grid">
                <div class="full"><x-field name="data[partner_alignment]" label="Why this partner is a good fit" type="textarea" :value="$letterData['partner_alignment'] ?? ''"/></div>
                <div class="full"><x-field name="data[support]" label="Specific support requested" type="textarea" :value="$letterData['support'] ?? ''"/></div>
                <div class="full"><x-field name="data[commitments]" label="Recognition or benefits the chapter can provide" type="textarea" :value="$letterData['commitments'] ?? ''"/></div>
                <div class="full"><p class="hint">Examples from your template: logo placement, acknowledgment at the event, and a certificate or impact report. Include only agreed, feasible benefits.</p></div>
            </div>
        </section>
        <section class="panel form-panel chapter-form-section">
            <div class="section-heading"><span>04</span><div><h2>Signatories and enclosure</h2><p>The printable letter includes spaces for the project chair and Local Organization President to sign.</p></div></div>
            <div class="form-grid">
                <x-field name="data[signatory]" label="Proposed project chair / signatory name" :value="$letterData['signatory'] ?? ''"/>
                <x-field name="data[president_name]" label="Local Organization President name" :value="$letterData['president_name'] ?? ''"/>
                <div class="full"><x-field name="data[body]" label="Additional message (optional)" type="textarea" :value="$letterData['body'] ?? ''"/></div>
                <div class="full"><x-field name="attachment" label="Supporting file (optional; max 10 MB)" type="file"/></div>
            </div>
            <p class="hint">Submit the completed LOI to Admin. Print the approved letter for signing and hard-copy delivery to the partner. Earlier versions are retained.</p>
        </section>
    @else
        <section class="panel form-panel chapter-form-section">
            <div class="section-heading"><span>01</span><div><h2>Report details</h2><p>Save a draft, then submit the completed report for review.</p></div></div>
            <div class="form-grid">
                <x-field name="title" label="Document title / subject" :value="$record->title" required/>
                <div class="field"><span>Report type</span><strong class="report-type-value">{{ $record->type === 'Completion' ? 'Project Completion Report (PCR)' : ($record->type === 'Liquidation' ? 'Liquidation Report' : 'Overall Financial Report') }}</strong><input type="hidden" name="type" value="{{ $record->type }}">@if($project && !$record->exists)<small>Need the other format? <a class="text-link" href="{{ route('records.create', ['kind' => 'reports', 'project_id' => $project->id, 'type' => $record->type === 'Completion' ? 'Liquidation' : 'Completion']) }}">Switch report type</a></small>@endif</div>
            </div>
        </section>
        @if($project)
            @if($supportsReportUpload)
                <div class="report-input-options" data-report-submission-form data-report-has-file="{{ !empty($record->data['attachment_path']) ? '1' : '0' }}">
                    <fieldset class="report-submission-choice">
                        <legend>How would you like to prepare this report?</legend>
                        <label><input type="radio" name="data[submission_method]" value="digital" @checked($reportMethod === 'digital')><span><strong>Fill out digital form</strong><small>Enter the report details here.</small></span></label>
                        <label><input type="radio" name="data[submission_method]" value="upload" @checked($reportMethod === 'upload')><span><strong>Upload completed report</strong><small>Use your signed PCR or liquidation file instead.</small></span></label>
                    </fieldset>
                    <div data-report-digital-fields>
            @endif
            @if($record->type === 'Liquidation')
                <section class="panel form-panel chapter-form-section"><div class="section-heading"><span>02</span><div><h2>Cash and voucher details</h2><p>Expense rows and actual totals come from posted project ledger entries and their receipts.</p></div></div><div class="form-grid">
                    @foreach(\App\Support\ChapterForms::LIQUIDATION as $key => $label)<div class="{{ in_array($key, ['receipt_notes', 'reconciliation_notes']) ? 'full' : '' }}"><x-field :name="'data['.$key.']'" :label="$label" :type="$key === 'report_date' ? 'date' : ($key === 'cash_received' ? 'number' : (in_array($key, ['receipt_notes', 'reconciliation_notes']) ? 'textarea' : 'text'))" :value="$record->data[$key] ?? ($key === 'report_date' ? now()->format('Y-m-d') : '')"/></div>@endforeach
                </div></section>
            @else
            @foreach(\App\Support\ChapterForms::REPORT_SECTIONS as $heading => $keys)
                <section class="panel form-panel chapter-form-section"><div class="section-heading"><span>{{ str_pad((string) ($loop->iteration + 1), 2, '0', STR_PAD_LEFT) }}</span><div><h2>{{ $heading }}</h2><p>{{ $loop->first ? 'Compare completed work with the approved proposal and record who was reached.' : 'Record member roles, supporting evidence, issues, and the next action.' }}</p></div></div><div class="form-grid">
                    @foreach($keys as $key)<div class="full"><x-field :name="'data['.$key.']'" :label="\App\Support\ChapterForms::REPORT[$key]" type="textarea" :value="$record->data[$key] ?? ''"/></div>@endforeach
                </div></section>
            @endforeach
            @endif
            <div class="notice">When submitted, the report captures the project's projected and posted income and expenses, plus pending liquidation and missing-evidence counts. The Treasurer verifies linked expenses during Liquidation Report Review.</div>
            @if($supportsReportUpload)
                    </div>
                    <section class="panel form-panel chapter-form-section report-upload-card">
                        <div class="section-heading"><span>03</span><div><h2>Report file</h2><p>In digital-form mode, this is optional supporting evidence. In upload mode, attach the completed report here instead.</p></div></div>
                        @if(!empty($record->data['attachment_path']))<p class="hint">Current file: <a class="text-link" href="{{ route('records.attachment', ['kind' => $kind, 'id' => $record->id]) }}">{{ $record->data['attachment_name'] ?? 'Download current report' }}</a>. Upload a replacement to create a new version.</p>@endif
                        <div class="form-grid"><div class="full"><x-field name="attachment" label="Completed report / supporting file (PDF, DOC, DOCX, JPG, or PNG; max 10 MB)" type="file" :required="$reportMethod === 'upload' && empty($record->data['attachment_path'])"/></div></div>
                    </section>
                </div>
            @endif
        @else
            <section class="panel form-panel chapter-form-section"><div class="section-heading"><span>02</span><div><h2>Financial period and reconciliation</h2><p>Report only on the stated date range. The submitted report captures posted ledger totals.</p></div></div><div class="form-grid">
                @foreach(\App\Support\ChapterForms::FINANCIAL_REPORT as $key => $label)<div class="full"><x-field :name="'data['.$key.']'" :label="$label" :type="str_starts_with($key, 'period_') ? 'date' : 'textarea'" :value="$record->data[$key] ?? ''"/></div>@endforeach
            </div></section>
            <section class="panel form-panel chapter-form-section"><div class="section-heading"><span>03</span><div><h2>Supporting file</h2><p>Add an approved supporting document if needed.</p></div></div><div class="form-grid"><div class="full"><x-field name="attachment" label="Supporting report file (optional; max 10 MB)" type="file"/></div></div></section>
        @endif
    @endif
    <div class="form-footer"><a class="btn secondary" href="{{ $record->exists ? route('records.show', [$kind, $record->id]) : ($project ? route('projects.show', $project).'#completion' : route('records', $kind)) }}"><x-icon name="close" /> Cancel</a><button class="btn primary"><x-icon name="save" /> Save draft</button></div>
</form>
@endsection
