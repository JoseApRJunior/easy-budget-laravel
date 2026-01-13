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

<div class="{{ $col ?? ($isCustom ? 'col-12 col-md-6 col-xl-5-custom' : 'col-12 col-md-6 col-lg-3') }}">
    <div @class([
        'card border-0 shadow-sm h-100 stat-card-modern',
        "bg-$variant bg-gradient" => $gradient,
    ]) @style([
        "--stat-card-color: var(--text-$variant, var(--primary-color));" => !$gradient,
    ])>
        <div class="card-body p-3 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                @if($icon)
                    <div class="avatar-circle me-2" @style([
                        "background-color: " . ($gradient ? 'var(--contrast-overlay)' : 'var(--stat-card-color)') . " !important;",
                        "width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 50%;"
                    ])>
                        <i class="bi bi-{{ $icon }}" @style([
                            "color: " . ($gradient ? 'var(--contrast-text)' : 'var(--stat-card-color)') . " !important;",
                            "font-size: 0.9rem;"
                        ])></i>
                    </div>
                @endif
                <h6 class="mb-0 small fw-bold text-uppercase" @style([
                    "color: " . ($gradient ? 'var(--contrast-text-secondary)' : 'var(--text-secondary)') . " !important;"
                ])>{{ $title }}</h6>
            </div>
            <h3 class="mb-1 fw-bold" @style([
                "color: " . ($gradient ? 'var(--contrast-text)' : 'var(--text-primary)') . " !important;"
            ])>{{ $value }}</h3>
            @if($description)
                <p class="small-text mb-0" @style([
                    "color: " . ($gradient ? 'var(--contrast-text-secondary)' : 'var(--text-secondary)') . " !important;",
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
