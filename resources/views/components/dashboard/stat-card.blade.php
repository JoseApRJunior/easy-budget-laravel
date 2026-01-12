@props([
    'title',
    'value',
    'description' => null,
    'icon' => null,
    'variant' => 'primary', // primary, success, info, warning, danger, secondary
    'gradient' => config('theme.dashboard.stat_card.gradient', true),
    'isCustom' => false, // Para col-xl-5-custom
    'col' => null // Permite sobrescrever as classes de coluna
])

@php
    $color = config("theme.colors.$variant");
    $isLightVariant = in_array($variant, ['primary', 'success', 'info', 'warning', 'danger', 'secondary', 'light']);
    $textColor = $gradient ? ($isLightVariant ? 'dark' : 'white') : 'dark';
@endphp

<div class="{{ $col ?? ($isCustom ? 'col-12 col-md-6 col-xl-5-custom' : 'col-12 col-md-6 col-lg-3') }}">
    <div @class([
        'card border-0 shadow-sm h-100 stat-card-modern',
        "bg-$variant bg-gradient" => $gradient,
        "text-$textColor" => $gradient,
    ]) @style([
        "--stat-card-color: $color;" => $color,
        "--stat-card-text-primary: " . config('theme.colors.text', '#1e293b') . ";",
        "--stat-card-text-secondary: " . config('theme.colors.secondary', '#94a3b8') . ";",
    ])>
        <div class="card-body p-3 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                @if($icon)
                    <div @class([
                        'avatar-circle me-2',
                        'bg-white bg-opacity-20' => $gradient && !$isLightVariant,
                        'bg-black bg-opacity-10' => $gradient && $isLightVariant,
                    ]) @style([
                        "background-color: var(--stat-card-color) !important;" => !$gradient,
                        "opacity: 0.1;" => !$gradient,
                        "width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 50%;"
                    ])>
                        <i @class([
                            'bi bi-' . $icon,
                            'text-white' => $gradient && !$isLightVariant,
                            'text-dark' => $gradient && $isLightVariant,
                        ]) @style([
                            "color: var(--stat-card-color) !important;" => !$gradient,
                            "font-size: 0.9rem;"
                        ])></i>
                    </div>
                @endif
                <h6 @class([
                    'mb-0 small fw-bold text-uppercase',
                    'text-white text-opacity-80' => $gradient && !$isLightVariant,
                    'text-dark text-opacity-80' => $gradient && $isLightVariant,
                ]) @style([
                    "color: var(--stat-card-text-secondary) !important;" => !$gradient
                ])>{{ $title }}</h6>
            </div>
            <h3 @class([
                'mb-1 fw-bold',
                'text-white' => $gradient && !$isLightVariant,
                'text-dark' => $gradient && $isLightVariant,
            ]) @style([
                "color: var(--stat-card-text-primary) !important;" => !$gradient
            ])>{{ $value }}</h3>
            @if($description)
                <p @class([
                    'small-text mb-0',
                    'text-white text-opacity-80' => $gradient && !$isLightVariant,
                    'text-dark text-opacity-80' => $gradient && $isLightVariant,
                ]) @style([
                    "color: var(--stat-card-text-secondary) !important;" => !$gradient,
                    "font-size: 0.75rem; font-weight: 500;"
                ])>{{ $description }}</p>
            @endif
        </div>
    </div>
</div>

<style>
.stat-card-modern {
    transition: transform var(--transition-speed, 0.2s) var(--transition-timing, ease);
}
.stat-card-modern:hover {
    transform: translateY(-5px);
}
</style>
