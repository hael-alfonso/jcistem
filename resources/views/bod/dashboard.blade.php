@extends('layouts.workspace')
@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • BOARD OF DIRECTORS</div>
        <h1>BOD Dashboard</h1>
        <p>Review proposals, track chapter projects, and monitor reports that need governance attention.</p>
    </div>
</div>
<div class="stats-grid">
    <x-stat label="Waiting for review" :value="count($pending)" note="Projects in review stages" icon="clipboardCheck" />
    <x-stat label="Ongoing" :value="$ongoing" note="Approved projects in delivery" icon="folder" />
    <x-stat label="Completed" :value="$completed" note="Finished chapter projects" icon="check" />
    <x-stat label="Reports" :value="$reports" note="Submitted to Admin" icon="report" />
</div>
<x-card title="Projects for BOD review" subtitle="Open the project review list for details">
    <div class="table-scroll"><table>
        <thead><tr><th>Project</th><th>Area</th><th>Chair</th><th>Status</th><th>Progress</th></tr></thead>
        <tbody>
        @forelse ($pending as $p)
            <tr>
                <td><strong>{{ $p['title'] }}</strong></td>
                <td>{{ $p['area'] }}</td>
                <td>{{ $p['chair'] }}</td>
                <td><x-badge :text="$p['status']" /></td>
                <td>{{ $p['progress'] }}%</td>
            </tr>
        @empty
            <tr><td colspan="5">No projects currently waiting for BOD review.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</x-card>
@endsection
