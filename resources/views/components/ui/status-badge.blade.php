@props([
'item', // O modelo/objeto
'statusField' => 'status', // Campo de status (is_active, active, status, etc.)
'activeLabel' => 'Ativo', // Label para estado ativo
'inactiveLabel' => 'Inativo', // Label para estado inativo
'deletedLabel' => 'Deletado', // Label para estado deletado
'useColor' => true, // Se deve usar a cor do status
'showIcon' => true, // Se deve mostrar o ícone do status
])

@php
$isDeleted = isset($item->deleted_at) && $item->deleted_at !== null;
$statusValue = $item->{$statusField};
$icon = null;
$badgeStyle = '';
$badgeClass = '';

// Caso o status seja um Enum (como BudgetStatus)
if ($statusValue !== null && (
$statusValue instanceof \App\Contracts\Interfaces\StatusEnumInterface ||
(is_object($statusValue) && method_exists($statusValue, 'getMetadata'))
)) {
$metadata = $statusValue->getMetadata();
$label = $metadata['label'] ?? $metadata['description'];

if ($useColor) {
$color = $metadata['color_hex'] ?? $metadata['color'] ?? '#6c757d';

// Ajuste especial para visibilidade do status 'draft' (cinza claro)
$isDraft = (isset($metadata['value']) && $metadata['value'] === 'draft') ||
(isset($label) && strtolower($label) === 'rascunho');

if ($isDraft) {
$badgeStyle = "background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-weight: 600;";
} else {
$badgeStyle = "background-color: {$color}20; color: {$color}; border: 1px solid {$color}40;";
}
} else {
$badgeClass = 'bg-secondary text-white';
}

if ($showIcon) {
$icon = $metadata['icon'] ?? null;
}
} else {
// Lógica original para booleanos (is_active)
$isActive = $isDeleted ? false : (bool) ($statusValue ?? false);
$badgeClass = $isDeleted ? 'badge-deleted' : ($isActive ? 'badge-active' : 'badge-inactive');
$label = $isDeleted ? $deletedLabel : ($isActive ? $activeLabel : $inactiveLabel);
}
@endphp

<span {{ $attributes->merge(['class' => 'modern-badge ' . $badgeClass, 'style' => $badgeStyle]) }}>
    @if($icon)
    <i class="bi bi-{{ $icon }} me-1"></i>
    @endif
    {{ $label }}
</span>
