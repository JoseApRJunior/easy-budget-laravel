@props([
    'icon' => null,
    'label' => '',
    'value' => null,
    'iconVariant' => 'primary', // primary, success, info, warning, danger
    'valueClass' => '',
])

@php
    $bgColors = [
        'primary' => 'rgba(var(--bs-primary-rgb), 0.1)',
        'success' => 'rgba(var(--bs-success-rgb), 0.1)',
        'info' => 'rgba(var(--bs-info-rgb), 0.1)',
        'warning' => 'rgba(var(--bs-warning-rgb), 0.1)',
        'danger' => 'rgba(var(--bs-danger-rgb), 0.1)',
        'secondary' => 'rgba(var(--bs-secondary-rgb), 0.1)',
    ];

    $textColors = [
        'primary' => 'var(--bs-primary)',
        'success' => 'var(--bs-success)',
        'info' => 'var(--bs-info)',
        'warning' => 'var(--bs-warning)',
        'danger' => 'var(--bs-danger)',
        'secondary' => 'var(--bs-secondary)',
    ];

    $iconBg = $bgColors[$iconVariant] ?? $bgColors['primary'];
    $iconColor = $textColors[$iconVariant] ?? $textColors['primary'];
@endphp

<div {{ $attributes->merge(['class' => 'col-md-4']) }}>
    <div class="d-flex align-items-center gap-3">
        @if($icon)
            <div class="item-icon" style="width: 48px; height: 48px; background: {{ $iconBg }}; color: {{ $iconColor }}; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="bi bi-{{ $icon }}"></i>
            </div>
        @endif
        <div>
            <label class="text-muted small d-block mb-1">{{ $label }}</label>
            <h5 class="mb-0 fw-bold {{ $valueClass }}">
                {{ $value ?? $slot }}
            </h5>
        </div>
    </div>
</div>
