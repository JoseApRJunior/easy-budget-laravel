@props([
    'item',                      // O modelo/objeto (category, product, etc.)
    'resource',                  // Nome do recurso (categories, products, etc.)
    'identifier' => 'id',        // Campo identificador (id, slug, sku, etc.)
    'nameField' => 'name',       // Campo para o nome do item
    'canDelete' => true,         // Se pode mostrar botão de deletar
    'restoreBlocked' => false,   // Se a restauração está bloqueada
    'restoreBlockedMessage' => 'Não é possível restaurar este item no momento.',
    'size' => null,              // Tamanho dos botões (sm, lg, null)
])

@php
    $identifierValue = $item->{$identifier};
    $itemName = $item->{$nameField};
    $isDeleted = $item->deleted_at !== null;
    $resourceSingular = rtrim($resource, 's'); // Remove 's' final para singular
@endphp

<div {{ $attributes->merge(['class' => 'action-btn-group']) }}>
    @if ($isDeleted)
        {{-- Item deletado: mostrar Visualizar + Restaurar --}}
        <x-ui.button
            type="link"
            :href="route('provider.' . $resource . '.show', $identifierValue)"
            variant="info"
            icon="eye"
            title="Visualizar"
            :size="$size"
        />

        <x-ui.button
            variant="success"
            icon="arrow-counterclockwise"
            data-bs-toggle="modal"
            data-bs-target="{{ $restoreBlocked ? '' : '#restoreModal' }}"
            data-restore-url="{{ route('provider.' . $resource . '.restore', $identifierValue) }}"
            :data-item-name="$itemName"
            title="{{ $restoreBlocked ? $restoreBlockedMessage : 'Restaurar' }}"
            :class="$restoreBlocked ? 'opacity-50' : ''"
            style="{{ $restoreBlocked ? 'cursor: not-allowed;' : '' }}"
            :onclick="$restoreBlocked ? 'easyAlert.warning(\'' . addslashes($restoreBlockedMessage) . '\', { duration: 8000 }); return false;' : ''"
            :size="$size"
        />
    @else
        {{-- Item ativo: mostrar Visualizar + Editar + Deletar (condicional) --}}
        <x-ui.button
            type="link"
            :href="route('provider.' . $resource . '.show', $identifierValue)"
            variant="info"
            icon="eye"
            title="Visualizar"
            :size="$size"
        />

        <x-ui.button
            type="link"
            :href="route('provider.' . $resource . '.edit', $identifierValue)"
            icon="pencil-square"
            title="Editar"
            :size="$size"
        />

        @if ($canDelete)
            <x-ui.button
                variant="danger"
                icon="trash"
                data-bs-toggle="modal"
                data-bs-target="#deleteModal"
                data-delete-url="{{ route('provider.' . $resource . '.destroy', $identifierValue) }}"
                :data-item-name="$itemName"
                title="Excluir"
                :size="$size"
            />
        @endif
    @endif
</div>
