@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'checked' => false,
    'value' => '1',
    'name' => '',
    'id' => null,
    'size' => 'md',
    'containerClass' => '',
])

@php
    $inputId = $id ?? $name;
    $hasError = !empty($error);
    $isChecked = $checked || old($name) == $value;

    $sizeClasses = [
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        'lg' => 'w-6 h-6',
    ];

    $containerClasses = collect([
        'mb-4',
        $containerClass,
    ])->filter()->implode(' ');
@endphp

<div class="{{ $containerClasses }}">
    <div class="flex items-start">
        <div class="flex items-center h-5">
            <input
                type="checkbox"
                name="{{ $name }}"
                id="{{ $inputId }}"
                value="{{ $value }}"
                @if($isChecked) checked @endif
                @if($required) required @endif
                @if($disabled) disabled @endif
                {{ $attributes->merge([
                    'class' => 'rounded border-gray-300 text-blue-600 focus:ring-blue-500 ' . ($sizeClasses[$size] ?? $sizeClasses['md'])
                ]) }}
            >
        </div>

        @if($label)
            <div class="ml-3">
                <label
                    for="{{ $inputId }}"
                    class="text-sm font-medium text-gray-700 {{ $required ? 'after:content-[\'*\'] after:text-red-500 after:ml-0.5' : '' }}"
                >
                    {{ $label }}
                </label>

                @if($hint)
                    <p class="mt-1 text-sm text-gray-500">{{ $hint }}</p>
                @endif
            </div>
        @endif
    </div>

    @if($hasError)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
