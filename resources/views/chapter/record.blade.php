@extends('layouts.chapter')
@section('title',$kind==='letters'?'JCI LOI':'Report')
@section('content')
<div class="page-heading no-print"><div><a class="back-link" href="{{ route('records',$kind) }}"><x-icon name="arrowLeft" /> {{ $kind==='letters'?'External LOIs':'Reports' }}</a><div class="eyebrow">{{ $kind === 'letters' ? 'EXTERNAL PARTNERSHIP LOI' : $record->type }} · VERSION {{ $record->version }}</div><h1>{{ $record->title }}</h1><p>Prepared by {{ $record->author?->name }} · {{ $record->updated_at->format('M d, Y H:i') }}</p></div><span class="badge large">{{ $record->status }}</span></div>
<div class="button-row no-print"><button class="btn secondary" type="button" data-print><x-icon name="print" /> Print / save PDF</button>
@if($editable)<a class="btn secondary" href="{{ route('records.edit',[$kind,$record->id]) }}"><x-icon name="edit" /> Edit draft</a><form method="POST" action="{{ route('records.transition',[$kind,$record->id]) }}">@csrf<input type="hidden" name="action" value="submit"><button class="btn primary"><x-icon name="arrow" /> Submit to Admin</button></form>@endif
@if(isset($record->data['attachment_path']))<a class="btn secondary" href="{{ route('records.attachment',[$kind,$record->id]) }}"><x-icon name="download" /> Download attachment</a>@endif
@if(auth()->user()->role==='admin'&&in_array($record->status,['Submitted','For Review']))<form class="inline-review" method="POST" action="{{ route('records.transition',[$kind,$record->id]) }}">@csrf<x-field name="comments" label="Review comments" required/><button name="action" value="approve" class="btn primary"><x-icon name="check" /> {{ $kind==='letters'?'Approve for Sending':'Mark Reviewed' }}</button><button name="action" value="return" class="btn secondary"><x-icon name="arrowLeft" /> Return for Revision</button></form>@endif
@php $manager=$record->project_id?$record->project->canManage(auth()->user()):auth()->user()->role==='treasurer'; @endphp
@if($manager && $kind==='letters'&&$record->status==='Approved for Sending')<form method="POST" action="{{ route('records.transition',[$kind,$record->id]) }}">@csrf<input type="hidden" name="action" value="sent"><button class="btn primary"><x-icon name="check" /> Mark as Sent</button></form><span class="hint">Record this after sending the letter through your chapter's communication channel.</span>@endif
@if($manager&&in_array($record->status,['Reviewed','Sent','Approved for Sending']))<form method="POST" action="{{ route('records.transition',[$kind,$record->id]) }}">@csrf<input type="hidden" name="action" value="revise"><button class="btn secondary"><x-icon name="filePlus" /> Create a revision</button></form>@endif
@if($kind==='letters'&&$record->status==='Sent'&&($manager||auth()->user()->role==='admin'))<form method="POST" action="{{ route('records.transition',[$kind,$record->id]) }}">@csrf<input type="hidden" name="action" value="archive"><button class="btn secondary" data-confirm="Archive this letter? It will remain in the document history."><x-icon name="archive" /> Archive letter</button></form>@endif</div>
@if($record->review_comments)<div class="notice no-print"><strong>Review comments:</strong> {{ $record->review_comments }}</div>@endif
@if($kind === 'letters')
    @include('chapter.partials.external-loi')
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
@if($record->versions)<section class="panel form-panel no-print"><h2>Previous versions</h2>@foreach(array_reverse($record->versions) as $version)<details class="record-detail"><summary><strong>Version {{ $version['version'] }} · {{ $version['status'] }}</strong><span>{{ $version['saved_at'] }}</span></summary><h3>{{ $version['title'] }}</h3>@foreach($version['data'] as $key=>$value)@if(!str_contains($key,'path'))<div><h4>{{ ucwords(str_replace('_',' ',$key)) }}</h4><p class="preserve-lines">{{ is_array($value)?json_encode($value,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE):$value }}</p></div>@endif @endforeach</details>@endforeach</section>@endif
@endsection
