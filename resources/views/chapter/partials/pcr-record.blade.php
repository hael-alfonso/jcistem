@php $data = $record->data ?? []; $project = $record->project; $snapshot = $data['financial_snapshot'] ?? []; @endphp
<article class="panel form-panel printable-document project-document">
    <header class="project-letterhead"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="JCI logo" width="140" height="78"><div><strong>JCI Carmona</strong><span>Junior Chamber International</span></div></header>
    <p class="document-overline">PROJECT COMPLETION REPORT (PCR)</p>
    <h2>Project information</h2>
    <div class="table-wrap"><table><tbody>
        <tr><th>Project title</th><td>{{ $project?->title }}</td><th>Reference</th><td>{{ $project?->reference }}</td></tr>
        <tr><th>JCI area</th><td>{{ $project?->area }}</td><th>Project chair</th><td>{{ $project?->chair?->name ?? $record->author?->name }}</td></tr>
        <tr><th>Schedule</th><td>{{ $project?->starts_on?->format('d/m/Y') ?? 'Not set' }} – {{ $project?->ends_on?->format('d/m/Y') ?? 'Not set' }}</td><th>Venue</th><td>{{ $project?->venue ?? 'Not set' }}</td></tr>
    </tbody></table></div>
    @foreach(\App\Support\ChapterForms::REPORT_SECTIONS as $heading => $keys)
        <h2>{{ $heading }}</h2>
        @foreach($keys as $key)<div class="record-section"><h3>{{ \App\Support\ChapterForms::REPORT[$key] }}</h3><p class="preserve-lines">{{ $data[$key] ?? 'Not provided' }}</p></div>@endforeach
    @endforeach
    @if($snapshot)
        <h2>Financial summary at submission</h2>
        <div class="table-wrap"><table><thead><tr><th>Measure</th><th>Proposed</th><th>Actual posted</th></tr></thead><tbody><tr><td>Income</td><td>{{ is_numeric($snapshot['projected_income'] ?? null) ? 'PHP '.number_format((float) $snapshot['projected_income'], 2) : 'Not recorded' }}</td><td>PHP {{ number_format((float) ($snapshot['actual_income'] ?? 0), 2) }}</td></tr><tr><td>Expenses</td><td>PHP {{ number_format((float) ($snapshot['projected_expenses'] ?? 0), 2) }}</td><td>PHP {{ number_format((float) ($snapshot['actual_expenses'] ?? 0), 2) }}</td></tr></tbody></table></div>
    @endif
    <div class="document-signatures"><div class="document-signature"><span>Prepared by · Signature</span><strong>{{ $project?->chair?->name ?? $record->author?->name }}</strong><small>Project Chair</small></div><div class="document-signature"><span>Reviewed by · Signature</span><strong>Chapter Administrator</strong><small>JCI Carmona</small></div></div>
</article>
