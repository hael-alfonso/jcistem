@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Budget Allocation</h1>
        <p>Monitor target/proposed budget, approved allocation, category allocations, and remaining funds.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('admin.finance') }}"><x-icon name="arrow" /> Back</a>
    </div>
</div>

<x-card title="Category allocation" subtitle="Approved versus used by budget category">
    <div class="chart-box chart-box-lg"><canvas data-chart="alloc" data-payload='@json($chart)'></canvas></div>
</x-card>

<x-card title="Budget allocation monitoring" subtitle="Admin view of the current approved allocations.">
    <div class="table-scroll">
        <table>
            <thead><tr><th>Project / Category</th><th>Approved allocation</th><th>Used</th><th>Remaining</th><th>Utilization</th></tr></thead>
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
        </table>
    </div>
</x-card>
@endsection
