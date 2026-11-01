@php
    $unlinkedAllocations = $project->allocations->filter(fn ($allocation) => !$allocation->budget_item_id && (float) $allocation->amount > 0);
    $unlinkedExpenses = $project->transactions->filter(fn ($entry) => $entry->status === 'Posted' && $entry->direction === 'debit' && !$entry->budget_item_id);
    $canReconcile = $u->role === 'treasurer' && in_array($project->status, ['Approved', 'Plan Locked', 'Ongoing'], true);
@endphp
@if($unlinkedAllocations->isNotEmpty() || $unlinkedExpenses->isNotEmpty())
<div class="budget-reconciliation">
    <div class="panel-heading flush"><div><h3>Earlier finance records need matching</h3><p>{{ $unlinkedAllocations->count() }} allocation(s) and {{ $unlinkedExpenses->count() }} posted expense(s) still need a specific budget line. The original amounts will stay unchanged.</p></div></div>
    @if($canReconcile && empty($project->budget_items))
    <details class="record-detail no-print"><summary><x-icon name="checklist" /> Step 1: Record the approved historical breakdown<x-icon name="chevron" class="disclosure-icon" /></summary>
        <form method="POST" action="{{ route('budget.reconcile-plan', $project) }}">@csrf
            @include('chapter.partials.project-budget-builder', ['project' => $project, 'proposal' => true])
            <p class="hint">Classify the original approved total only. This does not increase the project budget.</p>
            <button class="btn primary" data-confirm="Record this historical budget breakdown without changing the approved total?">Save historical breakdown</button>
        </form>
    </details>
    @endif
    @if($canReconcile && !empty($project->budget_items))
        @foreach($unlinkedAllocations as $allocation)
        <details class="record-detail no-print"><summary><x-icon name="wallet" /> Step 2: Match allocation #{{ $allocation->id }} · PHP {{ number_format((float) $allocation->amount, 2) }}<x-icon name="chevron" class="disclosure-icon" /></summary>
            <form method="POST" action="{{ route('budget.reconcile-allocation', [$project, $allocation]) }}" class="form-grid">@csrf
                <p class="full hint">Original category: {{ $allocation->category }}. Enter amounts for the correct expense lines. Their sum must be exactly PHP {{ number_format((float) $allocation->amount, 2) }}.</p>
                @foreach($project->budget_items as $item)
                <label class="field"><span>{{ $item['description'] }} · approved PHP {{ number_format((float) $item['amount'], 2) }}</span><input type="number" name="lines[{{ $item['id'] }}]" min="0" step="0.01" value="0.00" required></label>
                @endforeach
                <div class="full"><button class="btn primary" data-confirm="Match this original allocation to the entered expense lines? Its total will not change.">Match allocation</button></div>
            </form>
        </details>
        @endforeach
        @foreach($unlinkedExpenses as $entry)
        <details class="record-detail no-print"><summary><x-icon name="receipt" /> Step 3: Match posted expense {{ $entry->reference }} · PHP {{ number_format((float) $entry->amount, 2) }}<x-icon name="chevron" class="disclosure-icon" /></summary>
            <form method="POST" action="{{ route('budget.reconcile-entry', [$project, $entry]) }}" class="form-grid">@csrf
                <p class="full hint">{{ $entry->description }} · original category {{ $entry->category }}. Select the line whose allocation covers this expense.</p>
                <x-field name="budget_item_id" label="Specific approved expense" type="select" :options="collect($project->budget_items)->mapWithKeys(fn ($item) => [$item['id'] => $item['description']])->all()" required/>
                <div class="full"><button class="btn primary" data-confirm="Link this posted expense to the selected approved budget line?">Match expense</button></div>
            </form>
        </details>
        @endforeach
    @elseif(!$canReconcile)
        <p class="hint">A Treasurer can reconcile active projects. Closed and reporting projects keep their locked historical record.</p>
    @endif
</div>
@endif
