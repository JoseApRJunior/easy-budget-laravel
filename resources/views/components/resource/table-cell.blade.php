@props([
    'header' => false,
    'align' => 'left',
])

@php
    $tag = $header ? 'th' : 'td';
    $alignmentClass = match($align) {
        'center' => 'text-center',
        'right' => 'text-end',
        default => '',
    };
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $alignmentClass]) }}>
    {{ $slot }}
</{{ $tag }}>
