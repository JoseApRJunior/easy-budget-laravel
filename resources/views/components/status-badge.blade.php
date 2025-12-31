@props([
    'item',                      // O modelo/objeto
    'statusField' => 'status',   // Campo de status (is_active, active, status, etc.)
    'activeLabel' => 'Ativo',    // Label para estado ativo
    'inactiveLabel' => 'Inativo', // Label para estado inativo
    'deletedLabel' => 'Deletado', // Label para estado deletado
])

@php
    $isDeleted = isset($item->deleted_at) && $item->deleted_at !== null;
    $statusValue = $item->{$statusField};

    // Caso o status seja um Enum (como BudgetStatus)
    if ($statusValue !== null && ($statusValue instanceof \App\Contracts\Interfaces\StatusEnumInterface || method_exists($statusValue, 'getMetadata'))) {
        $metadata = $statusValue->getMetadata();
        $label = $metadata['description'];
        $color = $metadata['color'] ?? '#6c757d';
        $icon = $metadata['icon'] ?? null;

        // Gerar uma classe CSS dinâmica ou usar style inline
        $badgeStyle = "background-color: {$color}20; color: {$color}; border: 1px solid {$color}40;";
        $badgeClass = '';
    } else {
        // Lógica original para booleanos (is_active)
        $isActive = $isDeleted ? false : (bool) ($statusValue ?? false);
        $badgeClass = $isDeleted ? 'badge-deleted' : ($isActive ? 'badge-active' : 'badge-inactive');
        $label = $isDeleted ? $deletedLabel : ($isActive ? $activeLabel : $inactiveLabel);
        $badgeStyle = '';
        $icon = null;
    }
@endphp

<span {{ $attributes->merge(['class' => 'modern-badge ' . $badgeClass, 'style' => $badgeStyle]) }}>
    @if(isset($icon))
        <i class="bi {{ $icon }} me-1"></i>
    @endif
    {{ $label }}
</span>
