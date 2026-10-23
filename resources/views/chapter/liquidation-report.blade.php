@extends('layouts.chapter')
@section('title', 'Financial liquidation report')
@section('content')
@php
    $projectedIncome = $project->proposal['projected_income'] ?? null;
    $projectedIncome = is_numeric($projectedIncome) ? (float) $projectedIncome : null;
    $actualIncome = (float) $income->sum('amount');
    $actualExpenses = (float) $expenses->sum('amount');
    $allocatedByCategory = $project->allocations->pluck('amount', 'category');
    $categories = $allocatedByCategory->keys()->merge($spentByCategory->keys())->unique()->sort()->values();
    $pending = $expenses->where('liquidation_status', '!=', 'Liquidated')->count();
    $missingEvidence = $expenses->filter(fn ($entry) => !$entry->document_path)->count();
@endphp
<div class="page-heading no-print"><div><a class="back-link" href="{{ route('projects.show', $project) }}#budget"><x-icon name="arrowLeft" /> {{ $project->title }}</a><div class="eyebrow">{{ $project->reference }} · TREASURER RECORD</div><h1>Financial liquidation report</h1><p>Compare the plan with posted transactions and review each expense's supporting document.</p></div><button class="btn primary" type="button" data-print><x-icon name="print" /> Print / save PDF</button></div>
<div class="notice no-print">This is a live ledger view, updated as transactions change. Only posted entries count. A document attached to an expense is evidence for review, not proof of an independent audit.</div>
<article class="panel printable-document project-document financial-document">
    <header class="project-letterhead"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="JCI logo" width="140" height="78"><div><strong>JCI Carmona</strong><span>JCI Philippines · Carmona City, Cavite</span></div></header>
    <p class="document-overline">PROJECT FINANCIAL RECORD · {{ $project->reference }}</p>
    <h2>Financial Liquidation Report</h2>
    <dl class="document-facts">
        <div><dt>Project</dt><dd>{{ $project->title }}</dd></div>
        <div><dt>Area</dt><dd>{{ $project->area }}</dd></div>
        <div><dt>Project Chair</dt><dd>{{ $project->chair?->name ?? 'Not yet assigned' }}</dd></div>
        <div><dt>Prepared as of</dt><dd>{{ now()->format('F j, Y g:i A') }}</dd></div>
    </dl>
    <section class="proposal-document-section">
        <h3>01 · Projected versus actual</h3>
        <div class="table-wrap"><table><thead><tr><th>Measure</th><th>Projected</th><th>Actual posted</th><th>Difference</th></tr></thead><tbody>
            <tr><td>Income / posted credits</td><td>{{ $projectedIncome === null ? 'Not recorded' : '₱'.number_format($projectedIncome, 2) }}</td><td>₱{{ number_format($actualIncome, 2) }}</td><td>{{ $projectedIncome === null ? '—' : '₱'.number_format($actualIncome - $projectedIncome, 2) }}</td></tr>
            <tr><td>Expenditure / posted debits</td><td>₱{{ number_format((float) $project->proposed_budget, 2) }}</td><td>₱{{ number_format($actualExpenses, 2) }}</td><td>₱{{ number_format($actualExpenses - (float) $project->proposed_budget, 2) }}</td></tr>
        </tbody></table></div>
        <p class="document-note">Projected expenditure is the proposal's total estimated budget. Actuals use posted ledger credits and debits; void entries are excluded. Difference is actual minus projected.</p>
    </section>
    <section class="proposal-document-section">
        <h3>02 · Approved allocation and spending by category</h3>
        <div class="table-wrap"><table><thead><tr><th>Category</th><th>Approved allocation</th><th>Posted expenses</th><th>Balance</th></tr></thead><tbody>
            @forelse($categories as $category)
                @php $allocated = (float) ($allocatedByCategory[$category] ?? 0); $spent = (float) ($spentByCategory[$category] ?? 0); @endphp
                <tr><td>{{ $category }}</td><td>₱{{ number_format($allocated, 2) }}</td><td>₱{{ number_format($spent, 2) }}</td><td>₱{{ number_format($allocated - $spent, 2) }}</td></tr>
            @empty
                <tr><td colspan="4">No approved allocation or posted expenses recorded yet.</td></tr>
            @endforelse
        </tbody><tfoot><tr><th>Total</th><th>₱{{ number_format((float) $project->allocations->sum('amount'), 2) }}</th><th>₱{{ number_format($actualExpenses, 2) }}</th><th>₱{{ number_format((float) $project->allocations->sum('amount') - $actualExpenses, 2) }}</th></tr></tfoot></table></div>
        <p class="document-note">Approved allocations are shown separately from the proposal's estimated category costs.</p>
    </section>
    <section class="proposal-document-section">
        <h3>03 · Receipt and liquidation register</h3>
        <p>{{ $expenses->count() }} posted expense(s) · {{ $pending }} pending liquidation · {{ $missingEvidence }} without uploaded evidence</p>
        <div class="table-wrap"><table><thead><tr><th>Reference / date</th><th>Payee / category</th><th>Amount</th><th>Evidence</th><th>Liquidation</th></tr></thead><tbody>
            @forelse($expenses as $entry)
                <tr><td>{{ $entry->reference }}<br><small>{{ $entry->transaction_date?->format('M j, Y') }}</small></td><td>{{ $entry->counterparty }}<br><small>{{ $entry->category }}</small></td><td>₱{{ number_format((float) $entry->amount, 2) }}</td><td>@if($entry->document_path)<a href="{{ route('ledger.receipt', $entry) }}">{{ $entry->document_name ?: 'View document' }}</a>@else<span class="danger-text">Missing</span>@endif</td><td>{{ $entry->liquidation_status }}</td></tr>
            @empty
                <tr><td colspan="5">No posted expenses recorded yet.</td></tr>
            @endforelse
        </tbody></table></div>
        <p class="document-note">The Treasurer confirms liquidation in the ledger after checking the receipt, invoice, or supporting acknowledgment. Keep original documents under chapter retention rules.</p>
    </section>
    <footer class="document-page-footer">{{ $project->reference }} · Treasurer working report · {{ $project->status }}</footer>
</article>
@endsection
