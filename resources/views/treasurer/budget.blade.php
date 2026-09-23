@extends('layouts.workspace')
@section('content')
<div class="page-head"><div class="page-copy"><div class="eyebrow">TREASURER</div><h1>Budget Allocation</h1><p>Record and maintain approved project allocations. Admin can monitor these values but cannot post them.</p></div></div>
<x-card title="Category allocations" subtitle="Approved versus used by budget category">
    <div class="table-scroll"><table>
        <thead><tr><th>Project / Category</th><th>Allocated</th><th>Used</th><th>Remaining</th><th>Utilization</th></tr></thead>
        <tbody>
        @foreach ($allocations as $a)
            @php $p = \App\Support\JciDemoData::project($a['project']); @endphp
            <tr>
                <td><strong>{{ $p['title'] ?? '—' }}</strong><span class="muted">{{ $a['category'] }}</span></td>
                <td>{{ \App\Support\JciDemoData::money($a['allocated']) }}</td>
                <td>{{ \App\Support\JciDemoData::money($a['used']) }}</td>
                <td>{{ \App\Support\JciDemoData::money($a['allocated'] - $a['used']) }}</td>
                <td>{{ number_format(($a['used'] / max(1, $a['allocated'])) * 100, 1) }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</x-card>
@endsection
