@props([
    'item',                      // O modelo/objeto
    'statusField' => 'is_active', // Campo de status (is_active, active, status, etc.)
    'activeLabel' => 'Ativo',    // Label para estado ativo
    'inactiveLabel' => 'Inativo', // Label para estado inativo
    'deletedLabel' => 'Deletado', // Label para estado deletado
])

@php
    $isDeleted = isset($item->deleted_at) && $item->deleted_at !== null;
    $isActive = $isDeleted ? false : ($item->{$statusField} ?? false);

    // Determinar classe do badge
    $badgeClass = $isDeleted
        ? 'badge-deleted'
        : ($isActive ? 'badge-active' : 'badge-inactive');

    // Determinar label
    $label = $isDeleted
        ? $deletedLabel
        : ($isActive ? $activeLabel : $inactiveLabel);
@endphp

<span {{ $attributes->merge(['class' => 'modern-badge ' . $badgeClass]) }}>
    {{ $label }}
</span>
