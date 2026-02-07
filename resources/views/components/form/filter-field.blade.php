@props([
    'type' => 'text',            // Tipo do campo: text, select, date, etc.
    'name',                      // Nome do campo
    'label',                     // Label do campo
    'placeholder' => '',         // Placeholder
    'value' => null,             // Valor atual
    'options' => [],             // Opções para select
    'col' => 'col-md-4',         // Classe de coluna Bootstrap
    'required' => false,         // Campo obrigatório
    'mask' => null,              // Máscara de input (ex: 00/00/0000)
    'filters' => [],             // Array de filtros (para pegar valor automaticamente)
])

@php
    // Determinar valor do campo
    $fieldValue = $value ?? old($name, $filters[$name] ?? '');
@endphp

<div class="{{ $col }}">
    <div class="form-group">
        <label for="{{ $name }}" class="form-label small fw-bold text-muted text-uppercase">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>

        @if($type === 'select')
            {{-- Campo Select --}}
            <select
                class="form-select tom-select"
                id="{{ $name }}"
                name="{{ $name }}"
                {{ $required ? 'required' : '' }}
                {{ $attributes }}
            >
                @foreach($options as $optionValue => $optionLabel)
                    <option
                        value="{{ $optionValue }}"
                        {{ $fieldValue == $optionValue ? 'selected' : '' }}
                    >
                        {{ $optionLabel }}
                    </option>
                @endforeach
                {{ $slot }}
            </select>

        @elseif($type === 'textarea')
            {{-- Textarea --}}
            <textarea
                class="form-control"
                id="{{ $name }}"
                name="{{ $name }}"
                placeholder="{{ $placeholder }}"
                {{ $required ? 'required' : '' }}
                {{ $attributes }}
            >{{ $fieldValue }}</textarea>

        @elseif($type === 'date')
            {{-- Campo de Data (Padrão type="date") --}}
            <input
                type="date"
                class="form-control"
                id="{{ $name }}"
                name="{{ $name }}"
                value="{{ \App\Helpers\DateHelper::formatDateOrDefault($fieldValue, 'Y-m-d', $fieldValue) }}"
                {{ $required ? 'required' : '' }}
                {{ $attributes }}
            >

        @else
            {{-- Campo de Texto padrão --}}
            <input
                type="{{ $type }}"
                class="form-control"
                id="{{ $name }}"
                name="{{ $name }}"
                value="{{ $fieldValue }}"
                placeholder="{{ $placeholder }}"
                @if($mask)
                    data-mask="{{ $mask }}"
                @endif
                {{ $required ? 'required' : '' }}
                {{ $attributes }}
            >
        @endif
    </div>
</div>
