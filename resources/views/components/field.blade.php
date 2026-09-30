@props(['name','label','type'=>'text','value'=>'','options'=>[],'required'=>false,'multiple'=>false])
@php
$key = str_replace(['[',']'], ['.',''], $name);
$key = rtrim($key,'.');
$current = old($key,$value);
$id = 'field-'.preg_replace('/[^a-zA-Z0-9_-]/','-',$name).'-'.uniqid();
@endphp
<label class="field" for="{{ $id }}"><span>{{ $label }} @if($required)<em aria-hidden="true">*</em>@endif</span>
@if($type==='textarea')
<textarea id="{{ $id }}" name="{{ $name }}" rows="4" @required($required) {{ $attributes }}>{{ $current }}</textarea>
@elseif($type==='select')
<select id="{{ $id }}" name="{{ $name }}{{ $multiple ? '[]' : '' }}" @required($required) @if($multiple) multiple @endif {{ $attributes }}>
@if(!$multiple)<option value="">Select {{ strtolower($label) }}</option>@endif
@foreach($options as $optionValue => $optionLabel)<option value="{{ $optionValue }}" @selected($multiple ? in_array($optionValue,(array)$current) : (string)$current===(string)$optionValue)>{{ $optionLabel }}</option>@endforeach
</select>
@else
<input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" @if(!in_array($type,['password','file'])) value="{{ $current }}" @endif @required($required) {{ $attributes }}>
@endif
</label>

