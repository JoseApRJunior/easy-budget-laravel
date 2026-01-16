@props([
    'name',
    'label' => null,
    'id' => null,
    'value' => '',
    'rows' => 3,
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'help' => null,
])

@php
    $id = $id ?? $name;
@endphp

<div class="form-group">
    @if($label)
        <label for="{{ $id }}" class="form-label fw-bold small text-muted text-uppercase">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <textarea 
        name="{{ $name }}" 
        id="{{ $id }}" 
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'form-control ' . ($errors->has($name) ? 'is-invalid' : '')]) }}
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif
    >{{ old($name, $value ?? $slot) }}</textarea>

    @error($name)
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror

    @if($help)
        <div class="form-text text-muted small">{{ $help }}</div>
    @endif
</div>
