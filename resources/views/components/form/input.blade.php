@props([
    'type' => 'text',
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'placeholder' => '',
    'value' => '',
    'name' => '',
    'id' => null,
    'size' => 'md',
    'containerClass' => '',
])

@php
    $inputId = $id ?? $name;
    $hasError = !empty($error);
    $inputValue = $value !== '' ? $value : (old($name) ?? '');

    $sizeClasses = [
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-4 py-3 text-base',
    ];

    $baseClasses = collect([
        'w-full rounded-lg border-gray-300 shadow-sm transition-colors duration-150',
        'focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20',
        $hasError ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : '',
        $disabled ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'bg-white',
        $readonly ? 'bg-gray-50 cursor-default' : '',
        $sizeClasses[$size] ?? $sizeClasses['md'],
    ])->filter()->implode(' ');

    $containerClasses = collect([
        'mb-4',
        $containerClass,
    ])->filter()->implode(' ');
@endphp

<div class="{{ $containerClasses }}">
    @if($label)
        <label
            for="{{ $inputId }}"
            class="block text-sm font-medium text-gray-700 mb-2 {{ $required ? 'after:content-[\'*\'] after:text-red-500 after:ml-0.5' : '' }}"
        >
            {{ $label }}
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $inputId }}"
        value="{{ $inputValue }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif
        {{ $attributes->merge(['class' => $baseClasses]) }}
    >

    @if($hint)
        <p class="mt-1 text-sm text-gray-500">{{ $hint }}</p>
    @endif

    @if($hasError)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
