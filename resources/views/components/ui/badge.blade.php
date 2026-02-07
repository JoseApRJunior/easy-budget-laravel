@props([
    'variant' => 'light', // primary, success, info, warning, danger, secondary, light, dark
    'label' => null,
    'icon' => null,
    'outline' => false,
    'pill' => false,
])

@php
    $variantColors = [
        'primary' => '#0d6efd',
        'success' => '#198754',
        'info' => '#0dcaf0',
        'warning' => '#ffc107',
        'danger' => '#dc3545',
        'secondary' => '#6c757d',
        'light' => '#f8f9fa',
        'dark' => '#212529',
    ];

    $color = $variantColors[$variant] ?? $variantColors['secondary'];
    
    // Estilo moderno: fundo suave com cor de texto forte
    $badgeStyle = $variant === 'light' 
        ? "background-color: var(--hover-bg); color: #64748b; border: 1px solid var(--form-border);"
        : "background-color: {$color}15; color: {$color}; border: 1px solid {$color}30;";
    
    if ($outline) {
        $badgeStyle = "background-color: transparent; color: {$color}; border: 1px solid {$color};";
    }

    $classes = 'modern-badge';
    if ($pill) $classes .= ' rounded-pill';
@endphp

<span {{ $attributes->merge(['class' => $classes, 'style' => $badgeStyle]) }}>
    @if($icon)
        <i class="bi bi-{{ $icon }} me-1"></i>
    @endif
    {{ $label ?? $slot }}
</span>
