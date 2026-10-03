@php
    $countSegments = collect($segments)->filter(fn ($segment) => $segment['value'] > 0)->values();
    $countMaximum = max(1, $countSegments->max('value'));
    $countColors = ['#176a9b', '#267344', '#7052a8', '#996014', '#ae462f'];
@endphp
<div class="count-chart" aria-label="Counts by category">
    @foreach($countSegments as $segment)
        <div class="count-chart-row">
            <div><span>{{ $segment['label'] }}</span><strong>{{ number_format($segment['value']) }}</strong></div>
            <div class="count-chart-track" role="img" aria-label="{{ $segment['label'] }}: {{ $segment['value'] }}">
                <span style="width: {{ 100 * $segment['value'] / $countMaximum }}%; background-color: {{ $segment['color'] ?? $countColors[$loop->index % count($countColors)] }}"></span>
            </div>
        </div>
    @endforeach
</div>
