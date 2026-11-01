@php
    $budgetLines = collect($project->budget_items ?? []);
    $allocationsByLine = $project->allocations->whereNotNull('budget_item_id')->keyBy('budget_item_id');
    $spentByLine = $project->transactions->where('status', 'Posted')->where('direction', 'debit')->groupBy('budget_item_id')->map(fn ($rows) => $rows->sum('amount'));
    $legacyAllocations = $project->allocations->whereNull('budget_item_id');
@endphp
<h3 class="subheading">Expense-level allocation and spending</h3>
<div class="table-wrap"><table><thead><tr><th>Specific expense</th><th>Proposed</th><th>Approved allocation</th><th>Posted spending</th><th>Available on this line</th></tr></thead><tbody>
@forelse($budgetLines as $item)
@php $allocation = $allocationsByLine->get($item['id'] ?? ''); $spent = (float) $spentByLine->get($item['id'] ?? '', 0); $approved = (float) ($allocation?->amount ?? 0); @endphp
<tr><td><strong>{{ $item['description'] }}</strong><small>{{ \App\Support\ProjectBudget::CATEGORIES[$item['category']] ?? 'Other expense' }}@if($allocation && $allocation->budget_item_description !== $item['description']) · Plan description changed since allocation @endif</small></td><td>PHP {{ number_format((float) $item['amount'], 2) }}</td><td>PHP {{ number_format($approved, 2) }}@if($allocation)<small>{{ $allocation->approved_by }} · {{ $allocation->approved_on?->format('M d, Y') }}</small>@endif</td><td>PHP {{ number_format($spent, 2) }}</td><td>PHP {{ number_format($approved - $spent, 2) }}</td></tr>
@empty<tr><td colspan="5">No itemized budget yet. Add the specific expenses before recording new allocations.</td></tr>@endforelse
<tr><th>Total</th><th>PHP {{ number_format((float) $project->proposed_budget, 2) }}</th><th>PHP {{ number_format((float) $project->allocated, 2) }}</th><th>PHP {{ number_format((float) $project->spent, 2) }}</th><th>PHP {{ number_format((float) $project->remaining, 2) }}</th></tr>
</tbody></table></div>
@if($legacyAllocations->isNotEmpty())<p class="hint">{{ $legacyAllocations->count() }} earlier category allocation(s) total PHP {{ number_format((float) $legacyAllocations->sum('amount'), 2) }}. These remain in the project total but are not assigned to a specific expense line.</p>@endif
@if($spentByLine->get('', 0) > 0)<p class="hint">PHP {{ number_format((float) $spentByLine->get('', 0), 2) }} of earlier posted expenses is not assigned to a specific line.</p>@endif
@if($u->role === 'treasurer' && (in_array($project->status, ['Plan Locked', 'Ongoing'], true) || ($project->status === 'Approved' && $project->proposal_reviewed_at)) && $budgetLines->isNotEmpty())
<details class="record-detail no-print"><summary><x-icon name="wallet" /> Allocate a specific expense<x-icon name="chevron" class="disclosure-icon" /></summary><form method="POST" action="{{ route('budget.store', $project) }}" class="form-grid">@csrf
<x-field name="budget_item_id" label="Expense from approved project budget" type="select" :options="$budgetLines->mapWithKeys(fn ($item) => [$item['id'] => $item['description'].' · PHP '.number_format((float) $item['amount'], 2)])->all()" required/><x-field name="amount" label="Approved amount for this expense (PHP)" type="number" step="0.01" min="0" required/><x-field name="approved_by" label="Approving authority" required/><x-field name="approved_on" label="Approval date" type="date" required/><div class="full"><x-field name="remarks" label="Allocation remarks / adjustment reason" type="textarea" required/></div><div class="full"><button class="btn primary"><x-icon name="save" /> Save expense allocation</button></div></form></details>
@endif
