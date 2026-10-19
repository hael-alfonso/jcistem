@props(['label'])
<button type="submit" {{ $attributes->class(['button', 'button-primary', 'auth-submit']) }}>
    <span data-submit-label>{{ $label }}</span>
</button>
