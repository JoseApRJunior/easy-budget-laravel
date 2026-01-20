@props([
    'icon',
    'variant' => 'primary',
    'size' => 'md', // sm, md, lg
    'rounded' => true,
])

@php
    $themeColor = config("theme.colors.$variant", config('theme.colors.primary'));
    
    $sizes = [
        'sm' => ['box' => '32px', 'icon' => '0.9rem', 'padding' => '2'],
        'md' => ['box' => '48px', 'icon' => '1.25rem', 'padding' => '3'],
        'lg' => ['box' => '64px', 'icon' => '1.75rem', 'padding' => '4'],
    ];
    
    $currentSize = $sizes[$size] ?? $sizes['md'];
@endphp

<div {{ $attributes->merge([
    'class' => 'd-inline-flex align-items-center justify-content-center ' . ($rounded ? 'rounded' : ''),
    'style' => "background-color: {$themeColor}1a; width: {$currentSize['box']}; height: {$currentSize['box']}; min-width: {$currentSize['box']};"
]) }}>
    <i class="bi bi-{{ $icon }}" style="color: {{ $themeColor }}; font-size: {{ $currentSize['icon'] }};"></i>
</div>
