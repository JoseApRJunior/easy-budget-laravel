@props([
    'item',                      // O modelo/objeto
    'statusField' => 'status',   // Campo de status
    'useColor' => false,         // Se deve usar a cor do status no texto
])

@php
    $statusValue = $item->{$statusField};
    $description = '';
    $color = null;
    $icon = 'bi bi-info-circle';

    if ($statusValue !== null && (
        $statusValue instanceof \App\Contracts\Interfaces\StatusEnumInterface ||
        (is_object($statusValue) && method_exists($statusValue, 'getMetadata'))
    )) {
        $metadata = $statusValue->getMetadata();
        $description = $metadata['description'] ?? '';
        $color = $metadata['color_hex'] ?? $metadata['color'] ?? null;

        if (method_exists($statusValue, 'getIcon')) {
            $icon = $statusValue->getIcon();
        } elseif (method_exists($statusValue, 'icon')) {
            $icon = 'bi bi-' . $statusValue->icon();
        }
    }

    $style = $useColor && $color ? "color: {$color} !important;" : "";
@endphp

@if($description)
    <span {{ $attributes->merge(['class' => 'small ' . ($useColor ? '' : (str_contains($attributes->get('class', ''), 'text-') ? '' : 'text-muted')), 'style' => $style]) }}>
        <i class="{{ $icon }} me-1 {{ str_contains($attributes->get('class', ''), 'text-') ? '' : 'opacity-75' }}"></i>
        {{ $description }}
    </span>
@endif
