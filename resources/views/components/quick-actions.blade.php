@props([
    'title' => 'Ações Rápidas',
    'icon' => 'lightning-charge',
    'variant' => 'secondary' // primary, secondary, success, info, warning, danger, none
])

<div @class([
    'card border-0 shadow-sm hover-card mb-4',
    'bg-transparent shadow-none border-0' => $variant === 'none',
])>
    <div @class([
        'card-header d-flex justify-content-between align-items-center',
        "bg-$variant text-white" => !in_array($variant, ['none', 'light', 'transparent']),
        'bg-transparent text-dark border-0 ps-0 py-2' => $variant === 'none',
        'bg-transparent text-dark border-bottom' => $variant === 'transparent',
    ])>
        <h5 @class([
            'card-title mb-0 fw-bold',
            'fs-6 text-muted text-uppercase small' => $variant === 'none',
        ])>
            <i class="bi bi-{{ $icon }} me-2"></i>{{ $title }}
        </h5>
    </div>

    <div @class([
        'card-body',
        'px-0' => $variant === 'none',
    ])>
        <div class="d-grid gap-2">
            {{ $slot }}
        </div>
    </div>
</div>
