@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>My Account</h1>
        <p>Personal profile, activity log and simple system settings.</p>
    </div>
</div>

<x-card title="Profile" subtitle="Admin account">
    <div class="profile-header">
        <span class="avatar avatar-lg">{{ \App\Support\JciDemoData::initials($currentUser) }}</span>
        <div>
            <h3>{{ $currentUser }}</h3>
            <span>Admin • {{ \App\Support\JciDemoData::config()['location'] }}</span>
        </div>
    </div>
    <div class="detail-grid compact">
        <div><span>Email</span><strong>admin@jcicarmona.org</strong></div>
        <div><span>Role</span><strong>Admin</strong></div>
        <div><span>Member No.</span><strong>JCI-001</strong></div>
        <div><span>Account status</span><strong>Active</strong></div>
    </div>
</x-card>

<div class="grid-2">
    <x-card title="Activity Log" subtitle="Recent actions performed through this account">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Time</th><th>Action</th><th>Record</th><th>Remarks</th></tr></thead>
                <tbody>
                @forelse ($activity as $a)
                    <tr>
                        <td>{{ $a['time'] }}</td>
                        <td>{{ $a['action'] }}</td>
                        <td>{{ $a['object'] }}</td>
                        <td>{{ $a['remarks'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No recent activity.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
    <x-card title="Settings" subtitle="Simple workspace preferences">
        <div class="settings-list">
            <div><span>Notifications</span><label class="switch"><input type="checkbox" checked><i></i></label></div>
            <div><span>Compact tables</span><label class="switch"><input type="checkbox"><i></i></label></div>
            <div><span>Show help text</span><label class="switch"><input type="checkbox" checked><i></i></label></div>
        </div>
    </x-card>
</div>
@endsection
