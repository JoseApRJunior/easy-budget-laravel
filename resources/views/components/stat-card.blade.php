@props([
    'title',
    'value',
    'description' => null,
    'icon' => null,
    'variant' => 'primary', // primary, success, info, warning, danger, secondary
    'gradient' => true,
    'isCustom' => false // Para col-xl-5-custom
])

<div class="{{ $isCustom ? 'col-12 col-md-6 col-xl-5-custom' : 'col-12 col-md-6 col-lg-3' }}">
    <div @class([
        'card border-0 shadow-sm h-100',
        "bg-$variant" => $gradient && $variant === 'primary',
        "bg-gradient text-white" => $gradient && $variant === 'primary',
    ])>
        <div class="card-body p-3 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                @if($icon)
                    <div @class([
                        'avatar-circle me-2',
                        'bg-white bg-opacity-25' => $variant === 'primary' && $gradient,
                        "bg-$variant bg-gradient" => !($variant === 'primary' && $gradient)
                    ]) style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                        <i class="bi bi-{{ $icon }} text-white" style="font-size: 0.9rem;"></i>
                    </div>
                @endif
                <h6 @class([
                    'mb-0 small fw-bold',
                    'text-white text-opacity-75' => $variant === 'primary' && $gradient,
                    'text-muted' => !($variant === 'primary' && $gradient)
                ])>{{ strtoupper($title) }}</h6>
            </div>
            <h3 @class([
                'mb-1 fw-bold',
                'text-white' => $variant === 'primary' && $gradient,
                "text-$variant" => !($variant === 'primary' && $gradient)
            ])>{{ $value }}</h3>
            @if($description)
                <p @class([
                    'small-text mb-0',
                    'text-white text-opacity-75' => $variant === 'primary' && $gradient,
                    'text-muted' => !($variant === 'primary' && $gradient)
                ])>{{ $description }}</p>
            @endif
        </div>
    </div>
</div>
