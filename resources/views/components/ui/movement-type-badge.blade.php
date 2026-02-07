@props(['type'])

@php
    $badgeClass = match($type) {
        'entry' => 'success',
        'exit', 'subtraction' => 'error',
        'adjustment' => 'warning',
        'reservation' => 'info',
        'cancellation' => 'secondary',
        default => 'secondary'
    };

    $typeLabel = match($type) {
        'entry' => 'Entrada',
        'exit' => 'Saída',
        'subtraction' => 'Subtração',
        'adjustment' => 'Ajuste',
        'reservation' => 'Reserva',
        'cancellation' => 'Cancel.',
        default => ucfirst($type)
    };

    $icon = match($type) {
        'entry' => 'plus-circle',
        'exit', 'subtraction' => 'dash-circle',
        'adjustment' => 'sliders',
        'reservation' => 'lock',
        'cancellation' => 'arrow-counterclockwise',
        default => 'dot'
    };
@endphp

<span {{ $attributes->merge(['class' => 'modern-badge badge-' . $badgeClass]) }}>
    <i class="bi bi-{{ $icon }} me-1"></i> {{ $typeLabel }}
</span>
