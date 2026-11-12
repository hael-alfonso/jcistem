@extends('layouts.chapter')
@section('title', 'Treasurer ledger report')
@section('content')
@php
    $postedEntries = $entries->where('status', 'Posted');
    $credits = (float) $postedEntries->where('direction', 'credit')->sum('amount');
    $debits = (float) $postedEntries->where('direction', 'debit')->sum('amount');
    $filters = collect(['Project' => $selectedProject?->title, 'Search' => request('q'), 'Status' => request('status'), 'Flow' => request('direction') ? ucfirst(request('direction')) : null, 'From' => request('from'), 'To' => request('to')])->filter(fn ($value) => filled($value));
@endphp
<div class="page-heading no-print"><div><a class="back-link" href="{{ route('ledger', request()->except('print')) }}"><x-icon name="arrowLeft" /> Back to ledger</a><div class="eyebrow">TREASURER REPORT</div><h1>Transaction ledger</h1><p>{{ $entries->count() }} matching record{{ $entries->count() === 1 ? '' : 's' }} · Generated {{ now()->format('F j, Y g:i A') }}</p></div><button class="btn primary" type="button" data-print><x-icon name="print" /> Print / save PDF</button></div>
<style>@media print { @page { size: landscape; margin: 12mm; } }</style>
<article class="panel printable-document project-document report-document ledger-print-report">
    <header class="project-letterhead"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="JCI Carmona logo" width="96" height="56"><div><strong>JCI Carmona</strong><span>Treasurer ledger · Generated {{ now()->format('F j, Y g:i A') }}</span></div></header>
    <p class="document-overline">OFFICIAL FINANCIAL RECORDS</p>
    <h1 class="report-title">Transaction ledger</h1>
    @if($filters->isNotEmpty())<p class="budget-summary-meta">{{ $filters->map(fn ($value, $key) => $key.': '.$value)->implode(' · ') }}</p>@endif
    <div class="ledger-report-totals"><div><span>Posted credits</span><strong>PHP {{ number_format($credits, 2) }}</strong></div><div><span>Posted debits</span><strong>PHP {{ number_format($debits, 2) }}</strong></div><div><span>Net posted movement</span><strong>PHP {{ number_format($credits - $debits, 2) }}</strong></div><small>Totals exclude void records. Matching void entries, if any, remain identified in the register below.</small></div>
    <div class="table-wrap report-table-wrap"><table class="report-register-table ledger-pdf-table"><colgroup><col style="width:13%"><col style="width:9%"><col style="width:11%"><col style="width:23%"><col style="width:12%"><col style="width:11%"><col style="width:11%"><col style="width:10%"></colgroup><thead><tr><th>Reference</th><th>Date</th><th>Type</th><th>Project / member and description</th><th>Counterparty</th><th>Credit</th><th>Debit</th><th>Status</th></tr></thead><tbody>
        @forelse($entries as $entry)
            <tr><td data-label="Reference">{{ $entry->reference }}</td><td data-label="Date">{{ $entry->transaction_date?->format('M j, Y') }}</td><td data-label="Type">{{ $entry->type }}</td><td data-label="Project / member and description"><strong>{{ $entry->project?->title ?? $entry->member?->name ?? 'Chapter transaction' }}</strong><small>{{ $entry->description }}</small></td><td data-label="Counterparty">{{ $entry->counterparty }}</td><td data-label="Credit" class="report-money">{{ $entry->direction === 'credit' ? 'PHP '.number_format((float) $entry->amount, 2) : '—' }}</td><td data-label="Debit" class="report-money">{{ $entry->direction === 'debit' ? 'PHP '.number_format((float) $entry->amount, 2) : '—' }}</td><td data-label="Status">{{ $entry->status }}</td></tr>
        @empty
            <tr><td colspan="8">No ledger transactions match these filters.</td></tr>
        @endforelse
    </tbody></table></div>
    <p class="document-note">This report reflects the selected ledger filters and includes all matching records, not only the on-screen page. Posted credits and debits are totaled separately; void records are excluded from totals.</p>
</article>
@endsection
