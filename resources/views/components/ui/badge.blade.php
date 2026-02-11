@props([
    'variant' => 'light', // primary, success, info, warning, danger, secondary, light, dark
    'label' => null,
    'icon' => null,
    'outline' => false,
    'pill' => false,
    'solid' => false, // Novo prop para estilo sólido
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
    
    // Define cor do texto para modo sólido
    $textColor = in_array($variant, ['warning', 'info', 'light']) ? '#212529' : '#ffffff';

    if ($solid) {
        // Estilo sólido: fundo cor cheia, texto contrastante
        $badgeStyle = "background-color: {$color}; color: {$textColor}; border: 1px solid {$color};";
    } elseif ($outline) {
        // Estilo outline
        $badgeStyle = "background-color: transparent; color: {$color}; border: 1px solid {$color};";
    } elseif ($variant === 'light') {
        // Estilo light específico
        $badgeStyle = "background-color: var(--hover-bg); color: #64748b; border: 1px solid var(--form-border);";
    } else {
        // Estilo moderno padrão (suave): fundo 15% opacidade
        $badgeStyle = "background-color: {$color}15; color: {$color}; border: 1px solid {$color}30;";
    }

    $classes = 'modern-badge';
    if ($pill) $classes .= ' rounded-pill';
    if ($solid) $classes .= ' shadow-sm'; // Adiciona sombra suave no modo sólido
@endphp

<span {{ $attributes->merge(['class' => $classes, 'style' => $badgeStyle]) }}>
    @if($icon)
        <i class="bi bi-{{ $icon }} me-1"></i>
    @endif
    {{ $label ?? $slot }}
</span>
