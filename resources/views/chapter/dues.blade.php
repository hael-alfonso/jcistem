@extends('layouts.chapter')
@section('title', $all ? 'Member dues recording' : 'My member dues')
@section('content')
@php
    $shown = $dues->getCollection();
    $outstanding = $shown->sum(fn ($due) => $due->balance);
    $paid = $shown->sum(fn ($due) => $due->paid);
    $overdue = $shown->filter(fn ($due) => $due->status === 'Overdue')->count();
@endphp
<div class="page-heading"><div><span class="eyebrow">JCI CARMONA MEMBERSHIP</span><h1>{{ $all ? 'Member dues recording' : 'My member dues' }}</h1><p>{{ $all ? 'Assess dues and record payments in the official chapter ledger.' : 'See each dues period, the posted payments, and any amount still due.' }}</p></div>@if($all)<a class="btn secondary" href="{{ route('ledger') }}"><x-icon name="receipt" /> Treasurer ledger</a>@endif</div>
<div class="dues-summary" aria-label="Summary of dues shown on this page"><div><span>Outstanding</span><strong>PHP {{ number_format($outstanding, 2) }}</strong><small>Across periods shown</small></div><div><span>Posted payments</span><strong>PHP {{ number_format($paid, 2) }}</strong><small>Across periods shown</small></div><div><span>Overdue periods</span><strong>{{ $overdue }}</strong><small>Of {{ $shown->count() }} shown assessments</small></div></div>
<details class="dues-help"><summary><x-icon name="info" /> How dues and balances work <x-icon name="chevron" /></summary><p>The Treasurer sets each period and records payments. Only posted payments reduce a balance. Unpaid means no payment is posted; Partial means some amount is posted; Overdue means an open balance passed its due date; Paid means the balance is cleared. A zero assessment is waived or adjusted.</p></details>
@if($all)
<details class="panel form-panel dues-create" @if($errors->any()) open @endif><summary class="large-summary"><x-icon name="plus" /> Open or adjust a dues period</summary><p class="hint">Use the amount and due date approved for the period. Leave member blank to assess all active members.</p>
    <form method="POST" action="{{ route('dues.store') }}" class="form-grid">@csrf
        <x-field name="period" label="Dues period" type="month" :value="now()->format('Y-m')" required/><x-field name="due_date" label="Due date" type="date" required/><x-field name="member_id" label="Member (blank for all active members)" type="select" :options="$members->pluck('name','id')->all()"/><x-field name="amount" label="Dues amount (PHP)" type="number" min="0" step="0.01" required/><x-field name="remarks" label="Policy, adjustment, or waiver reason" type="textarea" required/><div class="full"><p class="hint">To waive an unpaid assessment, enter zero and explain the reason. An assessment cannot be reduced below posted payments.</p><button class="btn primary" type="submit"><x-icon name="save" /> Save dues period</button></div>
    </form>
</details>
@endif
<div class="dues-list-heading"><div><h2>{{ $all ? 'Member assessments' : 'Your dues history' }}</h2><p>Open a period to see its payment record.</p></div><span>{{ number_format($dues->total()) }} {{ \Illuminate\Support\Str::plural('period', $dues->total()) }}</span></div>
<section class="dues-list" aria-label="Dues periods">
@forelse($dues as $due)
    <details class="due-card"><summary><span class="due-period"><strong>{{ $all ? ($due->member?->name ?? 'Member') : \Carbon\Carbon::createFromFormat('!Y-m', $due->period)->format('F Y') }}</strong><small>{{ $all ? \Carbon\Carbon::createFromFormat('!Y-m', $due->period)->format('F Y').' · ' : '' }}Due {{ $due->due_date?->format('M d, Y') ?? 'date pending' }}</small></span><span class="due-amount"><strong>PHP {{ number_format($due->balance, 2) }}</strong><small>remaining</small></span><span class="badge due-status {{ strtolower($due->status) }}">{{ (float) $due->amount === 0.0 ? 'Waived / Adjusted' : $due->status }}</span></summary>
        <div class="due-details">@if($due->remarks)<p>{{ $due->remarks }}</p>@endif
            <div class="due-progress"><span>Posted: PHP {{ number_format($due->paid, 2) }}</span><span>Assessed: PHP {{ number_format($due->amount, 2) }}</span></div><progress value="{{ min($due->paid, (float) $due->amount) }}" max="{{ max(0.01, (float) $due->amount) }}" aria-label="Payment progress for {{ $due->period }}"></progress>
            <h3>Payment history</h3><div class="table-wrap"><table class="due-payment-table"><thead><tr><th>Reference</th><th>Date</th><th>Amount</th><th>Status</th><th>Receipt</th></tr></thead><tbody>
                @forelse($due->payments as $payment)<tr><td data-label="Reference">{{ $payment->reference }}</td><td data-label="Date">{{ $payment->transaction_date?->format('M d, Y') }}</td><td data-label="Amount">PHP {{ number_format($payment->amount, 2) }}</td><td data-label="Status"><span class="badge {{ $payment->status === 'Posted' ? 'status-approved' : 'status-declined' }}">{{ $payment->status }}</span></td><td data-label="Receipt">@if($payment->document_path)<a class="text-link" href="{{ route('ledger.receipt', $payment) }}"><x-icon name="download" /> Download</a>@else<span class="muted">None uploaded</span>@endif</td></tr>
                @empty<tr><td colspan="5" class="due-no-payments">No payments recorded for this period.</td></tr>@endforelse
            </tbody></table></div>
            @if($all && $due->balance > 0)
                <form method="POST" action="{{ route('dues.payment', $due) }}" enctype="multipart/form-data" class="form-grid due-payment">@csrf<h3 class="full">Record payment</h3><x-field name="reference" label="Payment reference" required/><x-field name="amount" label="Amount paid (PHP)" type="number" min="0.01" :max="$due->balance" step="0.01" required/><x-field name="transaction_date" label="Payment date" type="date" :value="now()->format('Y-m-d')" required/><x-field name="payment_method" label="Payment method" type="select" :options="array_combine(['Cash','Bank transfer','GCash','Cheque','Other'],['Cash','Bank transfer','GCash','Cheque','Other'])" value="Cash" required/><x-field name="remarks" label="Payment remarks" type="textarea"/><x-field name="receipt" label="Receipt or proof (PDF or image)" type="file"/><div class="full"><p class="hint">The reference must be unique. A saved payment posts to the chapter ledger and updates this member's balance.</p><button class="btn primary" type="submit" data-confirm="Record this payment and post it to the chapter ledger? Check the amount and reference first."><x-icon name="plus" /> Record payment</button></div></form>
            @endif
        </div>
    </details>
@empty<div class="empty-state panel"><x-icon name="receipt"/><h3>{{ $all ? 'No dues assessments yet' : 'No dues periods recorded for you' }}</h3><p>{{ $all ? 'Open a dues period to begin the chapter record.' : 'Your dues periods and payment history will appear once the Treasurer records them.' }}</p></div>@endforelse
</section>
@include('chapter.partials.pagination', ['items' => $dues])
@endsection
