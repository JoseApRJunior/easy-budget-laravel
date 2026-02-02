@props([
    'name',
    'label' => null,
    'type' => 'text',
    'id' => null,
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'icon' => null,
    'prepend' => null,
    'append' => null,
    'help' => null,
])

@php
    $id = $id ?? $name;
@endphp

<div class="mb-4">
    @if($label)
        <label for="{{ $id }}" class="form-label fw-bold small text-muted text-uppercase">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <div class="input-group">
        @if($icon)
            <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-{{ $icon }} text-muted"></i>
            </span>
        @endif

        @if($prepend)
            {{ $prepend }}
        @endif

        <input 
            type="{{ $type }}" 
            name="{{ $name }}" 
            id="{{ $id }}" 
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->merge(['class' => 'form-control ' . ($errors->has($name) ? 'is-invalid' : ''), 'style' => 'background-color: var(--form-input-bg);']) }}
            @if($required) required @endif
        >

        @if($append)
            {{ $append }}
        @endif
    </div>

    @error($name)
        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>
    @enderror

    @if($help)
        <div class="form-text text-muted small">{{ $help }}</div>
    @endif
</div>
