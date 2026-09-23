@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Tasks &amp; Milestones</h1>
        <p>Monitor task status, deadlines, assignments, and milestones.</p>
    </div>
</div>

<div class="grid-2">
    <x-card title="Task status" subtitle="Distribution of tasks by current status">
        <div class="chart-box"><canvas data-chart="doughnut" data-payload='@json($statusChart)'></canvas></div>
    </x-card>
    <x-card title="Task overview" subtitle="A quick count before opening the detailed list">
        <div class="mini-chart-grid">
            <div class="mini-kpi"><strong>{{ \App\Support\JciDemoData::countBy($tasks, 'status', 'Overdue') }}</strong><span>overdue</span></div>
            <div class="mini-kpi"><strong>{{ \App\Support\JciDemoData::countBy($tasks, 'status', 'In Progress') }}</strong><span>in progress</span></div>
            <div class="mini-kpi"><strong>{{ \App\Support\JciDemoData::countBy($tasks, 'status', 'Pending') }}</strong><span>pending</span></div>
            <div class="mini-kpi"><strong>{{ \App\Support\JciDemoData::countBy($tasks, 'status', 'Completed') }}</strong><span>completed</span></div>
        </div>
    </x-card>
</div>

<x-card title="Task list" subtitle="Open a task to view its project-scoped details">
    <div class="table-scroll">
        <table>
            <thead><tr><th>Task / Project</th><th>Assigned User</th><th>Deadline</th><th>Priority</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($tasks as $t)
                @php $p = \App\Support\JciDemoData::project($t['project']); @endphp
                <tr>
                    <td><strong>{{ $t['title'] }}</strong><span class="muted">{{ $p['title'] ?? '—' }}</span></td>
                    <td><span class="avatar avatar-sm">{{ \App\Support\JciDemoData::initials($t['assignee']) }}</span> {{ $t['assignee'] }}</td>
                    <td>{{ \App\Support\JciDemoData::date($t['deadline']) }}</td>
                    <td><x-badge :text="$t['priority']" :tone="'priority-'.strtolower($t['priority'])" /></td>
                    <td><x-badge :text="$t['status']" /></td>
                    <td>
                        <button class="icon-action" type="button" data-detail='@json($t + ["projectTitle" => $p["title"] ?? "—"])' aria-label="View task"><x-icon name="eye" /></button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>
@endsection
