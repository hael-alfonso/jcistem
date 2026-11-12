@extends('layouts.chapter')
@section('title', 'Member dues report')
@section('content')
@php
    $outstanding = (float) $printedDues->sum('balance');
    $paid = (float) $printedDues->sum('paid');
    $assessed = (float) $printedDues->sum('amount');
    $overdue = $printedDues->where('status', 'Overdue')->count();
    $filters = collect(['Member' => $selectedMember?->name, 'Payment status' => request('status') && request('status') !== 'all' ? ucfirst(request('status')) : null, 'Period' => request('period'), 'Due from' => request('from'), 'Due to' => request('to')])->filter(fn ($value) => filled($value));
@endphp
<div class="page-heading no-print"><div><a class="back-link" href="{{ route($assessmentPage ? 'dues.assessments' : 'dues', request()->except('print')) }}"><x-icon name="arrowLeft" /> Back to {{ $assessmentPage ? 'assessments' : 'my dues' }}</a><div class="eyebrow">JCI CARMONA MEMBERSHIP</div><h1>{{ $assessmentPage ? 'Member dues report' : 'My member dues report' }}</h1><p>{{ $printedDues->count() }} period{{ $printedDues->count() === 1 ? '' : 's' }} · Generated {{ now()->format('F j, Y g:i A') }}</p></div><button class="btn primary" type="button" data-print><x-icon name="print" /> Print / save PDF</button></div>
<article class="panel printable-document project-document report-document dues-print-report">
    <header class="project-letterhead"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="JCI Carmona logo" width="96" height="56"><div><strong>JCI Carmona</strong><span>{{ $assessmentPage ? 'Member dues register' : 'Personal dues statement' }} · Generated {{ now()->format('F j, Y g:i A') }}</span></div></header>
    <p class="document-overline">MEMBER FINANCIAL RECORDS</p>
    <h1 class="report-title">{{ $assessmentPage ? 'Member dues report' : 'My member dues report' }}</h1>
    @if($filters->isNotEmpty())<p class="budget-summary-meta">{{ $filters->map(fn ($value, $key) => $key.': '.$value)->implode(' · ') }}</p>@endif
    <div class="dues-report-totals"><div><span>Assessed</span><strong>PHP {{ number_format($assessed, 2) }}</strong></div><div><span>Posted payments</span><strong>PHP {{ number_format($paid, 2) }}</strong></div><div><span>Remaining balance</span><strong>PHP {{ number_format($outstanding, 2) }}</strong></div><div><span>Overdue periods</span><strong>{{ $overdue }}</strong></div></div>
    <div class="table-wrap report-table-wrap"><table class="report-register-table dues-pdf-table"><thead><tr><th>{{ $assessmentPage ? 'Member / period' : 'Period' }}</th><th>Due date</th><th>Assessed</th><th>Paid</th><th>Remaining</th><th>Status</th></tr></thead><tbody>
        @forelse($printedDues as $due)
            <tr><td data-label="{{ $assessmentPage ? 'Member / period' : 'Period' }}"><strong>{{ $assessmentPage ? ($due->member?->name ?? 'Member') : \Carbon\Carbon::createFromFormat('!Y-m', $due->period)->format('F Y') }}</strong><small>{{ $assessmentPage ? \Carbon\Carbon::createFromFormat('!Y-m', $due->period)->format('F Y') : ($due->member?->member_no ?? 'Member account') }}</small></td><td data-label="Due date">{{ $due->due_date?->format('M j, Y') ?? 'Pending' }}</td><td data-label="Assessed" class="report-money">PHP {{ number_format((float) $due->amount, 2) }}</td><td data-label="Paid" class="report-money">PHP {{ number_format((float) $due->paid, 2) }}</td><td data-label="Remaining" class="report-money">PHP {{ number_format((float) $due->balance, 2) }}</td><td data-label="Status">{{ (float) $due->amount === 0.0 ? 'Waived / adjusted' : $due->status }}</td></tr>
        @empty
            <tr><td colspan="6">No dues periods match these filters.</td></tr>
        @endforelse
    </tbody></table></div>
    @if($printedDues->isNotEmpty())
    <section class="dues-report-payment-history"><h2>Payment history</h2>
        @foreach($printedDues as $due)
            <div class="dues-payment-report-block"><h3>{{ $assessmentPage ? ($due->member?->name ?? 'Member').' · ' : '' }}{{ \Carbon\Carbon::createFromFormat('!Y-m', $due->period)->format('F Y') }}</h3>
                <table class="dues-payment-report-table"><thead><tr><th>Reference</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead><tbody>
                    @forelse($due->payments as $payment)<tr><td>{{ $payment->reference }}</td><td>{{ $payment->transaction_date?->format('M j, Y') }}</td><td>PHP {{ number_format((float) $payment->amount, 2) }}</td><td>{{ $payment->status }}</td></tr>@empty<tr><td colspan="4">No payments recorded.</td></tr>@endforelse
                </tbody></table>
            </div>
        @endforeach
    </section>
    @endif
    <p class="document-note">Posted payments determine paid amounts and remaining balances. Waived or adjusted periods have a zero assessment. This report includes every matching period, including records beyond the on-screen pagination.</p>
</article>
@endsection
