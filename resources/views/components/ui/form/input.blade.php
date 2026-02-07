@props([
'name',
'label' => null,
'type' => 'text',
'id' => null,
'value' => '',
'placeholder' => '',
'required' => false,
'disabled' => false,
'readonly' => false,
'error' => null,
'help' => null,
'wrapperClass' => 'form-group',
])

@php
$id = $id ?? $name;
@endphp

<div class="{{ $wrapperClass }}">
    @if($label)
    <label for="{{ $id }}" class="form-label fw-bold small text-muted text-uppercase">
        {{ $label }}
        @if($required)
        <span class="text-danger">*</span>
        @endif
    </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'form-control ' . ($errors->has($name) ? 'is-invalid' : ''), 'style' => 'background-color: var(--form-input-bg);']) }}
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif>

    @error($name)
    <div class="invalid-feedback">
        {{ $message }}
    </div>
    @enderror

    @if($help)
    <div class="form-text text-muted small">{{ $help }}</div>
    @endif
</div>
