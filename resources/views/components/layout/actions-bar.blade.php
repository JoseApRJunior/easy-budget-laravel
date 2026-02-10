@props([
    'alignment' => 'end', // start, center, end, between
    'mb' => 4,
    'gap' => 2
])

@php
    $justifyClass = match($alignment) {
        'center' => 'justify-content-md-center',
        'start' => 'justify-content-md-start',
        'between' => 'justify-content-between',
        default => 'justify-content-end',
    };
@endphp

<div {{ $attributes->merge(['class' => "d-flex flex-column flex-md-row $justifyClass align-items-stretch align-items-md-center gap-$gap mb-$mb w-100"]) }}>
    {{ $slot }}
</div>
