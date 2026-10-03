@extends('layouts.chapter')
@section('title', $all ? 'Member dues recording' : 'My member dues')
@section('content')
<div class="page-heading"><div><div class="eyebrow">JCI CARMONA · CHAPTER MEMBERSHIP</div><h1>{{ $all ? 'Member dues recording' : 'My member dues' }}</h1><p>{{ $all ? 'Maintain monthly assessments and record chapter dues payments in the Treasurer ledger.' : 'Review your JCI Carmona dues periods, outstanding balances, and payments recorded by the Treasurer.' }}</p></div>@if($all)<a class="btn secondary" href="{{ route('ledger') }}">Open Treasurer ledger →</a>@endif</div>
<section class="dues-guide" aria-label="How member dues work">
    <div><span>01 · ASSESSMENT</span><strong>Monthly dues</strong><p>The Treasurer records each period, amount, and due date according to the chapter's approved dues policy.</p></div>
    <div><span>02 · RECORDING</span><strong>Payment and proof</strong><p>The Treasurer records payments against the member and period. Posted payments also appear in the Treasurer ledger.</p></div>
    <div><span>03 · REVIEW</span><strong>{{ $all ? 'Chapter record' : 'Your official record' }}</strong><p>Open a period below to see payment references, status, and any uploaded receipt or proof. {{ $all ? 'Review adjustments against the chapter record.' : 'Ask the Treasurer to correct a record.' }}</p></div>
</section>
@php
    $shown = $dues->getCollection();
    $outstanding = $shown->sum(fn ($due) => $due->balance);
    $paid = $shown->sum(fn ($due) => $due->paid);
    $overdue = $shown->filter(fn ($due) => $due->status === 'Overdue')->count();
@endphp
<div class="dues-summary" aria-label="Dues summary for the current page">
    <div><span>Outstanding on this page</span><strong>PHP {{ number_format($outstanding, 2) }}</strong><small>{{ $all ? 'Across shown members' : 'Across your shown periods' }}</small></div>
    <div><span>Posted payments on this page</span><strong>PHP {{ number_format($paid, 2) }}</strong><small>Only posted payments reduce the balance</small></div>
    <div><span>Overdue periods</span><strong>{{ $overdue }}</strong><small>Of {{ $shown->count() }} shown assessments</small></div>
</div>
<p class="dues-status-note"><strong>Status guide:</strong> Unpaid means no posted payment; Partial means some payment is posted; Overdue means an open balance is past its due date; Paid means no balance remains. A zero assessment is shown as waived or adjusted.</p>
@if($all)
<details class="panel form-panel dues-create"><summary class="large-summary">Open or adjust a monthly dues period</summary>
    <p class="hint">Use the amount and due date approved for the period. Selecting no member applies the assessment to all active members.</p>
    <form method="POST" action="{{ route('dues.store') }}" class="form-grid">@csrf
        <x-field name="period" label="Dues period" type="month" :value="now()->format('Y-m')" required/>
        <x-field name="due_date" label="Due date" type="date" required/>
        <x-field name="member_id" label="Member (leave blank for all active members)" type="select" :options="$members->pluck('name','id')->all()"/>
        <x-field name="amount" label="Dues amount (PHP)" type="number" min="0" step="0.01" required/>
        <x-field name="remarks" label="Policy, adjustment, or waiver reason" type="textarea" required/>
        <div class="full"><p class="hint">To waive an unpaid assessment, enter zero and explain the reason. Existing posted payments cannot be reduced below the amount already paid.</p><button class="btn primary" type="submit">Save dues period</button></div>
    </form>
</details>
@endif
<div class="dues-list-heading"><div><h2>{{ $all ? 'Member assessments' : 'Your dues history' }}</h2><p>Open a period to review its assessment and payment history.</p></div><span>{{ number_format($dues->total()) }} {{ \Illuminate\Support\Str::plural('period', $dues->total()) }}</span></div>
<section class="dues-list" aria-label="Dues periods">
    @forelse($dues as $due)
        <details class="due-card">
            <summary><span class="due-period"><strong>{{ $all ? ($due->member?->name ?? 'Member') : \Carbon\Carbon::createFromFormat('!Y-m', $due->period)->format('F Y') }}</strong><small>{{ $all ? \Carbon\Carbon::createFromFormat('!Y-m', $due->period)->format('F Y').' · ' : '' }}Due {{ $due->due_date?->format('M d, Y') ?? 'date pending' }}</small></span><span class="due-amount"><strong>PHP {{ number_format($due->balance, 2) }}</strong><small>remaining</small></span><span class="badge due-status {{ strtolower($due->status) }}">{{ (float) $due->amount === 0.0 ? 'Waived / Adjusted' : $due->status }}</span></summary>
            <div class="due-details">
                @if($due->remarks)<p>{{ $due->remarks }}</p>@endif
                <div class="due-progress"><span>Posted payments: PHP {{ number_format($due->paid, 2) }}</span><span>Assessed: PHP {{ number_format($due->amount, 2) }}</span></div>
                <progress value="{{ min($due->paid, (float) $due->amount) }}" max="{{ max(0.01, (float) $due->amount) }}"></progress>
                <h3>Payment history</h3>
                <div class="table-wrap"><table><thead><tr><th>Reference</th><th>Date</th><th>Amount</th><th>Status</th><th>Receipt</th></tr></thead><tbody>
                    @forelse($due->payments as $payment)<tr><td>{{ $payment->reference }}</td><td>{{ $payment->transaction_date?->format('M d, Y') }}</td><td>PHP {{ number_format($payment->amount, 2) }}</td><td>{{ $payment->status }}</td><td>@if($payment->document_path)<a class="text-link" href="{{ route('ledger.receipt', $payment) }}">Download</a>@else<span class="muted">None uploaded</span>@endif</td></tr>
                    @empty<tr><td colspan="5">No payments recorded for this period.</td></tr>@endforelse
                </tbody></table></div>
                @if($all && $due->balance > 0)
                    <form method="POST" action="{{ route('dues.payment', $due) }}" enctype="multipart/form-data" class="form-grid due-payment">@csrf
                        <x-field name="reference" label="Payment reference" required/>
                        <x-field name="amount" label="Amount paid (PHP)" type="number" min="0.01" :max="$due->balance" step="0.01" required/>
                        <x-field name="transaction_date" label="Payment date" type="date" :value="now()->format('Y-m-d')" required/>
                        <x-field name="payment_method" label="Payment method" type="select" :options="array_combine(['Cash','Bank transfer','GCash','Cheque','Other'],['Cash','Bank transfer','GCash','Cheque','Other'])" value="Cash" required/>
                        <x-field name="remarks" label="Payment remarks" type="textarea"/>
                        <x-field name="receipt" label="Receipt or proof (PDF or image)" type="file"/>
                        <div class="full"><p class="hint">The payment reference must be unique. A recorded payment is posted to the chapter ledger and updates this member's balance.</p><button class="btn primary" type="submit">Record payment</button></div>
                    </form>
                @endif
            </div>
        </details>
    @empty<div class="empty-state panel"><h3>{{ $all ? 'No dues assessments yet' : 'No dues periods recorded for you' }}</h3><p>{{ $all ? 'Open a monthly dues period to begin the chapter record.' : 'Your dues periods and payment history will appear here once the Treasurer records them.' }}</p></div>@endforelse
    @include('chapter.partials.pagination', ['items' => $dues])
</section>
@endsection
