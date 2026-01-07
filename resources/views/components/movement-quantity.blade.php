@props([
    'quantity',
    'type',
    'showSymbol' => true
])

@php
    $colorClass = match($type) {
        'entry' => 'text-success',
        'exit', 'subtraction' => 'text-danger',
        default => 'text-dark'
    };

    $symbol = '';
    if ($showSymbol) {
        $symbol = match($type) {
            'entry' => '+',
            'exit', 'subtraction' => '-',
            default => ''
        };
    }

    $formattedQuantity = \App\Helpers\CurrencyHelper::format($quantity, 0, false);
@endphp

<span {{ $attributes->merge(['class' => 'fw-bold ' . $colorClass]) }}>
    {{ $symbol }}{{ $formattedQuantity }}
</span>
