@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Project Reports</h1>
        <p>Review submitted project and financial reports from responsible users.</p>
    </div>
</div>

<div class="grid-2">
    <x-card title="Report status" subtitle="Number of reports in each status">
        <div class="chart-box"><canvas data-chart="doughnut" data-payload='@json($statusChart)'></canvas></div>
    </x-card>
    <x-card title="Inbox summary" subtitle="Current report workload">
        <div class="mini-chart-grid">
            <div class="mini-kpi"><strong>{{ \App\Support\JciDemoData::countBy($reports, 'status', 'Submitted to Admin') }}</strong><span>submitted to Admin</span></div>
            <div class="mini-kpi"><strong>{{ \App\Support\JciDemoData::countBy($reports, 'status', 'Reviewed') }}</strong><span>reviewed</span></div>
            <div class="mini-kpi"><strong>{{ collect($reports)->where('type', 'Project Progress Report')->count() + collect($reports)->where('type', 'Project Completion Report')->count() }}</strong><span>project reports</span></div>
            <div class="mini-kpi"><strong>{{ collect($reports)->filter(fn($r) => str_contains($r['type'], 'Financial'))->count() }}</strong><span>financial reports</span></div>
        </div>
    </x-card>
</div>

<x-card title="Report inbox" subtitle="Submitted records available to Admin.">
    <div class="table-scroll">
        <table>
            <thead><tr><th>Report</th><th>Project</th><th>Submitted by</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($reports as $r)
                @php $p = \App\Support\JciDemoData::project($r['project']); @endphp
                <tr>
                    <td><strong>{{ $r['title'] }}</strong><span class="muted">{{ $r['type'] }}</span></td>
                    <td>{{ $p['title'] ?? 'Organization-wide' }}</td>
                    <td>{{ $r['submittedBy'] }}</td>
                    <td>{{ $r['submitted'] }}</td>
                    <td><x-badge :text="$r['status']" /></td>
                    <td>
                        <div class="inline-actions">
                            <button class="icon-action" type="button" data-detail='@json($r)' aria-label="View report"><x-icon name="eye" /></button>
                            <button class="icon-action" type="button" data-toast="Download prepared for demo." aria-label="Download"><x-icon name="download" /></button>
                            <button class="icon-action" type="button" data-toast="Print preview opened for demo." aria-label="Print"><x-icon name="print" /></button>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>
@endsection
