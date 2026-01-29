@props([
'name',
'label' => null,
'id' => null,
'options' => [], // Array de objetos ou array simples [value => label]
'selected' => null,
'placeholder' => 'Selecione uma opção...',
'required' => false,
'disabled' => false,
'readonly' => false,
'error' => null,
'help' => null,
'class' => '',
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

    <select 
        name="{{ $name }}" 
        id="{{ $id }}" 
        {{ $attributes->merge(['class' => 'form-select ' . ($errors->has($name) ? 'is-invalid' : '') . ' ' . $class, 'style' => 'background-color: var(--contrast-overlay);']) }}
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif>
        @if($placeholder)
        <option value="">{{ $placeholder }}</option>
        @endif

        {{ $slot }}

        @foreach($options as $key => $option)
        @php
        $value = is_object($option) ? $option->id : $key;
        $text = is_object($option) ? $option->name : $option;
        $isSelected = old($name, $selected) == $value;
        @endphp
        <option value="{{ $value }}" {{ $isSelected ? 'selected' : '' }}>
            {{ $text }}
        </option>
        @endforeach
    </select>

    @error($name)
    <div class="invalid-feedback">
        {{ $message }}
    </div>
    @enderror

    @if($help)
    <div class="form-text text-muted small">{{ $help }}</div>
    @endif
</div>
