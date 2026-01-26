@props([
    'size' => null,
    'cols' => null,
    'sm' => null,
    'md' => null,
    'lg' => null,
    'xl' => null,
])

@php
    $classes = [];

    if ($size) {
        $classes[] = $size;
    }

    if ($cols) {
        $classes[] = $cols === true || $cols === 'true' ? 'col' : "col-{$cols}";
    }

    if ($sm) {
        $classes[] = $sm === true || $sm === 'true' ? 'col-sm' : "col-sm-{$sm}";
    }

    if ($md) {
        $classes[] = $md === true || $md === 'true' ? 'col-md' : "col-md-{$md}";
    }

    if ($lg) {
        $classes[] = $lg === true || $lg === 'true' ? 'col-lg' : "col-lg-{$lg}";
    }

    if ($xl) {
        $classes[] = $xl === true || $xl === 'true' ? 'col-xl' : "col-xl-{$xl}";
    }

    // Se nenhuma classe de coluna foi definida via props (size ou breakpoints), 
    // e nenhuma classe 'col' foi passada via atributos, usamos col-12 como fallback.
    $hasColClass = str_contains($attributes->get('class', ''), 'col-') || str_contains($attributes->get('class', ''), 'col ');
    
    if (empty($classes) && !$hasColClass) {
        $classes[] = 'col-12';
    }

    $finalClass = implode(' ', $classes);
@endphp

<div {{ $attributes->merge(['class' => $finalClass]) }}>
    {{ $slot }}
</div>
