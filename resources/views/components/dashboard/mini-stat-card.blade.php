@props([
    'label',
    'value',
    'icon' => null,
    'variant' => 'primary',
    'col' => 'col-6'
])

<div {{ $attributes->merge(['class' => $col]) }}>
    <div class="p-2 rounded border transition-all hover-shadow-sm h-100 d-flex justify-content-between align-items-center" style="background-color: var(--hover-bg);">
        <div>
            <div class="d-flex align-items-center mb-1">
                @if($icon)
                    <i class="bi bi-{{ $icon }} text-{{ $variant }} me-1 small"></i>
                @endif
                <p class="text-muted x-small text-uppercase mb-0 fw-medium">{{ $label }}</p>
            </div>
            <div class="fw-bold text-{{ $variant }} h5 mb-0">{{ $value }}</div>
        </div>
        
        @if(isset($actions))
            <div class="text-end">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
