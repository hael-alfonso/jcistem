@extends('layouts.workspace')
@section('content')
<div class="page-head"><div class="page-copy"><div class="eyebrow">BOARD OF DIRECTORS</div><h1>Reports</h1><p>Chapter reports available for BOD review.</p></div></div>
<x-card title="Report inbox" subtitle="Submitted and draft reports">
    <div class="table-scroll"><table>
        <thead><tr><th>Report</th><th>Type</th><th>Submitted by</th><th>Status</th></tr></thead>
        <tbody>
        @foreach ($reports as $r)
            <tr>
                <td><strong>{{ $r['title'] }}</strong></td>
                <td>{{ $r['type'] }}</td>
                <td>{{ $r['submittedBy'] }}</td>
                <td><x-badge :text="$r['status']" /></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</x-card>
@endsection
