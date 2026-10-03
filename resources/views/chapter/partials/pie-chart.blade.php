@php
    $pieItems = collect($segments)->filter(fn ($item) => (float) $item['value'] > 0)->values();
    $pieTotal = (float) $pieItems->sum('value');
    $pieColors = ['#0876a8', '#36b3af', '#6988d0', '#e4a35e', '#936fc1', '#70a963', '#d4788b', '#4b9ab2'];
    $circumference = 2 * pi() * 40;
    $distance = 0;
    $pieSlices = [];
    $pieLegend = [];
    foreach ($pieItems as $index => $item) {
        $value = (float) $item['value'];
        $length = $pieTotal > 0 ? $circumference * $value / $pieTotal : 0;
        $color = $item['color'] ?? $pieColors[$index % count($pieColors)];
        $pieSlices[] = ['length' => $length, 'offset' => $distance, 'color' => $color];
        $pieLegend[] = ['label' => $item['label'], 'value' => $value, 'percent' => 100 * $value / $pieTotal, 'color' => $color];
        $distance += $length;
    }

@endphp
<div class="pie-layout">
    <div class="pie-disc" role="img" aria-label="{{ $pieItems->map(fn ($item) => $item['label'].': '.$item['value'])->join(', ') }}">
        <svg class="pie-svg" viewBox="0 0 100 100" aria-hidden="true">
            <circle cx="50" cy="50" r="40" fill="none" stroke="#eaf1f4" stroke-width="18"/>
            @foreach($pieSlices as $slice)
                <circle class="pie-slice" cx="50" cy="50" r="40" fill="none" stroke="{{ $slice['color'] }}" stroke-width="18"
                    stroke-dasharray="{{ number_format($slice['length'], 4, '.', '') }} {{ number_format($circumference - $slice['length'], 4, '.', '') }}"
                    stroke-dashoffset="{{ number_format(-$slice['offset'], 4, '.', '') }}" transform="rotate(-90 50 50)"
                    style="--slice-length: {{ number_format($slice['length'], 4, '.', '') }}; --slice-rest: {{ number_format($circumference - $slice['length'], 4, '.', '') }}; --slice-delay: {{ number_format($slice['offset'] / $circumference * 0.7, 3, '.', '') }}s"/>
            @endforeach
        </svg>
        <div class="pie-hole"><strong>{{ $center }}</strong><span>{{ $caption }}</span></div>
    </div>
    <ul class="pie-legend" tabindex="0" aria-label="Chart categories">
        @foreach($pieLegend as $item)
            <li><span class="pie-swatch" style="background: {{ $item['color'] }}"></span><span class="pie-label">{{ $item['label'] }}</span><strong>{{ $format === 'money' ? 'PHP '.number_format($item['value'], 2) : number_format($item['value']) }}</strong><small>{{ number_format($item['percent'], 1) }}%</small></li>
        @endforeach
    </ul>
</div>
