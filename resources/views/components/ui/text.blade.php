@props([
    'variant' => 'body', // body, small, muted, success, danger, warning, info
    'weight' => 'normal', // normal, medium, bold
    'italic' => false,
    'size' => null, // xs, sm, md, lg
])

@php
    $classes = [
        'weight-' . $weight => $weight !== 'normal',
        'fst-italic' => $italic,
    ];

    $variants = [
        'body' => 'text-dark',
        'small' => 'text-muted small',
        'muted' => 'text-muted',
        'success' => 'text-success',
        'danger' => 'text-danger',
        'warning' => 'text-warning',
        'info' => 'text-info',
    ];

    $sizes = [
        'xs' => 'font-xs',
        'sm' => 'small',
        'md' => '',
        'lg' => 'lead',
    ];

    $baseClass = $variants[$variant] ?? $variants['body'];
    if ($size && isset($sizes[$size])) {
        $baseClass .= ' ' . $sizes[$size];
    }

    if ($weight === 'medium') $baseClass .= ' fw-medium';
    if ($weight === 'bold') $baseClass .= ' fw-bold';
@endphp

<div {{ $attributes->merge(['class' => $baseClass . ($italic ? ' fst-italic' : '')]) }}>
    {{ $slot }}
</div>
