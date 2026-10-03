@php
    $peak = (float) $charts['months']->max('amount');
    $magnitude = $peak > 0 ? pow(10, floor(log10($peak))) : 1;
    $ceiling = $peak > 0 ? max(1, ceil($peak / $magnitude) * $magnitude) : 1;
    $axisDivisor = $ceiling >= 1000000 ? 1000000 : ($ceiling >= 1000 ? 1000 : 1);
    $axisUnit = $axisDivisor === 1000000 ? 'PHP (millions)' : ($axisDivisor === 1000 ? 'PHP (thousands)' : 'PHP');
@endphp
<section class="panel chart-panel expense-trend-panel">
    <div class="panel-heading"><div><h2>Expenses over time</h2><p>{{ $charts['months']->first()['label'] }} – {{ $charts['months']->last()['label'] }} · current month to date</p></div></div>
    @if($peak > 0)
        <div class="expense-trend">
            <svg viewBox="0 0 640 235" role="img" aria-label="Monthly posted project expenses in PHP. Exact amounts are listed below.">
                @foreach(range(0, 4) as $tick)
                    @php $y = 185 - $tick * 40; $amount = $ceiling * $tick / 4; @endphp
                    <line x1="88" y1="{{ $y }}" x2="628" y2="{{ $y }}" class="trend-grid"/>
                    <text x="78" y="{{ $y + 4 }}" text-anchor="end" class="trend-axis">{{ number_format($amount / $axisDivisor, $axisDivisor > 1 ? 3 : 2) }}</text>
                @endforeach
                <text x="88" y="14" class="trend-axis">{{ $axisUnit }}</text>
                @foreach($charts['months'] as $month)
                    @php $x = 112 + $loop->index * 88; $height = 160 * $month['amount'] / $ceiling; @endphp
                    <rect x="{{ $x }}" y="{{ 185 - $height }}" width="36" height="{{ $height }}" rx="3" class="trend-bar"><title>{{ $month['label'] }}: PHP {{ number_format($month['amount'], 2) }}</title></rect>
                    @if($month['amount'] == 0)<text x="{{ $x + 18 }}" y="176" text-anchor="middle" class="trend-axis">0</text>@endif
                    <text x="{{ $x + 18 }}" y="207" text-anchor="middle" class="trend-axis">{{ $month['label'] }}</text>
                @endforeach
            </svg>
        </div>
    @else
        <div class="empty-state compact"><h3>No posted expenses in this period</h3><p>The last six months have no recorded project spending.</p></div>
    @endif
    <dl class="expense-month-values" aria-label="Exact monthly expenses">
        @foreach($charts['months'] as $month)<div><dt>{{ $month['label'] }}</dt><dd>PHP {{ number_format($month['amount'], 2) }}</dd></div>@endforeach
    </dl>
    <p class="chart-note">Posted debit entries by transaction date, through {{ today()->format('M d, Y') }}. Months without spending show zero.</p>
</section>
