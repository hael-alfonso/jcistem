@if(collect($project->budget_items ?? [])->isNotEmpty())
<section class="record-detail no-print" aria-labelledby="expense-claim-form-title">
    <div class="project-record-heading"><div><strong id="expense-claim-form-title">Submit an expense claim for Treasurer review</strong><small>Record a paid project expense and attach its original receipt.</small></div><x-icon name="receipt" /></div>
    <form method="POST" action="{{ route('expense-claims.store', $project) }}" enctype="multipart/form-data" class="form-grid">@csrf
        <x-field name="spent_on" label="Expense date" type="date" :value="now()->format('Y-m-d')" required/>
        <x-field name="budget_item_id" label="Approved expense line" type="select" :options="collect($project->budget_items ?? [])->mapWithKeys(fn ($item) => [$item['id'] => $item['description']])->all()" required/>
        <x-field name="amount" label="Amount paid (PHP)" type="number" step="0.01" min="0.01" required/>
        <x-field name="payee" label="Paid to" required/>
        <x-field name="receipt_reference" label="Receipt or invoice number (optional)"/>
        <x-field name="description" label="What was purchased and why" type="textarea" required/>
        <x-field name="receipt" label="Original receipt (PDF or image, max 10 MB)" type="file" accept=".pdf,.jpg,.jpeg,.png" required/>
        <div class="full"><p class="hint">The Treasurer verifies the receipt and budget line before this expense enters the official ledger.</p><button class="btn primary" data-confirm="Send this expense claim and receipt to the Treasurer?">Submit claim</button></div>
    </form>
</section>
@else
<p class="hint">Itemized budget lines and Treasurer allocations are needed before the Chair can submit expense claims.</p>
@endif
