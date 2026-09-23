@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Members &amp; Accounts</h1>
        <p>Maintain the JCI Carmona member directory and user accounts.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="{{ route('admin.members.registration') }}"><x-icon name="userPlus" /> Member Registration</a>
    </div>
</div>

<div class="grid-2">
    <x-card title="Roles in the chapter" subtitle="Directory composition by assigned role">
        <div class="chart-box"><canvas data-chart="doughnut" data-payload='@json($chart)'></canvas></div>
    </x-card>
    <x-card title="Directory snapshot" subtitle="Current account counts">
        <div class="mini-chart-grid">
            <div class="mini-kpi"><strong>{{ count($users) }}</strong><span>members</span></div>
            <div class="mini-kpi"><strong>{{ \App\Support\JciDemoData::countBy($users, 'status', 'Active') }}</strong><span>active</span></div>
            <div class="mini-kpi"><strong>{{ \App\Support\JciDemoData::countBy($users, 'status', 'Pending') }}</strong><span>pending</span></div>
            <div class="mini-kpi"><strong>{{ \App\Support\JciDemoData::countBy($users, 'role', 'Admin') }}</strong><span>admins</span></div>
        </div>
    </x-card>
</div>

<x-card title="Member directory" subtitle="Open a member to view account details.">
    <div class="table-scroll">
        <table>
            <thead><tr><th>Member</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr>
                    <td>
                        <div class="member-cell">
                            <span class="avatar avatar-md">{{ \App\Support\JciDemoData::initials($u['name']) }}</span>
                            <div><strong>{{ $u['name'] }}</strong><span>{{ $u['memberNo'] }}</span></div>
                        </div>
                    </td>
                    <td>{{ $u['email'] }}</td>
                    <td>{{ $u['role'] }}</td>
                    <td><x-badge :text="$u['status']" /></td>
                    <td>{{ \App\Support\JciDemoData::date($u['joined']) }}</td>
                    <td><button class="icon-action" type="button" data-detail='@json($u)'><x-icon name="eye" /></button></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>
@endsection
