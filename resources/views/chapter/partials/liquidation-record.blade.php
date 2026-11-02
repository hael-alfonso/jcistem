@php
    $data = $record->data ?? [];
    $project = $record->project;
    $projectDetails = $data['project_snapshot'] ?? [];
    $snapshot = $data['financial_snapshot'] ?? [];
    $expenses = $snapshot['expense_lines'] ?? $project?->transactions()->where('status', 'Posted')->where('direction', 'debit')->orderBy('transaction_date')->get()->map(fn ($entry) => ['date' => $entry->transaction_date?->format('Y-m-d'), 'reference' => $entry->reference, 'receipt' => $entry->document_name, 'description' => $entry->description, 'quantity' => 1, 'amount' => (float) $entry->amount])->all() ?? [];
    $actual = (float) ($snapshot['actual_expenses'] ?? collect($expenses)->sum('amount'));
    $cash = (float) ($data['cash_received'] ?? 0);
@endphp
<article class="panel form-panel printable-document project-document">
    <header class="project-letterhead"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="JCI logo" width="140" height="78"><div><strong>JCI Carmona</strong><span>Junior Chamber International</span></div></header>
    <p class="document-overline">LIQUIDATION REPORT</p>
    <h2>Project and report information</h2>
    <div class="table-wrap"><table><tbody>
        <tr><th>Local Organization</th><td>JCI Carmona</td><th>Report date</th><td>{{ $data['report_date'] ?? 'Not set' }}</td></tr>
        <tr><th>Project / activity</th><td>{{ $projectDetails['title'] ?? $project?->title }}</td><th>Project date</th><td>{{ !empty($projectDetails['date']) ? \Illuminate\Support\Carbon::parse($projectDetails['date'])->format('d/m/Y') : ($project?->starts_on?->format('d/m/Y') ?? 'Not set') }}</td></tr>
        <tr><th>Project chair / officer</th><td>{{ $projectDetails['chair_name'] ?? $project?->chair?->name ?? $record->author?->name }}</td><th>Reference / voucher</th><td>{{ $data['voucher_reference'] ?? 'Not set' }}</td></tr>
    </tbody></table></div>
    <h2>Expense details</h2>
    <div class="table-wrap"><table><thead><tr><th>Item</th><th>Receipt date</th><th>OR / invoice</th><th>Description / particulars</th><th>Qty</th><th>Amount (PHP)</th></tr></thead><tbody>
        @forelse($expenses as $expense)<tr><td>{{ $loop->iteration }}</td><td>{{ $expense['date'] ?? '—' }}</td><td>{{ $expense['receipt'] ?? $expense['reference'] ?? '—' }}</td><td>{{ $expense['description'] ?? '—' }}</td><td>{{ $expense['quantity'] ?? 1 }}</td><td>{{ number_format((float) ($expense['amount'] ?? 0), 2) }}</td></tr>
        @empty<tr><td colspan="6">No posted project expenses yet.</td></tr>@endforelse
        <tr><th colspan="5">Total actual expenses</th><th>{{ number_format($actual, 2) }}</th></tr>
    </tbody></table></div>
    <h2>Cash reconciliation</h2>
    <div class="table-wrap"><table><tbody><tr><th>Total cash advanced / received</th><td>PHP {{ number_format($cash, 2) }}</td></tr><tr><th>Less: total actual expenses</th><td>PHP {{ number_format($actual, 2) }}</td></tr><tr><th>Excess cash to be returned</th><td>PHP {{ number_format(max(0, $cash - $actual), 2) }}</td></tr><tr><th>Amount to be reimbursed</th><td>PHP {{ number_format(max(0, $actual - $cash), 2) }}</td></tr></tbody></table></div>
    @if($data['receipt_notes'] ?? null)<p class="preserve-lines"><strong>Receipt references:</strong> {{ $data['receipt_notes'] }}</p>@endif
    @if($data['reconciliation_notes'] ?? null)<p class="preserve-lines"><strong>Reconciliation:</strong> {{ $data['reconciliation_notes'] }}</p>@endif
    <h2>Certification and signatures</h2>
    <div class="document-signatures"><div class="document-signature"><span>Prepared by · Signature</span><strong>{{ $projectDetails['chair_name'] ?? $project?->chair?->name ?? $record->author?->name }}</strong><small>Project Chair / Accountable Officer</small></div><div class="document-signature"><span>Verified by · Signature</span><strong>{{ $data['treasurer_name'] ?? 'LO Treasurer' }}</strong><small>Local Organization Treasurer</small></div><div class="document-signature"><span>Approved by · Signature</span><strong>{{ $data['president_name'] ?? 'LO President' }}</strong><small>Local Organization President</small></div></div>
    <p class="hint">Attach original receipts and invoices in the same order as the expense list. Return excess cash to the Treasurer; reimbursements require verification and signatures.</p>
</article>
