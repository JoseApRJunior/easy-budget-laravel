@props([
    'variant' => 'warning', // warning (yellow), primary (blue), info (cyan), secondary (gray)
    'icon' => 'chat-quote',
    'label' => null,
    'message' => null,
])

@php
    $variants = [
        'warning' => [
            'bg' => 'bg-light bg-opacity-50',
            'border' => 'border-warning',
            'text' => 'text-muted',
            'icon' => 'text-warning',
            'label' => 'text-warning',
        ],
        'primary' => [
            'bg' => 'bg-light bg-opacity-50',
            'border' => 'border-primary',
            'text' => 'text-primary',
            'icon' => 'text-primary',
            'label' => 'text-primary',
        ],
        'info' => [
            'bg' => 'bg-light bg-opacity-50',
            'border' => 'border-info',
            'text' => 'text-info',
            'icon' => 'text-info',
            'label' => 'text-info',
        ],
        'secondary' => [
            'bg' => 'bg-light bg-opacity-50',
            'border' => 'border-secondary',
            'text' => 'text-muted',
            'icon' => 'text-secondary',
            'label' => 'text-secondary',
        ],
    ];

    $config = $variants[$variant] ?? $variants['warning'];
@endphp

<div {{ $attributes->merge(['class' => "p-2 rounded-3 border-start border-4 {$config['bg']} {$config['border']}"]) }}>
    @if($label)
        <div class="d-flex align-items-center gap-2 mb-1 {{ $config['label'] }}">
            <i class="bi bi-{{ $icon }} small"></i>
            <span class="small fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">{{ $label }}</span>
        </div>
    @endif
    <div class="mb-0 small fst-italic {{ $config['text'] }} {{ $label ? 'ps-3' : 'd-flex align-items-start gap-2' }}">
        @if(!$label)
            <i class="bi bi-{{ $icon }} {{ $config['icon'] }} mt-1"></i>
        @endif
        <span>"{{ $message ?? $slot }}"</span>
    </div>
</div>
