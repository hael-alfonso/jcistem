@props(['activity' => []])

@php $user = auth()->user(); @endphp
<x-card title="Profile" subtitle="Signed-in account">
    <div class="profile-header">
        <span class="avatar avatar-lg">{{ \App\Support\JciDemoData::initials($user->name) }}</span>
        <div>
            <h3>{{ $user->name }}</h3>
            <span>{{ \App\Support\WorkspaceNav::label($user->role) }} • {{ $user->member_no }}</span>
        </div>
    </div>
    <div class="detail-grid compact">
        <div><span>Email</span><strong>{{ $user->email }}</strong></div>
        <div><span>Role</span><strong>{{ \App\Support\WorkspaceNav::label($user->role) }}</strong></div>
        <div><span>Member No.</span><strong>{{ $user->member_no }}</strong></div>
        <div><span>Account status</span><strong>Active</strong></div>
    </div>
</x-card>
@if (count($activity))
<x-card title="Activity Log" subtitle="Recent actions from this account">
    <div class="table-scroll">
        <table>
            <thead><tr><th>Time</th><th>Action</th><th>Record</th><th>Remarks</th></tr></thead>
            <tbody>
            @foreach ($activity as $a)
                <tr>
                    <td>{{ $a['time'] }}</td>
                    <td>{{ $a['action'] }}</td>
                    <td>{{ $a['object'] }}</td>
                    <td>{{ $a['remarks'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>
@endif
