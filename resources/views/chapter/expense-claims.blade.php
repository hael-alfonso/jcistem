@extends('layouts.chapter')
@section('title', $treasurer ? 'Expense claims to review' : 'My expense claims')
@section('content')
<div class="page-heading"><div><span class="eyebrow">PROJECT FINANCE</span><h1>{{ $treasurer ? 'Expense claims to review' : 'My expense claims' }}</h1><p>{{ $treasurer ? 'Verify the receipt and approved budget expense before posting a claim.' : 'Follow the Treasurer decision on expenses you submitted as Project Chair.' }}</p></div></div>
@if($selectedProject)<div class="task-chair-guide"><div><strong>{{ $selectedProject->title }}</strong><p>Showing this project's expense claims.</p></div>@if(!$selectedProject->trashed())<a class="btn secondary" href="{{ route('projects.show', $selectedProject) }}#budget">Project budget</a>@endif<a class="text-link" href="{{ route('expense-claims.index', array_filter(request()->only('mine'))) }}">All projects</a></div>@endif
<nav class="filter-tabs claim-tabs no-print" aria-label="Claim status"><a href="{{ route('expense-claims.index', array_filter(request()->only(['project', 'q', 'from', 'to', 'mine'])) + ['status' => 'pending']) }}" class="{{ $filter === 'pending' ? 'is-active' : '' }}" @if($filter === 'pending') aria-current="page" @endif>Pending</a><a href="{{ route('expense-claims.index', array_filter(request()->only(['project', 'q', 'from', 'to', 'mine'])) + ['status' => 'all']) }}" class="{{ $filter === 'all' ? 'is-active' : '' }}" @if($filter === 'all') aria-current="page" @endif>All claims</a></nav>
<form class="list-filters record-filters" method="GET" action="{{ route('expense-claims.index') }}"><input type="hidden" name="status" value="{{ $filter }}">@if(request()->boolean('mine'))<input type="hidden" name="mine" value="1">@endif
<label>Find claim<input name="q" value="{{ request('q') }}" placeholder="Payee, project, or receipt"></label>
<label>Project<select name="project"><option value="">All projects</option>@foreach($claimProjects as $claimProject)<option value="{{ $claimProject->id }}" @selected((string) request('project') === (string) $claimProject->id)>{{ $claimProject->title }}</option>@endforeach</select></label>
@if($filter === 'all')<label>Decision<select name="state"><option value="">Any decision</option>@foreach(['Pending', 'Posted', 'Returned', 'Rejected', 'Void'] as $state)<option value="{{ $state }}" @selected(request('state') === $state)>{{ $state }}</option>@endforeach</select></label>@endif
<label>Expense from<input type="date" name="from" value="{{ request('from') }}"></label><label>Expense to<input type="date" name="to" value="{{ request('to') }}"></label>
<button class="btn secondary" type="submit"><x-icon name="filter"/> Apply filters</button>@if(request()->hasAny(['q', 'project', 'state', 'from', 'to']))<a class="text-link" href="{{ route('expense-claims.index', ['status' => $filter] + array_filter(request()->only('mine'))) }}"><x-icon name="close"/> Clear filters</a>@endif</form>
<section class="expense-claim-list" aria-label="Expense claims">
@forelse($claims as $claim)
@php($budgetLine = collect($claim->project?->budget_items ?? [])->firstWhere('id', $claim->budget_item_id))
<article class="panel expense-claim-card">
    <div class="expense-claim-heading"><div><span class="eyebrow">CLAIM #{{ $claim->id }} · {{ $claim->project?->reference }}</span><h2>{{ $claim->project?->title ?? 'Project' }}</h2><p>{{ $claim->description }}</p></div><div class="claim-amount-status"><strong>PHP {{ number_format((float) $claim->amount, 2) }}</strong><span class="badge {{ $claim->status === 'Posted' ? 'status-approved' : ($claim->status === 'Pending' ? 'status-review' : (in_array($claim->status, ['Void', 'Rejected'], true) ? 'status-declined' : 'status-revision')) }}">{{ $claim->status }}</span></div></div>
    <dl class="expense-claim-facts"><div><dt>Budget expense</dt><dd>{{ $budgetLine['description'] ?? 'Legacy expense line' }}</dd></div><div><dt>Paid to</dt><dd>{{ $claim->payee }}</dd></div><div><dt>Expense date</dt><dd>{{ $claim->spent_on?->format('d M Y') }}</dd></div><div><dt>Submitted by</dt><dd>{{ $claim->submitter?->name }}</dd></div></dl>
    <div class="button-row claim-actions"><a class="btn secondary" href="{{ route('expense-claims.receipt', $claim) }}" target="_blank" rel="noopener"><x-icon name="eye" /> View original receipt</a>@if(!$claim->project?->trashed())<a class="text-link" href="{{ route('projects.show', $claim->project_id) }}#budget">Open project budget <x-icon name="arrow" /></a>@endif</div>
    @if($claim->review_note)<p class="expense-claim-note"><strong>Treasurer decision:</strong> {{ $claim->review_note }}</p>@endif
    @if($claim->submitted_by === auth()->id() && $claim->status === 'Returned' && $claim->project?->canManage(auth()->user()))
    <details class="claim-review-disclosure no-print"><summary><x-icon name="edit" /> Correct and resubmit <x-icon name="chevron" /></summary>
        <form method="POST" action="{{ route('expense-claims.update', $claim) }}" enctype="multipart/form-data" class="form-grid expense-claim-review">@csrf @method('PUT')
            <label class="field full"><span>Approved budget expense</span><select name="budget_item_id" required>@foreach($claim->project->budget_items ?? [] as $line)<option value="{{ $line['id'] ?? '' }}" @selected(($line['id'] ?? null) === $claim->budget_item_id)>{{ $line['description'] ?? 'Expense' }} · PHP {{ number_format((float) ($line['amount'] ?? 0), 2) }}</option>@endforeach</select></label>
            <x-field name="spent_on" label="Expense date" type="date" :value="$claim->spent_on?->format('Y-m-d')" required/>
            <x-field name="payee" label="Paid to" :value="$claim->payee" required maxlength="200"/>
            <x-field name="amount" label="Amount (PHP)" type="number" min="0.01" step="0.01" :value="$claim->amount" required/>
            <x-field name="receipt_reference" label="Receipt reference" :value="$claim->receipt_reference" maxlength="100"/>
            <div class="full"><x-field name="description" label="Expense details" type="textarea" :value="$claim->description" required/></div>
            <label class="field full"><span>Replacement receipt (optional)</span><input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"><small>The original receipt stays attached unless you choose a replacement.</small></label>
            <div class="full"><button class="btn primary" data-confirm="Send this corrected expense claim back to the Treasurer for verification?">Resubmit corrected claim</button></div>
        </form>
    </details>
    @endif
    @if($treasurer && $claim->status === 'Pending' && $claim->submitted_by !== auth()->id() && $claim->project?->chair_id !== auth()->id())
    <details class="claim-review-disclosure no-print" @if($errors->any()) open @endif><summary><x-icon name="checklist" /> Review and decide <x-icon name="chevron" /></summary>
        <div class="expense-claim-review"><form method="POST" action="{{ route('expense-claims.review', $claim) }}" class="form-grid">@csrf<input type="hidden" name="action" value="approve"><h3 class="full">Verify and post</h3><x-field name="account" label="Chapter account / fund" value="Chapter fund" required/><x-field name="payment_method" label="Payment method on receipt" type="select" :options="array_combine(['Cash','Bank transfer','GCash','Cheque','Other'],['Cash','Bank transfer','GCash','Cheque','Other'])" required/><div class="full"><x-field name="review_note" label="Verification note" type="textarea" required/></div><div class="full"><button class="btn primary" data-confirm="Verify this receipt and post the expense to the official ledger?">Verify and post expense</button></div></form>
        <form method="POST" action="{{ route('expense-claims.review', $claim) }}">@csrf<h3>Return or reject</h3><label class="field"><span>Reason for return or rejection</span><textarea name="review_note" required maxlength="5000"></textarea></label><div class="button-row"><button class="btn caution" name="action" value="return" data-confirm="Return this claim for correction?">Return to Chair</button><button class="btn danger" name="action" value="reject" data-confirm="Reject this expense claim?">Reject claim</button></div></form></div>
    </details>
    @endif
</article>
@empty
<div class="panel empty-state"><x-icon name="receipt"/><h2>No expense claims here</h2><p>{{ $treasurer ? 'Claims submitted by Project Chairs will appear here for review.' : 'Submit a claim from an active project’s Budget section.' }}</p>@unless($treasurer)<a class="btn secondary" href="{{ route('projects') }}">Open projects</a>@endunless</div>
@endforelse
</section>
@include('chapter.partials.pagination', ['items' => $claims])
@endsection
