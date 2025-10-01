@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'placeholder' => 'Selecione uma opção...',
    'value' => '',
    'name' => '',
    'id' => null,
    'options' => [],
    'size' => 'md',
    'multiple' => false,
    'searchable' => false,
    'containerClass' => '',
])

@php
    $inputId = $id ?? $name;
    $hasError = !empty($error);
    $inputValue = $value !== '' ? $value : old($name);

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
        $sizeClasses[$size] ?? $sizeClasses['md'],
    ])->filter()->implode(' ');

    $containerClasses = collect([
        'mb-4',
        $containerClass,
    ])->filter()->implode(' ');
@endphp

<div class="{{ $containerClasses }}" x-data="{ open: false, search: '', selected: @js($inputValue) }">
    @if($label)
        <label
            for="{{ $inputId }}"
            class="block text-sm font-medium text-gray-700 mb-2 {{ $required ? 'after:content-[\'*\'] after:text-red-500 after:ml-0.5' : '' }}"
        >
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        @if($searchable)
            <!-- Searchable Select -->
            <div>
                <button
                    type="button"
                    @click="open = !open"
                    :class="{ 'ring-2 ring-blue-500 border-blue-500': open }"
                    class="{{ $baseClasses }} text-left flex items-center justify-between"
                >
                    <span x-text="selected ? @js(collect($options)->where('value', $inputValue)->first()['label'] ?? $placeholder) : '{{ $placeholder }}'"></span>
                    <i class="bi bi-chevron-down transition-transform" :class="{ 'rotate-180': open }"></i>
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto"
                    @click.away="open = false"
                >
                    <div class="p-2">
                        <input
                            type="text"
                            x-model="search"
                            placeholder="Buscar..."
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div class="max-h-48 overflow-auto">
                        @foreach($options as $option)
                            <button
                                type="button"
                                @click="selected = '{{ $option['value'] }}'; open = false"
                                :class="{ 'bg-blue-50 text-blue-700': selected === '{{ $option['value'] }}' }"
                                class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 transition-colors"
                            >
                                {{ $option['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <!-- Regular Select -->
            <select
                name="{{ $name }}"
                id="{{ $inputId }}"
                @if($required) required @endif
                @if($disabled) disabled @endif
                @if($multiple) multiple @endif
                {{ $attributes->merge(['class' => $baseClasses]) }}
            >
                @if($placeholder && !$multiple)
                    <option value="">{{ $placeholder }}</option>
                @endif

                @foreach($options as $option)
                    <option
                        value="{{ $option['value'] }}"
                        @if($option['value'] == $inputValue) selected @endif
                    >
                        {{ $option['label'] }}
                    </option>
                @endforeach
            </select>
        @endif
    </div>

    @if($hint)
        <p class="mt-1 text-sm text-gray-500">{{ $hint }}</p>
    @endif

    @if($hasError)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif

    @if($searchable)
        <input type="hidden" name="{{ $name }}" :value="selected">
    @endif
</div>
