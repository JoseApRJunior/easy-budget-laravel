@props([
    'id',                        // ID do modal (deleteModal, restoreModal, etc.)
    'type' => 'delete',          // Tipo: delete, restore, confirm
    'resource' => 'item',        // Nome do recurso no singular (categoria, produto, etc.)
    'resourceField' => 'name',   // Campo que identifica o item (name, title, etc.)
    'method' => 'DELETE',        // Método HTTP (DELETE, POST, PUT)
    'title' => null,             // Título customizado (opcional)
    'message' => null,           // Mensagem customizada (opcional)
    'confirmLabel' => null,      // Label do botão de confirmar (opcional)
    'cancelLabel' => 'Cancelar', // Label do botão de cancelar
    'feature' => null,           // Feature flag para controle de acesso (opcional)
])

@php
    // Configuração padrão baseada no tipo
    $config = [
        'delete' => [
            'title' => 'Confirmar Exclusão',
            'message' => 'Tem certeza de que deseja excluir {{resource}} <strong id="{{id}}ItemName"></strong>?',
            'submessage' => 'Esta ação não pode ser desfeita.',
            'confirmLabel' => 'Excluir',
            'variant' => 'danger',
            'icon' => 'trash'
        ],
        'restore' => [
            'title' => 'Confirmar Restauração',
            'message' => 'Tem certeza de que deseja restaurar {{resource}} <strong id="{{id}}ItemName"></strong>?',
            'submessage' => 'O item será restaurado e ficará disponível novamente.',
            'confirmLabel' => 'Restaurar',
            'variant' => 'success',
            'icon' => 'arrow-counterclockwise'
        ],
        'confirm' => [
            'title' => 'Confirmar Ação',
            'message' => 'Tem certeza de que deseja continuar com esta ação?',
            'submessage' => null,
            'confirmLabel' => 'Confirmar',
            'variant' => 'primary',
            'icon' => 'check'
        ],
    ];

    $currentConfig = $config[$type] ?? $config['confirm'];

    // Aplicar customizações
    $modalTitle = $title ?? $currentConfig['title'];
    $modalMessage = $message ?? str_replace(
        ['{{resource}}', '{{id}}'],
        [$resource, $id],
        $currentConfig['message']
    );
    $confirmButtonLabel = $confirmLabel ?? $currentConfig['confirmLabel'];
    $formId = $id . 'Form';
    $itemNameId = $id . 'ItemName';
@endphp

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $id }}Label">{{ $modalTitle }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                {!! $modalMessage !!}
                @if($currentConfig['submessage'])
                    <br><small class="text-muted">{{ $currentConfig['submessage'] }}</small>
                @endif
            </div>
            <div class="modal-footer">
                <x-ui.button
                    variant="secondary"
                    data-bs-dismiss="modal"
                    :label="$cancelLabel"
                />
                <form id="{{ $formId }}" action="#" method="POST" class="d-inline">
                    @csrf
                    @method($method)
                    <x-ui.button
                        type="submit"
                        :variant="$currentConfig['variant']"
                        :label="$confirmButtonLabel"
                        :feature="$feature"
                    />
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('{{ $id }}');
        if (modal) {
            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;

                // Pegar dados do botão que disparou o modal
                const actionUrl = button.getAttribute('data-{{ $type }}-url') ||
                                button.getAttribute('data-action-url');
                const itemName = button.getAttribute('data-item-name') ||
                               button.getAttribute('data-{{ str_replace('-', '', $resource) }}-name');

                // Atualizar elementos do modal
                const itemNameElement = modal.querySelector('#{{ $itemNameId }}');
                const form = modal.querySelector('#{{ $formId }}');

                if (itemNameElement && itemName) {
                    itemNameElement.textContent = itemName;
                }

                if (form && actionUrl) {
                    form.action = actionUrl;
                }
            });
        }
    });
</script>
@endpush
