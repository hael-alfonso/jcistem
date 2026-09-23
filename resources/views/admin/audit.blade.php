@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Audit Log</h1>
        <p>Trace workflow, financial, assignment and reporting changes. Critical records are archived rather than destructively deleted.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-secondary" type="button" data-toast="Audit export prepared for demo."><x-icon name="download" /> Export log</button>
    </div>
</div>

<x-card title="Audit history" subtitle="Actor, timestamp, object/project, action and remarks are retained for traceability.">
    <div class="table-scroll">
        <table>
            <thead><tr><th>Timestamp</th><th>Actor</th><th>Action</th><th>Object</th><th>Project</th><th>Remarks</th></tr></thead>
            <tbody>
            @foreach ($rows as $a)
                @php $p = $a['project'] ? \App\Support\JciDemoData::project($a['project']) : null; @endphp
                <tr>
                    <td>{{ $a['time'] }}</td>
                    <td>{{ $a['actor'] }}</td>
                    <td>{{ $a['action'] }}</td>
                    <td>{{ $a['object'] }}</td>
                    <td>{{ $p['title'] ?? '—' }}</td>
                    <td>{{ $a['remarks'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>
<div class="audit-note"><x-icon name="shield" /> Production implementation must prevent unauthorized actions and preserve review history, comments, report versions and LOI versions.</div>
@endsection
