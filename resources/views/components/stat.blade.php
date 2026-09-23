@props(['label', 'value', 'note' => '', 'icon' => 'chart'])

<div class="stat-card">
    <div class="stat-label"><span>{{ $label }}</span><x-icon :name="$icon" class="stat-ico" /></div>
    <div class="stat-value">{{ $value }}</div>
    <div class="stat-note">{{ $note }}</div>
</div>
