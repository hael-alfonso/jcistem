@extends('layouts.chapter')
@section('title',$kind==='letters'?'JCI LOI':'Report')
@section('content')
@php
    $uploadedProjectReport = $kind === 'reports' && in_array($record->type, ['Completion', 'Liquidation'], true) && ($record->data['submission_method'] ?? 'digital') === 'upload';
    $reviewRole = $kind === 'reports' && $record->type === 'Liquidation' ? 'treasurer' : 'admin';
    $closedProject = in_array($record->project?->status, ['Archived', 'Completed', 'Cancelled'], true);
    $canReview = !$record->project?->trashed() && !$closedProject && auth()->user()->role === $reviewRole
        && auth()->id() !== $record->created_by && auth()->id() !== $record->project?->chair_id
        && in_array($record->status, ['Submitted', 'For Review'], true);
@endphp
<div class="page-heading no-print"><div><a class="back-link" href="{{ route('records',$kind) }}"><x-icon name="arrowLeft" /> {{ $kind==='letters'?'External LOIs':'Project Reports' }}</a><div class="eyebrow">{{ $kind === 'letters' ? 'EXTERNAL PARTNERSHIP LOI' : $record->type }} · VERSION {{ $record->version }}</div><h1>{{ $record->title }}</h1><p>Prepared by {{ $record->author?->name }} · {{ $record->updated_at->format('M d, Y H:i') }}</p></div><x-status-badge :status="$record->status" class="large" /></div>
<div class="button-row no-print">@if($kind !== 'letters' || in_array($record->status, ['Approved for Sending', 'Sent', 'Archived']))<button class="btn secondary" type="button" data-print><x-icon name="print" /> {{ $kind === 'letters' ? 'Print approved LOI' : 'Print / save PDF' }}</button>@endif
@if($kind === 'letters' && $record->partner)<a class="btn secondary" href="{{ route('partners.show', $record->partner) }}">Partner record</a>@endif
@if($editable)<a class="btn secondary" href="{{ route('records.edit',[$kind,$record->id]) }}"><x-icon name="edit" /> Edit draft</a><form method="POST" action="{{ route('records.transition',[$kind,$record->id]) }}">@csrf<input type="hidden" name="action" value="submit"><button class="btn primary" data-confirm="Submit this record for review? Reviewers will see the current version."><x-icon name="arrow" /> Submit for Review</button></form>@endif
@if(isset($record->data['attachment_path']))@if(in_array(strtolower(pathinfo($record->data['attachment_name'] ?? '', PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'docx']))<a class="btn secondary" href="{{ route('records.attachment',[$kind,$record->id,'preview'=>1]) }}" target="_blank" rel="noopener"><x-icon name="eye" /> View attachment</a>@endif<a class="btn secondary" href="{{ route('records.attachment',[$kind,$record->id]) }}"><x-icon name="download" /> Download original</a>@endif
@if($canReview)
    <section class="record-review-panel no-print">
        <div><span class="eyebrow">{{ $kind === 'letters' ? 'ADMIN REVIEW' : strtoupper($reviewRole).' REVIEW' }}</span><h2>{{ $kind === 'letters' ? 'Review this letter' : 'Review submitted report' }}</h2>
            <p>{{ $kind === 'reports' && $record->type === 'Liquidation' ? 'Check the reconciliation and receipts before approval.' : ($kind === 'reports' ? 'Review this report and preserve the decision with the submitted copy.' : 'Review the LOI before approving it for sending.') }}</p>
        </div>
        <form class="record-review-form" method="POST" action="{{ route('records.transition', [$kind, $record->id]) }}">
            @csrf
            <x-field name="comments" label="Review comments" required />
            <div class="button-row">
                <button name="action" value="approve" class="btn primary" data-confirm="Confirm this approval? The report and your review record will be retained."><x-icon name="check" /> {{ $kind === 'letters' ? 'Approve for Sending' : ($record->type === 'Liquidation' ? 'Approve Liquidation' : ($record->type === 'Completion' ? 'Approve Completion Report' : 'Approve Financial Report')) }}</button>
                <button name="action" value="return" class="btn caution" data-confirm="Return this record for revision with the entered comments?"><x-icon name="arrowLeft" /> Return for Revision</button>
            </div>
        </form>
    </section>
@endif
@php $manager=$record->project_id?($record->project->canManage(auth()->user()) || $record->project->canReport(auth()->user())):auth()->user()->role==='treasurer'; @endphp
@if($manager && $kind==='letters'&&$record->status==='Approved for Sending')<form method="POST" action="{{ route('records.transition',[$kind,$record->id]) }}">@csrf<input type="hidden" name="action" value="sent"><x-field name="delivered_on" label="Hard-copy delivery date" type="date" required/><x-field name="delivery_notes" label="Received by / delivery note"/><button class="btn primary" data-confirm="Confirm the approved hard copy was delivered to the partner?"><x-icon name="check" /> Record hard-copy delivery</button></form>@endif
@if($manager&&in_array($record->status,['Reviewed','Sent','Approved for Sending']))<form method="POST" action="{{ route('records.transition',[$kind,$record->id]) }}">@csrf<input type="hidden" name="action" value="revise"><button class="btn secondary"><x-icon name="filePlus" /> Create a revision</button></form>@endif
@if(!$record->project?->trashed() && !in_array($record->project?->status, ['Archived', 'Completed', 'Cancelled'], true) && $kind==='letters'&&$record->status==='Sent'&&($manager||auth()->user()->role==='admin'))<form method="POST" action="{{ route('records.transition',[$kind,$record->id]) }}">@csrf<input type="hidden" name="action" value="archive"><button class="btn secondary" data-confirm="Archive this letter? It will remain in the document history."><x-icon name="archive" /> Archive letter</button></form>@endif</div>
@if($kind === 'reports' && $record->project_id)
    <section class="record-review-route no-print" aria-label="Report approval route">
        <span class="eyebrow">APPROVAL ROUTE</span>
        <strong>{{ $record->type === 'Liquidation' ? 'Treasurer approval' : 'Admin approval' }}</strong>
        <p>{{ $record->type === 'Liquidation' ? 'The Treasurer reconciles posted expenses and receipts. Admin receives a read-only copy for oversight; Admin does not approve the liquidation.' : 'Admin reviews the project outcomes and evidence. The Treasurer handles the separate liquidation review.' }}</p>
    </section>
@elseif($kind === 'reports' && $record->type === 'Overall Financial')
    <section class="record-review-route no-print" aria-label="Report approval route">
        <span class="eyebrow">APPROVAL ROUTE</span><strong>Admin review and approval</strong>
        <p>The Treasurer prepares the chapter financial report; Admin receives the submitted copy and records the independent review.</p>
    </section>
@endif
@if($record->review_comments)<div class="notice no-print"><strong>Review comments:</strong> {{ $record->review_comments }}</div>@endif
@if($kind === 'letters')
    @include('chapter.partials.external-loi')
@elseif($uploadedProjectReport)
<section class="panel form-panel printable-document project-document">
    <header class="project-letterhead"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="JCI logo" width="140" height="78"><div><strong>JCI Carmona</strong><span>Junior Chamber International</span></div></header>
    <p class="document-overline">{{ $record->type === 'Completion' ? 'PROJECT COMPLETION REPORT (PCR)' : 'LIQUIDATION REPORT' }}</p>
    <h2>Completed report uploaded</h2>
    <p>The completed report is preserved as the attached original file. Open or download it from the attachment actions above; it is not reconstructed from blank digital fields.</p>
    <div class="detail-grid"><div><h3>Project</h3><p>{{ $record->project?->reference }} · {{ $record->project?->title }}</p></div><div><h3>Submitted file</h3><p>{{ $record->data['attachment_name'] ?? 'Attached report file' }}</p></div></div>
    @if(isset($record->data['financial_snapshot']))
        @php $snapshot = $record->data['financial_snapshot']; @endphp
        <h3 class="subheading">Treasurer's ledger reconciliation captured at submission</h3>
        <div class="table-wrap"><table><thead><tr><th>Measure</th><th>Amount / count</th></tr></thead><tbody>
            <tr><td>Actual posted income</td><td>PHP {{ number_format((float) ($snapshot['actual_income'] ?? 0), 2) }}</td></tr>
            <tr><td>Actual posted expenses</td><td>PHP {{ number_format((float) ($snapshot['actual_expenses'] ?? 0), 2) }}</td></tr>
            <tr><td>Expenses pending liquidation</td><td>{{ (int) ($snapshot['pending_liquidation'] ?? 0) }}</td></tr>
            <tr><td>Posted expenses missing receipt evidence</td><td>{{ (int) ($snapshot['missing_evidence'] ?? 0) }}</td></tr>
        </tbody></table></div>
        <small>Ledger values are a read-only snapshot taken when this report was submitted. Individual posted expenses and receipts remain available in Finance.</small>
    @endif
</section>
@elseif($record->type === 'Liquidation')
    @include('chapter.partials.liquidation-record')
@elseif($record->type === 'Completion')
    @include('chapter.partials.pcr-record')
@else
<section class="panel form-panel printable-document"><div class="project-letterhead"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="JCI logo" width="140" height="78"><div><strong>JCI Carmona</strong><span>JCI Philippines · Carmona City, Cavite</span></div></div><h2>{{ $record->title }}</h2>
@if(isset($record->data['project_snapshot']))<div class="notice"><strong>{{ $record->data['project_snapshot']['reference'] }} · {{ $record->data['project_snapshot']['title'] }}</strong><p>{{ $record->data['project_snapshot']['description'] }}</p><span>{{ $record->data['project_snapshot']['date'] }} · {{ $record->data['project_snapshot']['venue'] }}</span></div>@endif
<div class="detail-grid">@foreach($kind==='letters'?\App\Support\ChapterForms::LETTER:($record->project_id?\App\Support\ChapterForms::REPORT:\App\Support\ChapterForms::FINANCIAL_REPORT) as $key=>$label)<div><h3>{{ $label }}</h3><p class="preserve-lines">{{ $record->data[$key]??'Not provided' }}</p></div>@endforeach</div>
@if($kind==='reports'&&isset($record->data['project_snapshot']))<h3 class="subheading">Project financial snapshot</h3><p>Allocated: ₱{{ number_format($record->data['project_snapshot']['allocated'],2) }} · Actual expenses: ₱{{ number_format($record->data['project_snapshot']['expenses'],2) }} · Remaining: ₱{{ number_format($record->data['project_snapshot']['remaining'],2) }}</p>@endif
@if(isset($record->data['financial_snapshot']))
@php $snapshot=$record->data['financial_snapshot']; @endphp
<h3 class="subheading">Official ledger snapshot at submission</h3>
@if($record->project_id)
    <div class="table-wrap"><table><thead><tr><th>Measure</th><th>Projected</th><th>Actual posted</th></tr></thead><tbody>
        <tr><td>Income</td><td>{{ is_numeric($snapshot['projected_income'] ?? null) ? '₱'.number_format((float) $snapshot['projected_income'], 2) : 'Not recorded' }}</td><td>₱{{ number_format((float) ($snapshot['actual_income'] ?? 0), 2) }}</td></tr>
        <tr><td>Expenses</td><td>₱{{ number_format((float) ($snapshot['projected_expenses'] ?? 0), 2) }}</td><td>₱{{ number_format((float) ($snapshot['actual_expenses'] ?? 0), 2) }}</td></tr>
    </tbody></table></div>
    <p>{{ $snapshot['pending_liquidation'] ?? 0 }} posted expense(s) pending liquidation · {{ $snapshot['missing_evidence'] ?? 0 }} without uploaded evidence.</p>
@else
    <p>Income: ₱{{ number_format($snapshot['income'],2) }} · Expenses: ₱{{ number_format($snapshot['expenses'],2) }} · Net movement: ₱{{ number_format($snapshot['income']-$snapshot['expenses'],2) }}</p>
    <div class="table-wrap"><table><thead><tr><th>Reference</th><th>Type</th><th>Description</th><th>Direction</th><th>Amount</th></tr></thead><tbody>@foreach($snapshot['transactions'] as $entry)<tr><td>{{ $entry['reference'] }}</td><td>{{ $entry['type'] }}</td><td>{{ $entry['description'] }}</td><td>{{ $entry['direction'] }}</td><td>₱{{ number_format($entry['amount'],2) }}</td></tr>@endforeach</tbody></table></div>
@endif
<small>Captured {{ $snapshot['captured_at'] }}. Later corrections do not rewrite this submission.</small>
@endif
</section>
@endif
@endsection
