@props(['name', 'label', 'type' => 'text', 'value' => '', 'required' => false])
@php($id = 'auth-'.$name)
<div class="auth-field">
    <label for="{{ $id }}">{{ $label }}</label>
    <div class="auth-input-wrap">
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" @if($type !== 'password') value="{{ old($name, $value) }}" @endif @required($required) @if($errors->has($name)) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif {{ $attributes }}>
        @if($type === 'password')
            <button type="button" class="password-toggle" data-password-toggle aria-controls="{{ $id }}" aria-label="Show {{ strtolower($label) }}" aria-pressed="false" data-field-label="{{ strtolower($label) }}" hidden>Show</button>
        @endif
    </div>
    @error($name)<p class="field-error" id="{{ $id }}-error">{{ $message }}</p>@enderror
</div>
