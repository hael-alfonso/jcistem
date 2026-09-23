@extends('layouts.workspace')

@section('content')
@php $remain = max(0, $project['approvedBudget'] - $project['usedFunds']); @endphp
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Project Details</h1>
        <p>Monitoring view: scope, progress, tasks, budget, LOI, documents and reports.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('admin.projects') }}"><x-icon name="arrow" /> Back</a>
    </div>
</div>

<div class="project-hero">
    <div>
        <div class="area-chip">{{ $project['area'] }}</div>
        <h2>{{ $project['title'] }}</h2>
        <p>{{ $project['needs'] }}</p>
        <div class="hero-meta">
            <span><x-icon name="user" /> Chair: {{ $project['chair'] }}</span>
            <span><x-icon name="calendar" /> {{ \App\Support\JciDemoData::date($project['date']) }}</span>
            <span><x-icon name="folder" /> {{ $project['status'] }}</span>
        </div>
    </div>
    <div class="hero-status">
        <x-badge :text="$project['status']" />
        <div class="hero-progress">
            <div class="progress-wrap">
                <div class="progress"><span style="width:{{ $project['progress'] }}%"></span></div>
                <span class="progress-label">{{ $project['progress'] }}%</span>
            </div>
        </div>
    </div>
</div>

<div class="stats-grid">
    <x-stat label="Approved budget" :value="\App\Support\JciDemoData::money($project['approvedBudget'])" note="Project-level financial view" icon="wallet" />
    <x-stat label="Used funds" :value="\App\Support\JciDemoData::money($project['usedFunds'])" note="From validated records" icon="chart" />
    <x-stat label="Remaining funds" :value="\App\Support\JciDemoData::money($remain)" note="Approved allocation minus expenses" icon="wallet" />
    <x-stat label="Task count" :value="count($tasks)" note="Accessible tasks in project" icon="checklist" />
</div>

<div class="tabs" role="tablist">
    <button class="tab active" type="button" data-tab="overview">Overview</button>
    <button class="tab" type="button" data-tab="tasks">Tasks</button>
    <button class="tab" type="button" data-tab="finance">Budget &amp; Funds</button>
    <button class="tab" type="button" data-tab="loi">LOI</button>
    <button class="tab" type="button" data-tab="docs">Documents</button>
    <button class="tab" type="button" data-tab="reports">Reports</button>
</div>

<div class="tab-panel" data-panel="overview">
    <div class="grid-2">
        <x-card title="Project summary" subtitle="Controlled project-linked data">
            <div class="detail-grid">
                <div><span>Objectives</span><strong>{{ $project['objectives'] }}</strong></div>
                <div><span>Beneficiaries</span><strong>{{ $project['beneficiaries'] }}</strong></div>
                <div><span>Expected outputs</span><strong>{{ $project['outputs'] }}</strong></div>
                <div><span>Expected outcomes</span><strong>{{ $project['outcomes'] }}</strong></div>
                <div><span>Partners / stakeholders</span><strong>{{ $project['partners'] }}</strong></div>
                <div><span>Participation</span><strong>{{ $project['participants'] }}</strong></div>
            </div>
        </x-card>
        <x-card title="Project timeline" subtitle="Schedule and milestone monitoring">
            <div class="timeline">
                <div><b>Proposal</b><span>Submitted and retained in workflow history.</span></div>
                <div><b>Current stage</b><span>{{ $project['status'] }}</span></div>
                <div><b>Target date</b><span>{{ \App\Support\JciDemoData::date($project['date']) }} • {{ $project['venue'] }}</span></div>
            </div>
        </x-card>
    </div>
</div>

<div class="tab-panel" data-panel="tasks" hidden>
    <x-card title="Tasks & milestones" subtitle="Project-scoped task monitoring">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Task</th><th>Assignee</th><th>Deadline</th><th>Priority</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($tasks as $t)
                    <tr>
                        <td>{{ $t['title'] }}</td>
                        <td>{{ $t['assignee'] }}</td>
                        <td>{{ \App\Support\JciDemoData::date($t['deadline']) }}</td>
                        <td><x-badge :text="$t['priority']" :tone="'priority-'.strtolower($t['priority'])" /></td>
                        <td><x-badge :text="$t['status']" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5">No tasks for this project.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>

<div class="tab-panel" data-panel="finance" hidden>
    <x-card title="Budget & Funds" subtitle="Project-level monitoring view">
        <div class="stats-grid">
            <x-stat label="Target / proposed" :value="\App\Support\JciDemoData::money($project['targetBudget'])" icon="wallet" />
            <x-stat label="Approved allocation" :value="\App\Support\JciDemoData::money($project['approvedBudget'])" icon="wallet" />
            <x-stat label="Actual expenses" :value="\App\Support\JciDemoData::money($project['usedFunds'])" icon="chart" />
            <x-stat label="Remaining" :value="\App\Support\JciDemoData::money($remain)" icon="wallet" />
        </div>
        <div class="formula-grid">
            <div><b>Utilization</b><span>{{ number_format(($project['usedFunds'] / max(1, $project['approvedBudget'])) * 100, 1) }}%</span></div>
            <div><b>Variance / unallocated</b><span>{{ \App\Support\JciDemoData::money(max(0, $project['targetBudget'] - $project['approvedBudget'])) }}</span></div>
        </div>
    </x-card>
</div>

<div class="tab-panel" data-panel="loi" hidden>
    <x-card title="Linked LOIs" subtitle="Controlled project-to-LOI linkage">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Reference</th><th>Partner</th><th>Subject</th><th>Version</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($lois as $l)
                    <tr>
                        <td>{{ $l['ref'] }}</td>
                        <td>{{ $l['partner'] }}</td>
                        <td>{{ $l['subject'] }}</td>
                        <td>v{{ $l['version'] }}</td>
                        <td><x-badge :text="$l['status']" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5">No linked LOIs.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>

<div class="tab-panel" data-panel="docs" hidden>
    <x-card title="Project documents" subtitle="Completion evidence and source documents">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Document</th><th>Type</th><th>Version</th><th>Updated</th></tr></thead>
                <tbody>
                @forelse ($docs as $d)
                    <tr>
                        <td>{{ $d['name'] }}</td>
                        <td>{{ $d['type'] }}</td>
                        <td>{{ $d['version'] }}</td>
                        <td>{{ $d['updated'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No documents.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>

<div class="tab-panel" data-panel="reports" hidden>
    <x-card title="Project reports" subtitle="Submitted and available reports">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Report</th><th>Type</th><th>Submitted by</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($reports as $r)
                    <tr>
                        <td>{{ $r['title'] }}</td>
                        <td>{{ $r['type'] }}</td>
                        <td>{{ $r['submittedBy'] }}</td>
                        <td><x-badge :text="$r['status']" /></td>
                    </tr>
                @empty
                    <tr><td colspan="4">No reports.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection
