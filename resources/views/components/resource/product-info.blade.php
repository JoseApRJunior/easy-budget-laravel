@props([
    'name',
    'sku',
    'avatar' => false,
    'icon' => 'box'
])

<div {{ $attributes->merge(['class' => 'd-flex align-items-center']) }}>
    @if($avatar)
        <div class="item-icon me-3">
            <i class="bi bi-{{ $icon }}"></i>
        </div>
    @endif
    <div>
        <div class="item-name-cell fw-bold text-dark">
            {{ $name }}
        </div>
        <div class="small text-muted">
            {{ $sku }}
        </div>
    </div>
</div>
