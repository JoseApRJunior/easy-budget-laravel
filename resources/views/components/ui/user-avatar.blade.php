@props([
    'name',
    'size' => 'md',
    'variant' => 'primary',
    'rounded' => true,
])

@php
    $sizes = [
        'sm' => '32px',
        'md' => '40px',
        'lg' => '48px',
    ];
    $fontSize = [
        'sm' => '0.8rem',
        'md' => '1rem',
        'lg' => '1.2rem',
    ];
    
    $currentSize = $sizes[$size] ?? $sizes['md'];
    $currentFontSize = $fontSize[$size] ?? $fontSize['md'];
    
    $initials = collect(explode(' ', $name))
        ->map(fn($n) => mb_substr($n, 0, 1))
        ->take(2)
        ->join('');
        
    $themeColor = config("theme.colors.$variant", '#0d6efd');
@endphp

<div {{ $attributes->merge([
    'class' => 'd-inline-flex align-items-center justify-content-center text-uppercase fw-bold ' . ($rounded ? 'rounded-circle' : 'rounded'),
    'style' => "background-color: {$themeColor}20; color: {$themeColor}; width: {$currentSize}; height: {$currentSize}; min-width: {$currentSize}; font-size: {$currentFontSize};"
]) }} title="{{ $name }}">
    {{ $initials }}
</div>
