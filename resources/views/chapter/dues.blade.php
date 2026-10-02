@extends('layouts.chapter')
@section('title', $all ? 'Member dues recording' : 'My member dues')
@section('content')
<div class="page-heading"><div><div class="eyebrow">CHAPTER MEMBERSHIP</div><h1>{{ $all ? 'Member dues recording' : 'My member dues' }}</h1><p>{{ $all ? 'Assess dues and record payments linked to the ledger.' : 'See your dues, payment history, and available receipts.' }}</p></div></div>
@php
    $shown = $dues->getCollection();
    $outstanding = $shown->sum(fn ($due) => $due->balance);
    $paid = $shown->sum(fn ($due) => $due->paid);
    $overdue = $shown->filter(fn ($due) => $due->status === 'Overdue')->count();
@endphp
<div class="dues-summary" aria-label="Dues summary for the current page">
    <div><span>Outstanding on this page</span><strong>?{{ number_format($outstanding, 2) }}</strong><small>{{ $all ? 'Across shown members' : 'Your unpaid balance' }}</small></div>
    <div><span>Payments recorded</span><strong>?{{ number_format($paid, 2) }}</strong><small>For shown dues periods</small></div>
    <div><span>Overdue periods</span><strong>{{ $overdue }}</strong><small>Of {{ $shown->count() }} shown assessments</small></div>
</div>
@if($all)
<details class="panel form-panel dues-create"><summary class="large-summary">Open or adjust a monthly dues period</summary>
    <form method="POST" action="{{ route('dues.store') }}" class="form-grid">@csrf
        <x-field name="period" label="Dues period" type="month" :value="now()->format('Y-m')" required/>
        <x-field name="due_date" label="Due date" type="date" required/>
        <x-field name="member_id" label="Member (leave blank for all active members)" type="select" :options="$members->pluck('name','id')->all()"/>
        <x-field name="amount" label="Dues amount (PHP)" type="number" min="0" step="0.01" required/>
        <x-field name="remarks" label="Policy, adjustment, or waiver reason" type="textarea" required/>
        <div class="full"><p class="hint">Enter zero with a reason to waive an unpaid assessment.</p><button class="btn primary" type="submit">Save dues period</button></div>
    </form>
</details>
@endif
<section class="dues-list" aria-label="Dues periods">
    @forelse($dues as $due)
        <details class="due-card">
            <summary><span class="due-period"><strong>{{ $all ? ($due->member?->name ?? 'Member') : \Carbon\Carbon::createFromFormat('!Y-m', $due->period)->format('F Y') }}</strong><small>{{ $all ? $due->period.' ? ' : '' }}Due {{ $due->due_date?->format('M d, Y') ?? 'date pending' }}</small></span><span class="due-amount"><strong>?{{ number_format($due->balance, 2) }}</strong><small>remaining</small></span><span class="badge due-status {{ strtolower($due->status) }}">{{ (float) $due->amount === 0.0 ? 'Waived / Adjusted' : $due->status }}</span></summary>
            <div class="due-details">
                @if($due->remarks)<p>{{ $due->remarks }}</p>@endif
                <div class="due-progress"><span>Paid ?{{ number_format($due->paid, 2) }}</span><span>Assessed ?{{ number_format($due->amount, 2) }}</span></div>
                <progress value="{{ min($due->paid, (float) $due->amount) }}" max="{{ max(0.01, (float) $due->amount) }}"></progress>
                <h3>Payment history</h3>
                <div class="table-wrap"><table><thead><tr><th>Reference</th><th>Date</th><th>Amount</th><th>Status</th><th>Receipt</th></tr></thead><tbody>
                    @forelse($due->payments as $payment)<tr><td>{{ $payment->reference }}</td><td>{{ $payment->transaction_date?->format('M d, Y') }}</td><td>?{{ number_format($payment->amount, 2) }}</td><td>{{ $payment->status }}</td><td>@if($payment->document_path)<a class="text-link" href="{{ route('ledger.receipt', $payment) }}">Download</a>@else?@endif</td></tr>
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
                        <div class="full"><button class="btn primary" type="submit">Record payment</button></div>
                    </form>
                @endif
            </div>
        </details>
    @empty<div class="empty-state panel"><h3>No dues assessments yet.</h3><p>Dues periods and payments will appear here once recorded by the Treasurer.</p></div>@endforelse
    @include('chapter.partials.pagination', ['items' => $dues])
</section>
@endsection
