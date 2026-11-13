@extends('layouts.chapter')
@section('title', $all ? ($assessmentPage ? 'Member Assessments' : 'Member Dues') : 'My Member Dues')
@section('content')
@php
    $shown = $dues?->getCollection() ?? collect();
    $outstanding = $shown->sum(fn ($due) => $due->balance);
    $paid = $shown->sum(fn ($due) => $due->paid);
    $overdue = $shown->filter(fn ($due) => $due->status === 'Overdue')->count();
@endphp
<div class="page-heading"><div><span class="eyebrow">JCI CARMONA MEMBERSHIP</span><h1>{{ $all ? ($assessmentPage ? 'Member assessments' : 'Member Dues') : 'My Member Dues' }}</h1><p>{{ $all ? ($assessmentPage ? 'Review each dues period and its payment history.' : 'Find registered accounts and record chapter dues.') : 'Your assessed periods, payments, and remaining balances in one place.' }}</p></div><div class="heading-actions">@if(!$all || $assessmentPage)<a class="btn secondary" href="{{ route($assessmentPage ? 'dues.assessments' : 'dues', array_merge(request()->except(['page', 'print']), ['print' => 1])) }}"><x-icon name="print" /> Print / save PDF</a>@endif @if($all)<button class="btn primary" type="button" data-finance-dialog-open="dues-assessment-dialog" data-dues-member-id="{{ $selectedMember?->id ?? 'none' }}"><x-icon name="plus" /> Assess dues</button><a class="btn secondary" href="{{ $assessmentPage ? route('dues.manage') : route('dues.assessments') }}">{{ $assessmentPage ? 'Registered accounts' : 'View assessments' }}</a>@endif</div></div>
@if($all && !$assessmentPage)
<form class="list-filters record-filters" method="GET" action="{{ route('dues.manage') }}"><label>Find account<input name="q" value="{{ request('q') }}" placeholder="Name, email, or member ID"></label><div class="filter-actions"><button class="btn secondary" type="submit"><x-icon name="filter"/> Search</button>@if(request()->filled('q'))<a class="text-link" href="{{ route('dues.manage') }}">Clear search</a>@endif</div></form>
@else
@if($assessmentPage)
<nav class="dues-status-tabs" aria-label="Filter assessments by payment status">
    @foreach(['all' => 'All assessments', 'unpaid' => 'Unpaid', 'paid' => 'Paid', 'waived' => 'Waived / adjusted'] as $tabStatus => $tabLabel)
        <a href="{{ route('dues.assessments', array_merge(request()->except(['status', 'page', 'accounts_page']), $tabStatus === 'all' ? [] : ['status' => $tabStatus])) }}" class="{{ request('status', 'all') === $tabStatus ? 'is-active' : '' }}" @if(request('status', 'all') === $tabStatus) aria-current="page" @endif>{{ $tabLabel }}</a>
    @endforeach
</nav>
@endif
<form class="list-filters record-filters" method="GET" action="{{ $all ? route('dues.assessments') : route('dues') }}">
@if($all)@if($selectedMember)<input type="hidden" name="member" value="{{ $selectedMember->id }}">@else<label>Find account<input name="q" value="{{ request('q') }}" placeholder="Name, email, or member ID"></label>@endif @endif
@if($assessmentPage)<input type="hidden" name="status" value="{{ request('status', 'all') }}"><label>Dues period<input type="month" name="period" value="{{ request('period') }}"></label>@endif
<label>Due from month<input type="month" name="from" value="{{ request('from') }}"></label><label>Due to month<input type="month" name="to" value="{{ request('to') }}"></label><div class="filter-actions"><button class="btn secondary" type="submit"><x-icon name="filter"/> Apply filters</button>@if(request()->filled('q') || request()->filled('member') || request()->filled('period') || (request()->filled('status') && request('status') !== 'all') || request()->filled('from') || request()->filled('to'))<a class="text-link" href="{{ $all ? route('dues.assessments') : route('dues') }}"><x-icon name="close"/> Clear filters</a>@endif</div></form>
@endif
@if(!$all || $assessmentPage)
@if($selectedMember)<div class="dues-selected-account"><span>Viewing dues for <strong>{{ $selectedMember->name }}</strong></span><a class="text-link" href="{{ route('dues.assessments') }}">Show all assessments</a></div>@endif
<div class="dues-summary" aria-label="Summary of dues shown on this page"><div><span>Outstanding</span><strong>PHP {{ number_format($outstanding, 2) }}</strong><small>Across periods shown</small></div><div><span>Posted payments</span><strong>PHP {{ number_format($paid, 2) }}</strong><small>Across periods shown</small></div><div><span>Overdue periods</span><strong>{{ $overdue }}</strong><small>Of {{ $shown->count() }} shown assessments</small></div></div>
<div class="notice notice-guidance dues-help-note" role="note"><span><strong>Balances use posted payments only.</strong> A period is overdue when its due date has passed and a balance remains. A zero assessment is waived or adjusted.</span></div>
<div class="dues-list-heading" id="dues-records"><div><h2>{{ $all ? 'Member assessments' : 'Your dues history' }}</h2><p>Payment details are shown with each period.</p></div><span>{{ number_format($dues->total()) }} {{ \Illuminate\Support\Str::plural('period', $dues->total()) }}</span></div>
<section class="dues-list" aria-label="Dues periods">
@forelse($dues as $due)
    <article class="due-card"><div class="due-card-header"><span class="due-period"><strong>{{ $all ? ($due->member?->name ?? 'Member') : \Carbon\Carbon::createFromFormat('!Y-m', $due->period)->format('F Y') }}</strong><small>{{ $all ? \Carbon\Carbon::createFromFormat('!Y-m', $due->period)->format('F Y').' · ' : '' }}Due {{ $due->due_date?->format('M d, Y') ?? 'date pending' }}</small></span><span class="due-amount"><strong>PHP {{ number_format($due->balance, 2) }}</strong><small>remaining</small></span><span class="badge due-status {{ (float) $due->amount === 0.0 ? 'waived' : strtolower($due->status) }}">{{ (float) $due->amount === 0.0 ? 'Waived / Adjusted' : $due->status }}</span></div>
        <div class="due-details">@if($due->remarks)<p>{{ $due->remarks }}</p>@endif
            <div class="due-progress"><span>Posted: PHP {{ number_format($due->paid, 2) }}</span><span>Assessed: PHP {{ number_format($due->amount, 2) }}</span></div>
            <h3>Payment history</h3><div class="table-wrap"><table class="due-payment-table"><thead><tr><th>Reference</th><th>Date</th><th>Amount</th><th>Status</th><th>Receipt</th></tr></thead><tbody>
                @forelse($due->payments as $payment)<tr><td data-label="Reference">{{ $payment->reference }}</td><td data-label="Date">{{ $payment->transaction_date?->format('M d, Y') }}</td><td data-label="Amount">PHP {{ number_format($payment->amount, 2) }}</td><td data-label="Status"><span class="badge {{ $payment->status === 'Posted' ? 'status-approved' : 'status-declined' }}">{{ $payment->status }}</span></td><td data-label="Receipt">@if($payment->document_path)<a class="text-link" href="{{ route('ledger.receipt', $payment) }}"><x-icon name="download" /> Download</a>@else<span class="muted">None uploaded</span>@endif</td></tr>
                @empty<tr><td colspan="5" class="due-no-payments">No payments recorded for this period.</td></tr>@endforelse
            </tbody></table></div>
            @if($all && $due->balance > 0)
                <button class="btn secondary due-payment-trigger" type="button" data-finance-dialog-open="due-payment-dialog-{{ $due->id }}"><x-icon name="plus" /> Record payment</button>
            @endif
        </div>
    </article>
    @if($all && $due->balance > 0)
                @php $paymentAttempt = old('_finance_dialog') === 'due-payment-dialog-'.$due->id; @endphp
                <dialog class="finance-dialog finance-dialog-compact" id="due-payment-dialog-{{ $due->id }}" aria-labelledby="due-payment-title-{{ $due->id }}">
                    <div class="finance-dialog-header"><div><span class="eyebrow">MEMBER DUES</span><h2 id="due-payment-title-{{ $due->id }}">Record payment</h2><p>{{ $due->member?->name ?? 'Member' }} · {{ \Carbon\Carbon::createFromFormat('!Y-m', $due->period)->format('F Y') }} · PHP {{ number_format($due->balance, 2) }} remaining</p></div><button class="finance-dialog-close" type="button" data-finance-dialog-close aria-label="Close payment form"><x-icon name="close" /></button></div>
                    <div class="finance-dialog-body">
                        @if($paymentAttempt && $errors->any())<div class="alert error" role="alert">Please review the payment: {{ $errors->first() }}</div>@endif
                        <form method="POST" action="{{ route('dues.payment', $due) }}" enctype="multipart/form-data" class="form-grid">@csrf<input type="hidden" name="_finance_dialog" value="due-payment-dialog-{{ $due->id }}">
                            <label class="field"><span>Payment reference <em aria-hidden="true">*</em></span><input name="reference" value="{{ $paymentAttempt ? old('reference') : '' }}" maxlength="100" required></label>
                            <label class="field"><span>Amount paid (PHP) <em aria-hidden="true">*</em></span><input name="amount" type="number" min="0.01" max="{{ number_format($due->balance, 2, '.', '') }}" step="0.01" value="{{ $paymentAttempt ? old('amount') : '' }}" required></label>
                            <label class="field"><span>Payment date <em aria-hidden="true">*</em></span><input name="transaction_date" type="date" value="{{ $paymentAttempt ? old('transaction_date', now()->format('Y-m-d')) : now()->format('Y-m-d') }}" required></label>
                            <fieldset class="full dues-payment-methods"><legend>Payment method <span aria-hidden="true">*</span></legend><div class="dues-payment-method-options">@foreach(['Cash', 'Bank transfer', 'GCash', 'Cheque', 'Other'] as $method)<label><input type="radio" name="payment_method" value="{{ $method }}" @checked(($paymentAttempt ? old('payment_method', 'Cash') : 'Cash') === $method) required><span>{{ $method }}</span></label>@endforeach</div></fieldset>
                            <label class="field"><span>Payment remarks</span><textarea name="remarks" rows="3" maxlength="5000">{{ $paymentAttempt ? old('remarks') : '' }}</textarea></label>
                            <label class="field"><span>Receipt or proof (PDF or image)</span><input name="receipt" type="file" accept=".pdf,.jpg,.jpeg,.png"></label>
                            <div class="full"><p class="hint">The reference must be unique. Saving posts the payment to the chapter ledger.</p></div>
                            <div class="finance-dialog-actions"><button class="btn secondary" type="button" data-finance-dialog-close>Cancel</button><button class="btn primary" type="submit"><x-icon name="plus" /> Post payment</button></div>
                        </form>
                    </div>
                </dialog>@if($paymentAttempt)<span hidden data-finance-dialog-reopen="due-payment-dialog-{{ $due->id }}"></span>@endif
    @endif
@empty<div class="empty-state panel"><x-icon name="receipt"/><h3>{{ $all ? 'No dues assessments yet' : 'No dues periods recorded for you' }}</h3><p>{{ $all ? 'Use Assess dues to create the first period for a member or all active members.' : 'Your dues periods and payment history will appear once the Treasurer records them.' }}</p></div>@endforelse
</section>
@include('chapter.partials.pagination', ['items' => $dues, 'label' => 'Dues record pages'])
@endif
@if($all)@include('chapter.partials.dues-assessment-dialog')@endif
@if($all && !$assessmentPage)
<section class="dues-account-panel panel" id="member-accounts" aria-labelledby="member-accounts-title">
    <div class="dues-account-heading"><div><span class="eyebrow">ACCOUNT DIRECTORY</span><h2 id="member-accounts-title">Registered accounts</h2><p>Find an account to view its dues. New assessments can be created for active accounts.</p></div><span class="dues-account-total">{{ number_format($accountRecords->total()) }} {{ \Illuminate\Support\Str::plural('account', $accountRecords->total()) }}</span></div>
    <div class="table-wrap"><table class="dues-account-table"><thead><tr><th>Account</th><th>Role</th><th>Account status</th><th>Dues periods</th><th><span class="sr-only">Action</span></th></tr></thead><tbody>
        @forelse($accountRecords as $account)
        <tr><td data-label="Account"><span class="dues-account-name"><strong>{{ $account->name }}</strong><small>{{ $account->member_no ?: $account->email }}</small></span></td><td data-label="Role">{{ \Illuminate\Support\Str::headline($account->role) }}</td><td data-label="Account status"><span class="dues-account-status {{ $account->status === 'active' ? 'is-active' : 'is-inactive' }}">{{ \Illuminate\Support\Str::headline($account->status) }}</span></td><td data-label="Dues periods">{{ number_format($account->member_dues_count) }}</td><td data-label="Action"><div class="dues-account-actions"><a class="text-link" href="{{ route('dues.assessments', ['member' => $account->id]).'#dues-records' }}">View dues</a>@if($account->status === 'active')<button class="btn secondary dues-assess-button" type="button" data-finance-dialog-open="dues-assessment-dialog" data-dues-member-id="{{ $account->id }}">Assess dues</button>@endif</div></td></tr>
        @empty<tr><td colspan="5" class="due-no-payments">No registered accounts match these filters.</td></tr>@endforelse
    </tbody></table></div>
    @include('chapter.partials.pagination', ['items' => $accountRecords, 'label' => 'Account pages'])
</section>
@endif
@endsection
