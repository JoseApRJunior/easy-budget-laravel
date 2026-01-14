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
    <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
                @if($icon)
                    <div @class([
                        'avatar-circle me-3',
                        "bg-$variant" => $variant,
                        "bg-gradient" => $gradient,
                    ])>
                        <i class="bi bi-{{ $icon }} text-white"></i>
                    </div>
                @endif
                <div>
                    <h6 class="text-muted mb-1 small fw-bold text-uppercase" >{{ $title }}</h6>
                    <h3 class="mb-0 fw-bold">{{ $value }}</h3>
                </div>
            </div>
            @if($description)
                <p class="text-muted small mb-0 lh-sm">
                    {{ $description }}
                </p>
            @endif
        </div>
    </div>
</div>
