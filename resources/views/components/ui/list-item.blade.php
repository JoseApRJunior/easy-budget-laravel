@props([
    'hover' => true,
    'padding' => '2',
    'rounded' => true,
    'justify' => 'between',
    'align' => 'center',
    'gap' => '2',
])

@php
    $classes = "d-flex align-items-{$align} justify-content-{$justify} gap-{$gap}";
    if ($padding) $classes .= " p-{$padding}";
    if ($rounded) $classes .= " rounded";
    if ($hover) $classes .= " transition-all hover-bg";
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
