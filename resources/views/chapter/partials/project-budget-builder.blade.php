@php
    $budgetRows = $budgetRows ?? old('budget_items');
    if (!is_array($budgetRows)) $budgetRows = $project->budget_items ?? [];
    if (!$budgetRows && (float) ($project->proposed_budget ?? 0) > 0) {
        $budgetRows = [[
            'category' => 'other',
            'description' => 'General project expenses (add the specific breakdown)',
            'quantity' => '1.00',
            'unit_cost' => $project->proposed_budget,
            'amount' => $project->proposed_budget,
        ]];
    }
    if (!$budgetRows) $budgetRows = [['category' => '', 'description' => '', 'quantity' => '1.00', 'unit_cost' => '', 'amount' => '0.00']];
    $displayedBudgetTotal = $displayedBudgetTotal ?? collect($budgetRows)->sum(fn ($item) => (float) ($item['amount'] ?? ((float) ($item['quantity'] ?? 0) * (float) ($item['unit_cost'] ?? 0))));
@endphp
<div class="budget-builder" data-budget-builder @if($proposal) data-locked-total="{{ number_format((float) $project->proposed_budget, 2, '.', '') }}" @endif>
    <input type="hidden" name="proposed_budget" value="{{ number_format($displayedBudgetTotal, 2, '.', '') }}" data-budget-total-input>
    <div class="budget-builder-heading">
        <div><span class="eyebrow">ITEMIZED PROPOSED EXPENSES</span><h3>What will the project spend on?</h3><p>Add every expected purchase or service. The PMS calculates each line and the total automatically.</p></div>
        <button class="btn secondary" type="button" data-budget-add><x-icon name="plus" /> Add expense</button>
    </div>
    <div class="budget-list-heading" aria-hidden="true"><span>Category</span><span>Specific expense</span><span>Qty</span><span>Unit cost</span><span>Amount</span><span></span></div>
    <div class="budget-item-list" data-budget-list>
        @foreach($budgetRows as $index => $item)
            <div class="budget-item-row" data-budget-row>
                <label><span>Category</span><select name="budget_items[{{ $index }}][category]" required><option value="">Select category</option>@foreach(\App\Support\ProjectBudget::CATEGORIES as $value => $label)<option value="{{ $value }}" @selected(($item['category'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
                <label><span>Specific expense</span><input name="budget_items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" maxlength="255" placeholder="Example: Printing of 100 learning kits" required></label>
                <label><span>Quantity</span><input name="budget_items[{{ $index }}][quantity]" type="number" min="0.01" max="999999.99" step="0.01" value="{{ $item['quantity'] ?? '1.00' }}" required data-budget-quantity></label>
                <label><span>Unit cost (PHP)</span><input name="budget_items[{{ $index }}][unit_cost]" type="number" min="0" max="999999999.99" step="0.01" value="{{ $item['unit_cost'] ?? '' }}" placeholder="0.00" required data-budget-unit-cost></label>
                <output data-budget-line-total>PHP {{ number_format((float) ($item['amount'] ?? 0), 2) }}</output>
                <button type="button" class="budget-remove" data-budget-remove aria-label="Remove expense"><x-icon name="close" /></button>
            </div>
        @endforeach
    </div>
    <template data-budget-row-template>
        <div class="budget-item-row" data-budget-row>
            <label><span>Category</span><select name="budget_items[__INDEX__][category]" required><option value="">Select category</option>@foreach(\App\Support\ProjectBudget::CATEGORIES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
            <label><span>Specific expense</span><input name="budget_items[__INDEX__][description]" maxlength="255" placeholder="Example: Printing of 100 learning kits" required></label>
            <label><span>Quantity</span><input name="budget_items[__INDEX__][quantity]" type="number" min="0.01" max="999999.99" step="0.01" value="1.00" required data-budget-quantity></label>
            <label><span>Unit cost (PHP)</span><input name="budget_items[__INDEX__][unit_cost]" type="number" min="0" max="999999999.99" step="0.01" placeholder="0.00" required data-budget-unit-cost></label>
            <output data-budget-line-total>PHP 0.00</output>
            <button type="button" class="budget-remove" data-budget-remove aria-label="Remove expense"><x-icon name="close" /></button>
        </div>
    </template>
    <div class="budget-builder-total"><span>Estimated project budget</span><strong data-budget-total>PHP {{ number_format($displayedBudgetTotal, 2) }}</strong></div>
    @if($proposal)<p class="budget-lock-message" data-budget-lock-message>The itemized total must remain equal to the BOD-approved budget of PHP {{ number_format((float) $project->proposed_budget, 2) }}.</p>@else<p class="hint">This calculated total becomes the proposed budget reviewed by the Admin, Treasurer, and Board.</p>@endif
</div>
