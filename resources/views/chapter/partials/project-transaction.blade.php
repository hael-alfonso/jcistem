<details class="panel form-panel no-print"><summary class="large-summary">＋ Record financial transaction</summary><form method="POST" action="{{ route('project-transactions.store', $project) }}" enctype="multipart/form-data" class="form-grid">@csrf
<x-field name="reference" label="Unique transaction reference" required/><x-field name="transaction_date" label="Transaction date" type="date" :value="now()->format('Y-m-d')" required/>
<x-field name="type" label="Transaction type" type="select" :options="array_combine(['Income','Expense'],['Income','Expense'])" required/>
<x-field name="amount" label="Amount (PHP)" type="number" step="0.01" min="0.01" required/>
<x-field name="category" label="Category" required/><x-field name="account" label="Account / fund" value="Chapter fund" required/>
<x-field name="counterparty" label="Payee / payer" required/><x-field name="payment_method" label="Payment method" type="select" :options="array_combine(['Cash','Bank transfer','GCash','Cheque','Other'],['Cash','Bank transfer','GCash','Cheque','Other'])" value="Cash" required/>
<x-field name="description" label="Description" type="textarea" required/><x-field name="remarks" label="Remarks / adjustment explanation" type="textarea"/>
<x-field name="receipt" label="Receipt / supporting document (PDF or image, max 10 MB)" type="file" required/>
<div class="full"><div class="notice">Record dues payments in Member Dues to keep the monthly dues record linked to the ledger. Expenses may not exceed the project's available allocation.</div><button class="btn primary" data-confirm="Post this project transaction to the official ledger? Corrections require a recorded void."><x-icon name="check" /> Post transaction</button></div></form></details>
