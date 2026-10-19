@props(['label', 'icon' => 'arrow'])
<button type="submit" title="{{ $label }}" {{ $attributes->class(['button', 'button-primary', 'auth-submit', 'auth-icon-button']) }}>
    <span class="visually-hidden" data-submit-label>{{ $label }}</span>
    <x-icon :name="$icon" />
</button>
