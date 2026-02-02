@props([
'label',
'value' => null,
'col' => null, // e.g., 'col-6'
'align' => 'start', // start, end
'icon' => null,
])

@php
$alignmentClass = match($align) {
'end' => 'text-end',
'center' => 'text-center',
default => 'text-start',
};
@endphp

<div @if($col) class="{{ $col }} {{ $alignmentClass }}" @else {{ $attributes->merge(['class' => 'mb-2']) }} @endif>
    <small class="text-muted d-block text-uppercase mb-1 small fw-bold">
        @if($icon)
        <i class="bi bi-{{ $icon }} me-1"></i>
        @endif
        {{ $label }}
    </small>
    <div class="text-dark fw-semibold text-truncate">
        @if($value)
        {{ $value }}
        @else
        {{ $slot }}
        @endif
    </div>
</div>
