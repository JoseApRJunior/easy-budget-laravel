@props([
    'padding' => 'p-3',
    'rounded' => 'rounded-3',
    'border' => 'border border-light-subtle',
    'background' => 'default', // 'default' uses var(--border-color), 'white' uses bg-white, or custom style
])

@php
    $styles = [];
    $classes = [];
    
    if ($padding) $classes[] = $padding;
    if ($rounded) $classes[] = $rounded;
    if ($border) $classes[] = $border;

    if ($background === 'default') {
        $styles[] = 'background-color: var(--border-color);';
    } elseif (str_starts_with($background, '#') || str_starts_with($background, 'rgb') || str_starts_with($background, 'hsl')) {
        $styles[] = "background-color: $background;";
    } elseif ($background) {
        $classes[] = $background; // e.g., 'bg-white'
    }

    $styleAttr = !empty($styles) ? implode(' ', $styles) : null;
@endphp

<div {{ $attributes->merge(['class' => implode(' ', $classes)]) }}
     @if($styleAttr) style="{{ $styleAttr }}" @endif>
    {{ $slot }}
</div>
