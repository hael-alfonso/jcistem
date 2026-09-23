@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Letters of Intent</h1>
        <p>Project-linked LOIs use controlled project data and version history.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="{{ route('admin.projects') }}"><x-icon name="link" /> Start from Project</a>
    </div>
</div>

<x-card title="LOI records" subtitle="Project reference, title, and purpose remain linked to the source project.">
    <div class="table-scroll">
        <table>
            <thead><tr><th>Reference</th><th>Project / Purpose</th><th>Partner</th><th>Date</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($lois as $l)
                @php $p = \App\Support\JciDemoData::project($l['project']); @endphp
                <tr>
                    <td><strong>{{ $l['ref'] }}</strong><span class="muted">v{{ $l['version'] }}</span></td>
                    <td><strong>{{ $p['title'] ?? '—' }}</strong><span class="muted">{{ $l['purpose'] }}</span></td>
                    <td>{{ $l['partner'] }}</td>
                    <td>{{ \App\Support\JciDemoData::date($l['date']) }}</td>
                    <td><x-badge :text="$l['status']" /></td>
                    <td><button class="icon-action" type="button" data-detail='@json($l + ["projectTitle" => $p["title"] ?? "—"])'><x-icon name="eye" /></button></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>

<div class="grid-3">
    <div class="info-card"><b>Auto-populated</b><span>Project reference / ID, title, description and applicable project data.</span></div>
    <div class="info-card"><b>Controlled refresh</b><span>Linked values may refresh without silently destroying LOI version history.</span></div>
    <div class="info-card"><b>Versioned</b><span>LOI versions remain linked to their project.</span></div>
</div>
@endsection
