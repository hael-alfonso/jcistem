@php
    $unlinkedAllocations = $project->allocations->filter(fn ($allocation) => !$allocation->budget_item_id && (float) $allocation->amount > 0);
    $unlinkedExpenses = $project->transactions->filter(fn ($entry) => $entry->status === 'Posted' && $entry->direction === 'debit' && !$entry->budget_item_id);
    $canReconcile = $u->role === 'treasurer' && in_array($project->status, ['Approved', 'Plan Locked', 'Ongoing'], true);
@endphp
@if($unlinkedAllocations->isNotEmpty() || $unlinkedExpenses->isNotEmpty())
<div class="budget-reconciliation">
    <div class="panel-heading flush"><div><h3>Earlier finance records need matching</h3><p>{{ $unlinkedAllocations->count() }} allocation(s) and {{ $unlinkedExpenses->count() }} posted expense(s) still need a specific budget line. The original amounts will stay unchanged.</p></div></div>
    @if($canReconcile && empty($project->budget_items))
    <section class="record-detail no-print" aria-labelledby="reconcile-plan-title"><div class="project-record-heading"><div><strong id="reconcile-plan-title">Step 1: Record the approved historical breakdown</strong><small>Classify the existing approved total without changing it.</small></div><x-icon name="checklist" /></div>
        <form method="POST" action="{{ route('budget.reconcile-plan', $project) }}">@csrf
            @include('chapter.partials.project-budget-builder', ['project' => $project, 'proposal' => true])
            <p class="hint">Classify the original approved total only. This does not increase the project budget.</p>
            <button class="btn primary" data-confirm="Record this historical budget breakdown without changing the approved total?">Save historical breakdown</button>
        </form>
    </section>
    @endif
    @if($canReconcile && !empty($project->budget_items))
        @foreach($unlinkedAllocations as $allocation)
        <section class="record-detail no-print" aria-labelledby="reconcile-allocation-title-{{ $allocation->id }}"><div class="project-record-heading"><div><strong id="reconcile-allocation-title-{{ $allocation->id }}">Step 2: Match allocation #{{ $allocation->id }} · PHP {{ number_format((float) $allocation->amount, 2) }}</strong><small>Distribute the original allocation across its correct expense lines.</small></div><x-icon name="wallet" /></div>
            <form method="POST" action="{{ route('budget.reconcile-allocation', [$project, $allocation]) }}" class="form-grid">@csrf
                <p class="full hint">Original category: {{ $allocation->category }}. Enter amounts for the correct expense lines. Their sum must be exactly PHP {{ number_format((float) $allocation->amount, 2) }}.</p>
                @foreach($project->budget_items as $item)
                <label class="field"><span>{{ $item['description'] }} · approved PHP {{ number_format((float) $item['amount'], 2) }}</span><input type="number" name="lines[{{ $item['id'] }}]" min="0" step="0.01" value="0.00" required></label>
                @endforeach
                <div class="full"><button class="btn primary" data-confirm="Match this original allocation to the entered expense lines? Its total will not change.">Match allocation</button></div>
            </form>
        </section>
        @endforeach
        @foreach($unlinkedExpenses as $entry)
        <section class="record-detail no-print" aria-labelledby="reconcile-entry-title-{{ $entry->id }}"><div class="project-record-heading"><div><strong id="reconcile-entry-title-{{ $entry->id }}">Step 3: Match posted expense {{ $entry->reference }} · PHP {{ number_format((float) $entry->amount, 2) }}</strong><small>Link the posted debit to its approved expense line.</small></div><x-icon name="receipt" /></div>
            <form method="POST" action="{{ route('budget.reconcile-entry', [$project, $entry]) }}" class="form-grid">@csrf
                <p class="full hint">{{ $entry->description }} · original category {{ $entry->category }}. Select the line whose allocation covers this expense.</p>
                <x-field name="budget_item_id" label="Specific approved expense" type="select" :options="collect($project->budget_items)->mapWithKeys(fn ($item) => [$item['id'] => $item['description']])->all()" required/>
                <div class="full"><button class="btn primary" data-confirm="Link this posted expense to the selected approved budget line?">Match expense</button></div>
            </form>
        </section>
        @endforeach
    @elseif(!$canReconcile)
        <p class="hint">A Treasurer can reconcile active projects. Closed and reporting projects keep their locked historical record.</p>
    @endif
</div>
@endif
