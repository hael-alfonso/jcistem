@extends('layouts.chapter')
@section('title', $treasurer ? 'Expense claims to review' : 'My expense claims')
@section('content')
<div class="page-heading"><div><span class="eyebrow">PROJECT FINANCE</span><h1>{{ $treasurer ? 'Expense claims to review' : 'My expense claims' }}</h1><p>{{ $treasurer ? 'Verify the original receipt and approved budget line before posting a project expense.' : 'Track claims you submitted as Project Chair and see the Treasurer’s decision.' }}</p></div></div>
<div class="filter-tabs no-print"><a href="{{ route('expense-claims.index') }}" class="{{ request('status', 'pending') === 'pending' ? 'is-active' : '' }}">Pending</a><a href="{{ route('expense-claims.index', ['status' => 'all']) }}" class="{{ request('status') === 'all' ? 'is-active' : '' }}">All claims</a></div>
<div class="expense-claim-list">
@forelse($claims as $claim)
<article class="panel form-panel expense-claim-card">
    <div class="expense-claim-heading"><div><span class="eyebrow">CLAIM #{{ $claim->id }} · {{ $claim->project?->reference }}</span><h2>{{ $claim->project?->title ?? 'Project' }}</h2><p>{{ $claim->description }}</p></div><span class="badge {{ $claim->status === 'Posted' ? 'status-approved' : ($claim->status === 'Pending' ? 'status-review' : ($claim->status === 'Void' ? 'status-declined' : 'status-revision')) }}">{{ $claim->status }}</span></div>
    <dl class="expense-claim-facts"><div><dt>Amount</dt><dd>PHP {{ number_format((float) $claim->amount, 2) }}</dd></div><div><dt>Paid to</dt><dd>{{ $claim->payee }}</dd></div><div><dt>Expense date</dt><dd>{{ $claim->spent_on?->format('d M Y') }}</dd></div><div><dt>Submitted by</dt><dd>{{ $claim->submitter?->name }}</dd></div></dl>
    <div class="button-row"><a class="btn secondary" href="{{ route('expense-claims.receipt', $claim) }}" target="_blank" rel="noopener"><x-icon name="eye" /> View original receipt</a><a class="btn secondary" href="{{ route('projects.show', $claim->project_id) }}#budget"><x-icon name="briefcase" /> Open project budget</a></div>
    @if($claim->review_note)<p class="expense-claim-note"><strong>Treasurer decision:</strong> {{ $claim->review_note }}</p>@endif
    @if($treasurer && $claim->status === 'Pending' && $claim->submitted_by !== auth()->id() && $claim->project?->chair_id !== auth()->id())
    <div class="expense-claim-review no-print">
        <form method="POST" action="{{ route('expense-claims.review', $claim) }}" class="form-grid">@csrf<input type="hidden" name="action" value="approve"><x-field name="account" label="Chapter account / fund" value="Chapter fund" required/><x-field name="payment_method" label="Payment method on receipt" type="select" :options="array_combine(['Cash','Bank transfer','GCash','Cheque','Other'],['Cash','Bank transfer','GCash','Cheque','Other'])" required/><div class="full"><x-field name="review_note" label="Verification note" type="textarea" required/></div><div class="full"><button class="btn primary" data-confirm="Verify this receipt and post the expense to the official ledger?">Verify and post expense</button></div></form>
        <form method="POST" action="{{ route('expense-claims.review', $claim) }}">@csrf<label class="field"><span>Reason for return or rejection</span><textarea name="review_note" required maxlength="5000"></textarea></label><div class="button-row"><button class="btn caution" name="action" value="return" data-confirm="Return this claim for correction?">Return to Chair</button><button class="btn danger" name="action" value="reject" data-confirm="Reject this expense claim?">Reject claim</button></div></form>
    </div>
    @endif
</article>
@empty
<div class="panel form-panel empty-state"><x-icon name="receipt"/><h2>No expense claims here</h2><p>{{ $treasurer ? 'Claims submitted by Project Chairs will appear here for review.' : 'Submit a claim from an active project’s Budget section.' }}</p></div>
@endforelse
</div>
@include('chapter.partials.pagination', ['items' => $claims])
@endsection
