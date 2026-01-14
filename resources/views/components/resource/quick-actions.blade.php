@props([
    'title' => 'Ações Rápidas',
    'icon' => 'lightning-charge',
    'variant' => 'none', // primary, secondary, success, info, warning, danger, none
    'col' => null, // Nova prop para gerenciar wrapper de grid
])

@if($col)
    <div class="{{ $col }}">
@endif

<div {{ $attributes->merge(['class' => 'card border-0 shadow-sm h-100']) }}>
    <div @class([
        'card-header py-3 border-1',
        "bg-$variant text-white" => !in_array($variant, ['none', 'light', 'transparent']),
        'text-dark' => in_array($variant, ['none', 'transparent']),
    ])>
        <h5 class="card-title mb-0 d-flex align-items-center">
            <i @class([
                "bi bi-$icon me-2",
                'text-primary' => in_array($variant, ['none', 'transparent'])
            ])></i>{{ $title }}
        </h5>
    </div>

    <div class="card-body">
        <div class="d-grid gap-2">
            {{ $slot }}
        </div>
    </div>
</div>

@if($col)
    </div>
@endif
