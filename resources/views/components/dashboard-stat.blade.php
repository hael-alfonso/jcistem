@props(['label', 'value', 'note', 'icon', 'tone' => 'projects', 'money' => false, 'href' => null])
<{{ $href ? 'a' : 'div' }} {{ $attributes->class(['stat-card', 'bank-stat', 'stat-'.$tone]) }} @if($href) href="{{ $href }}" @endif>
    <span class="stat-watermark" aria-hidden="true"><x-icon :name="$icon"/></span>
    <span class="stat-icon"><x-icon :name="$icon"/></span>
    <div class="stat-content">
        <span class="stat-label">{{ $label }}</span>
        <strong @class(['money' => $money])>{{ $value }}</strong>
        <small>{{ $note }}</small>
    </div>
</{{ $href ? 'a' : 'div' }}>
