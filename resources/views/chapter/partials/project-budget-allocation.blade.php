@php
    $budgetLines = collect($project->budget_items ?? []);
    $allocationsByLine = $project->allocations->whereNotNull('budget_item_id')->keyBy('budget_item_id');
    $postedExpenses = $project->transactions->where('status', 'Posted')->where('direction', 'debit');
    $spentByLine = $postedExpenses->groupBy('budget_item_id')->map(fn ($rows) => (float) $rows->sum('amount'));
    $pendingClaims = $project->expenseClaims->where('status', 'Pending');
    $pendingByLine = $pendingClaims->groupBy('budget_item_id')->map(fn ($rows) => (float) $rows->sum('amount'));
    $pendingReleases = $project->fundReleases->where('status', 'Pending');
    $pendingReleasesByLine = $pendingReleases->groupBy('budget_item_id')->map(fn ($rows) => (float) $rows->sum('amount'));
    $legacyAllocations = $project->allocations->whereNull('budget_item_id')->where('amount', '>', 0);
    $availableForClaims = max(0, (float) $project->allocated - (float) $project->spent - (float) $pendingClaims->sum('amount') - (float) $pendingReleases->sum('amount'));
    $canAllocate = $u->role === 'treasurer' && $u->status === 'active' && $project->chair_id !== $u->id
        && (in_array($project->status, ['Plan Locked', 'Ongoing'], true) || ($project->status === 'Approved' && $project->proposal_reviewed_at));
@endphp
<div class="project-finance-guide" role="note">
    <strong>How project funds move</strong>
    <ol><li><b>Approved plan</b><span>The project budget sets the maximum for each expense.</span></li><li><b>Treasurer allocation</b><span>The Treasurer authorizes an amount for each approved line.</span></li><li><b>Advance or claim</b><span>Cash advances need Chair receipt confirmation. Reimbursements need a receipt claim.</span></li><li><b>Official ledger</b><span>Confirmed advances and verified claims post once and reduce available funds.</span></li></ol>
</div>
<div class="project-finance-summary" aria-label="Project budget summary">
    <div><span>{{ $project->proposal_reviewed_at ? 'Approved plan' : 'Proposed plan' }}</span><strong>PHP {{ number_format((float) $project->proposed_budget, 2) }}</strong><small>Maximum planned spending</small></div>
    <div><span>Treasurer allocated</span><strong>PHP {{ number_format((float) $project->allocated, 2) }}</strong><small>Authorized by expense line</small></div>
    <div><span>Posted expenses</span><strong>PHP {{ number_format((float) $project->spent, 2) }}</strong><small>Verified ledger debits only</small></div>
    <div><span>Available allocation</span><strong>PHP {{ number_format($availableForClaims, 2) }}</strong><small>After PHP {{ number_format((float) $pendingClaims->sum('amount') + (float) $pendingReleases->sum('amount'), 2) }} reserved</small></div>
</div>
<div class="project-finance-heading"><div><h3 class="subheading">Expense allocations</h3><p>Each expense has its own limit. Pending claims and fund releases awaiting confirmation reserve funds.</p></div><div class="project-finance-links no-print">@if($u->role === 'treasurer')<a class="btn secondary" href="{{ route('expense-claims.index', ['project' => $project->id, 'status' => 'pending']) }}">Review {{ $pendingClaims->count() }} pending {{ \Illuminate\Support\Str::plural('claim', $pendingClaims->count()) }}</a><a class="btn secondary" href="{{ route('ledger', ['project' => $project->id]) }}">View project ledger</a>@elseif($project->isChair($u))<a class="btn secondary" href="{{ route('expense-claims.index', ['project' => $project->id, 'status' => 'all']) }}">My expense claims</a>@endif</div></div>
<div class="table-wrap project-allocation-wrap"><table class="project-allocation-table"><thead><tr><th>Approved expense</th><th>Plan limit</th><th>Allocated</th><th>Posted</th><th>Reserved</th><th>Available</th></tr></thead><tbody>
@forelse($budgetLines as $item)
@php
    $itemId = $item['id'] ?? '';
    $allocation = $allocationsByLine->get($itemId);
    $spent = (float) $spentByLine->get($itemId, 0);
    $pendingClaimAmount = (float) $pendingByLine->get($itemId, 0);
    $pendingReleaseAmount = (float) $pendingReleasesByLine->get($itemId, 0);
    $pending = $pendingClaimAmount + $pendingReleaseAmount;
    $approved = (float) ($allocation?->amount ?? 0);
    $available = max(0, $approved - $spent - $pending);
@endphp
<tr><td data-label="Expense"><strong>{{ $item['description'] }}</strong><small>{{ \App\Support\ProjectBudget::CATEGORIES[$item['category']] ?? 'Other expense' }}</small>@if($allocation && $allocation->budget_item_description !== $item['description'])<small>Plan description changed since allocation</small>@endif</td><td data-label="Plan limit">PHP {{ number_format((float) $item['amount'], 2) }}</td><td data-label="Allocated"><strong>PHP {{ number_format($approved, 2) }}</strong>@if($allocation)<small>{{ $allocation->approved_by }} &middot; {{ $allocation->approved_on?->format('M d, Y') }}</small>@else<small>Awaiting Treasurer allocation</small>@endif</td><td data-label="Posted">PHP {{ number_format($spent, 2) }}</td><td data-label="Reserved"><strong>PHP {{ number_format($pending, 2) }}</strong><small>{{ $pendingClaimAmount > 0 ? 'Claims PHP '.number_format($pendingClaimAmount, 2) : '' }}{{ $pendingClaimAmount > 0 && $pendingReleaseAmount > 0 ? ' · ' : '' }}{{ $pendingReleaseAmount > 0 ? 'Releases PHP '.number_format($pendingReleaseAmount, 2) : '' }}</small></td><td data-label="Available"><strong>PHP {{ number_format($available, 2) }}</strong></td></tr>
@empty<tr><td colspan="6">No itemized budget yet. Record the approved expense lines before allocating funds.</td></tr>@endforelse
</tbody></table></div>
@if($legacyAllocations->isNotEmpty() || (float) $spentByLine->get('', 0) > 0)<p class="project-finance-alert">Earlier finance records still need to be matched to approved expense lines below. New claims and spending are paused until reconciliation is complete.</p>@endif
@if($canAllocate && $budgetLines->isNotEmpty())
<div class="project-finance-form-action no-print" id="allocation-form"><div><strong>Set or adjust an expense allocation</strong><small>Authorize a total for one approved expense line.</small></div><button class="btn secondary" type="button" data-finance-dialog-open="project-allocation-dialog"><x-icon name="plus" /> Set allocation</button></div>
<dialog class="finance-dialog" id="project-allocation-dialog" aria-labelledby="allocation-form-title"><div class="finance-dialog-header"><div><span class="eyebrow">PROJECT BUDGET</span><h2 id="allocation-form-title">Set or adjust an expense allocation</h2><p>{{ $project->title }}</p></div><button class="finance-dialog-close" type="button" data-finance-dialog-close aria-label="Close allocation form"><x-icon name="close" /></button></div><div class="finance-dialog-body">@if(old('_finance_dialog') === 'project-allocation-dialog' && $errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif<form method="POST" action="{{ route('budget.store', $project) }}" class="form-grid">@csrf<input type="hidden" name="_finance_dialog" value="project-allocation-dialog">
<p class="full hint">Choose one approved expense line. Re-entering a line adjusts its allocation; the total cannot exceed its plan limit or fall below posted expenses, pending claims, and fund releases awaiting confirmation.</p>
<x-field name="budget_item_id" label="Approved expense line" type="select" :options="$budgetLines->mapWithKeys(fn ($item) => [$item['id'] => $item['description'].' · Plan PHP '.number_format((float) $item['amount'], 2)])->all()" required/><x-field name="amount" label="New total allocation for this line (PHP)" type="number" step="0.01" min="0" required/><x-field name="approved_by" label="Approving authority" required/><x-field name="approved_on" label="Approval date" type="date" required/><div class="full"><x-field name="remarks" label="Allocation or adjustment reason" type="textarea" required/></div><div class="finance-dialog-actions"><button class="btn secondary" type="button" data-finance-dialog-close>Cancel</button><button class="btn primary"><x-icon name="save" /> Save allocation</button></div></form></div></dialog>@if(old('_finance_dialog') === 'project-allocation-dialog')<span hidden data-finance-dialog-reopen="project-allocation-dialog"></span>@endif
@endif
