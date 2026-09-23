@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Financial Reports</h1>
        <p>Admin receives and monitors submitted financial reports. Treasurer prepares the Overall Financial Report.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('admin.finance') }}"><x-icon name="arrow" /> Back</a>
    </div>
</div>

<x-card title="Submitted financial reports" subtitle="Reports available to Admin for viewing, printing, and download.">
    <div class="table-scroll">
        <table>
            <thead><tr><th>Report</th><th>Submitted by</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($reports as $r)
                <tr>
                    <td><strong>{{ $r['title'] }}</strong><span class="muted">{{ $r['type'] }}</span></td>
                    <td>{{ $r['submittedBy'] }}</td>
                    <td>{{ $r['submitted'] }}</td>
                    <td><x-badge :text="$r['status']" /></td>
                    <td>
                        <div class="inline-actions">
                            <button class="icon-action" type="button" data-detail='@json($r)'><x-icon name="eye" /></button>
                            <button class="icon-action" type="button" data-toast="Download prepared for demo."><x-icon name="download" /></button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="empty-state"><strong>No submitted financial reports are currently available.</strong></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
