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

<div @if($col) class="{{ $col }} {{ $alignmentClass }}" @else {{ $attributes->merge(['class' => 'mb-2']) }} @endif style="min-width: 0;">
    <small class="text-muted d-block text-uppercase mb-1 small fw-bold text-truncate" title="{{ $label }}">
        @if($icon)
        <i class="bi bi-{{ $icon }} me-1"></i>
        @endif
        {{ $label }}
    </small>
    <div class="text-dark fw-semibold" style="font-size: 0.9rem; overflow-wrap: break-word;">
        @if($value)
        {{ $value }}
        @else
        {{ $slot }}
        @endif
    </div>
</div>
