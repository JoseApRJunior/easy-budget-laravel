@extends('layouts.app')

@section('title', 'Detalhes do Produto: ' . $product->name)

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Detalhes do Produto"
        icon="box-seam"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Produtos' => route('provider.products.dashboard'),
            $product->name => '#'
        ]">
        <p class="text-muted mb-0">Visualize as informações completas do produto</p>
    </x-layout.page-header>

    <x-layout.v-stack gap="4">
        {{-- Card de Informações Unificado --}}
        <x-resource.resource-header-card
            :title="$product->name"
            :subtitle="'SKU: ' . $product->sku"
        >
            <x-slot:actions>
                <div class="d-flex align-items-center gap-2">
                    <x-ui.status-badge :item="$product" statusField="active" />
                    <x-ui.button type="link" :href="route('provider.inventory.show', $product->sku)" variant="outline-primary" size="sm" icon="bi bi-arrow-right-circle" label="Painel de Inventário" feature="inventory" />
                </div>
            </x-slot:actions>

            {{-- Seção Superior: Imagem e Estoque --}}
            <x-layout.grid-col md="3">
                <div class="position-relative bg-white d-flex align-items-center justify-content-center p-2 rounded border shadow-sm" style="height: 180px;">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                        class="img-fluid h-100 w-100" style="object-fit: contain;">
                </div>
            </x-layout.grid-col>

            <x-layout.grid-col md="9">
                <div class="h-100 d-flex flex-column justify-content-between">
                    <!-- Informações de Estoque Rápido -->
                    @php
                        $inventory = $product->inventory->first();
                        $quantity = $inventory ? $inventory->quantity : 0;
                        $minQuantity = $inventory ? $inventory->min_quantity : 0;
                        $statusClass = $quantity <= 0 ? 'danger' : ($quantity <= $minQuantity ? 'warning' : 'success');
                        $statusLabel = $quantity <= 0 ? 'Sem Estoque' : ($quantity <= $minQuantity ? 'Estoque Baixo' : 'Em Estoque');
                    @endphp

                    <div class="d-flex align-items-center p-3 rounded bg-{{ $statusClass }} bg-opacity-10 border border-{{ $statusClass }} border-opacity-10 mb-3">
                        <div class="avatar-circle bg-{{ $statusClass }} me-3 shadow-sm" style="width: 40px; height: 40px;">
                            <i class="bi bi-box-seam text-white fs-5"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase fw-bold x-small d-block mb-0">Saldo em Estoque</small>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-0 fw-bold text-{{ $statusClass }} me-3">{{ $quantity }} <small class="h6 mb-0 text-muted fw-normal">{{ $product->unit ?? 'un' }}</small></h4>
                                <span class="badge bg-{{ $statusClass }} rounded-pill px-3 py-1 small">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <x-layout.grid-row class="g-3">
                        <x-layout.grid-col md="4">
                            <x-resource.resource-info
                                title="Preço de Venda"
                                :subtitle="$product->formatted_price"
                                icon="cash-stack"
                                iconClass="text-success"
                                titleClass="text-uppercase small fw-bold"
                                subtitleClass="h5 text-success mb-0"
                            />
                        </x-layout.grid-col>
                        <x-layout.grid-col md="4">
                            <x-resource.resource-info
                                title="Preço de Custo"
                                :subtitle="$product->formatted_cost_price"
                                icon="receipt"
                                iconClass="text-muted"
                                titleClass="text-uppercase small fw-bold"
                                subtitleClass="h5 text-muted mb-0"
                            />
                        </x-layout.grid-col>
                        <x-layout.grid-col md="4">
                            @php
                                $marginColor = $product->profit_margin_percentage >= 30 ? 'success' : ($product->profit_margin_percentage >= 15 ? 'warning' : 'danger');
                                $marginValue = $product->cost_price > 0 ? number_format($product->profit_margin_percentage, 1, ',', '.') . '%' : 'N/A';
                            @endphp
                            <x-resource.resource-info
                                title="Margem de Lucro"
                                :subtitle="$marginValue"
                                icon="percent"
                                iconClass="text-{{ $marginColor }}"
                                titleClass="text-uppercase small fw-bold"
                                subtitleClass="h5 text-{{ $marginColor }} mb-0"
                            />
                        </x-layout.grid-col>
                    </x-layout.grid-row>
                </div>
            </x-layout.grid-col>

            <x-resource.resource-header-divider />

            {{-- Seção Intermediária: Categorização e Unidade --}}
            <x-layout.grid-col md="4">
                <x-resource.resource-info
                    title="Categoria"
                    icon="folder2-open"
                    iconClass="text-primary"
                    titleClass="text-uppercase small fw-bold"
                >
                    <x-slot:subtitle>
                        @if ($product->category)
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 border border-primary border-opacity-10 small">
                                {{ $product->category->parent_id ? $product->category->getFormattedHierarchy() : $product->category->name }}
                            </span>
                        @else
                            <span class="text-muted">Sem categoria</span>
                        @endif
                    </x-slot:subtitle>
                </x-resource.resource-info>
            </x-layout.grid-col>

            <x-layout.grid-col md="4">
                <x-resource.resource-info
                    title="Unidade de Medida"
                    :subtitle="$product->unit ?? 'Não informada'"
                    icon="rulers"
                    iconClass="text-info"
                    titleClass="text-uppercase small fw-bold"
                    subtitleClass="text-dark"
                />
            </x-layout.grid-col>

            <x-layout.grid-col md="4">
                <x-resource.resource-info
                    title="Data de Cadastro"
                    :subtitle="$product->created_at->format('d/m/Y H:i')"
                    icon="calendar-plus"
                    iconClass="text-muted"
                    titleClass="text-uppercase small fw-bold"
                    subtitleClass="text-dark"
                />
            </x-layout.grid-col>

            <x-resource.resource-header-divider />

            {{-- Seção Inferior: Descrição --}}
            <x-layout.grid-col cols="12">
                <x-resource.resource-info
                    title="Descrição do Produto"
                    icon="card-text"
                    iconClass="text-secondary"
                    titleClass="text-uppercase small fw-bold"
                >
                    <x-slot:subtitle>
                        <div class="mt-2 p-3 rounded bg-light border border-light-subtle text-dark">
                            {{ $product->description ?? 'Nenhuma descrição detalhada informada para este produto.' }}
                        </div>
                    </x-slot:subtitle>
                </x-resource.resource-info>
            </x-layout.grid-col>
        </x-resource.resource-header-card>
    </x-layout.v-stack>

    <!-- Footer com Ações -->
    <div class="mt-4 pt-3 border-top">
        <x-layout.grid-row class="align-items-center g-3">
            <x-layout.grid-col cols="12" md="auto" class="order-2 order-md-1">
                <x-ui.back-button index-route="provider.products.index" class="w-100 w-md-auto px-md-4" feature="products" />
            </x-layout.grid-col>

            <x-layout.grid-col cols="12" md="true" class="text-center d-none d-md-block order-md-2">
                <small class="text-muted fst-italic">
                    Última atualização: {{ $product->updated_at?->format('d/m/Y H:i') }}
                </small>
            </x-layout.grid-col>

            <x-layout.grid-col cols="12" md="auto" class="order-1 order-md-3">
                <div class="d-grid d-md-flex gap-2">
                    @if ($product->deleted_at)
                        <x-ui.button variant="success" class="px-4" data-bs-toggle="modal" data-bs-target="#restoreModal"
                            data-restore-url="{{ route('provider.products.restore', $product->sku) }}"
                            data-product-name="{{ $product->name }}" icon="arrow-counterclockwise" label="Restaurar" feature="products" />
                    @else
                        <x-ui.button type="link" :href="route('provider.products.edit', $product->sku)" class="px-4" icon="pencil-fill" label="Editar" feature="products" />

                        <x-ui.button :variant="$product->active ? 'warning' : 'success'" class="px-4"
                            data-bs-toggle="modal" data-bs-target="#toggleModal"
                            data-toggle-url="{{ route('provider.products.toggle-status', $product->sku) }}"
                            data-product-name="{{ $product->name }}"
                            data-action="{{ $product->active ? 'Desativar' : 'Ativar' }}"
                            :icon="$product->active ? 'slash-circle' : 'check-lg'"
                            :label="$product->active ? 'Desativar' : 'Ativar'" feature="products" />

                        <x-ui.button variant="danger" class="px-4" data-bs-toggle="modal" data-bs-target="#deleteModal"
                            data-delete-url="{{ route('provider.products.destroy', $product->sku) }}"
                            data-product-name="{{ $product->name }}" icon="trash-fill" label="Excluir" feature="products" />
                    @endif
                </div>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </div>
</x-layout.page-container>

<!-- Modal de Confirmação de Ativação/Desativação -->
<div class="modal fade" id="toggleModal" tabindex="-1" aria-labelledby="toggleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toggleModalLabel">Confirmar
                    {{ $product->active ? 'Desativação' : 'Ativação' }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                Tem certeza de que deseja {{ $product->active ? 'desativar' : 'ativar' }} o produto <strong
                    id="toggleProductName"></strong>?
                <br><small class="text-muted">Esta ação pode afetar a disponibilidade do produto.</small>
            </div>
            <div class="modal-footer">
                <x-ui.button variant="secondary" data-bs-dismiss="modal" label="Cancelar" feature="products" />
                <form id="toggleForm" action="#" method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <x-ui.button type="submit" :variant="$product->active ? 'warning' : 'success'"
                        :icon="$product->active ? 'slash-circle' : 'check-lg'"
                        :label="$product->active ? 'Desativar' : 'Ativar'" feature="products" />
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                Tem certeza de que deseja excluir o produto <strong id="deleteProductName"></strong>?
                <br><small class="text-muted">Esta ação pode ser desfeita.</small>
            </div>
            <div class="modal-footer">
                <x-ui.button variant="secondary" data-bs-dismiss="modal" label="Cancelar" feature="products" />
                <form id="deleteForm" action="#" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" label="Excluir" feature="products" />
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Restauração -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="restoreModalLabel">Confirmar Restauração</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                Tem certeza de que deseja restaurar o produto <strong id="restoreProductName"></strong>?
                <br><small class="text-muted">O produto será restaurado e ficará disponível novamente.</small>
            </div>
            <div class="modal-footer">
                <x-ui.button variant="secondary" data-bs-dismiss="modal" label="Cancelar" feature="products" />
                <form id="restoreForm" action="#" method="POST" class="d-inline">
                    @csrf
                    <x-ui.button type="submit" variant="success" label="Restaurar" feature="products" />
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Modal Atualizar Limites -->
<div class="modal fade" id="updateLimitsModal" tabindex="-1" aria-labelledby="updateLimitsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold" id="updateLimitsModalLabel">
                    <i class="bi bi-sliders me-2"></i>Editar Limites de Estoque
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('provider.inventory.limits.update', $product->sku) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted small mb-4">
                        Defina os níveis mínimo e máximo para receber alertas automáticos de reposição e excesso de estoque.
                    </p>

                    <div class="mb-3">
                        <label for="min_quantity" class="form-label fw-bold">Estoque Mínimo (Alerta de Baixa)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-arrow-down-circle text-danger"></i></span>
                            <input type="number" class="form-control" id="min_quantity" name="min_quantity"
                                value="{{ old('min_quantity', $inventory?->min_quantity ?? 0) }}" min="0" required>
                        </div>
                        <div class="form-text">Você será notificado quando o estoque estiver igual ou abaixo deste valor.</div>
                    </div>

                    <div class="mb-0">
                        <label for="max_quantity" class="form-label fw-bold">Estoque Máximo (Opcional)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-arrow-up-circle text-success"></i></span>
                            <input type="number" class="form-control" id="max_quantity" name="max_quantity"
                                value="{{ old('max_quantity', $inventory?->max_quantity) }}" min="0">
                        </div>
                        <div class="form-text">Define o limite ideal de armazenamento para este produto. Deixe vazio para não limitar.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Cancelar</button>
                    <x-ui.button type="submit" variant="primary" label="Salvar Alterações" icon="check-lg" feature="inventory" />
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Script para os modais
    document.addEventListener('DOMContentLoaded', function() {
        // Modal de exclusão
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const deleteUrl = button.getAttribute('data-delete-url');
                const productName = button.getAttribute('data-product-name');

                const deleteProductName = deleteModal.querySelector('#deleteProductName');
                const deleteForm = deleteModal.querySelector('#deleteForm');

                deleteProductName.textContent = productName;
                deleteForm.action = deleteUrl;
            });
        }

        // Modal de ativação/desativação
        const toggleModal = document.getElementById('toggleModal');
        if (toggleModal) {
            toggleModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const toggleUrl = button.getAttribute('data-toggle-url');
                const productName = button.getAttribute('data-product-name');
                const action = button.getAttribute('data-action');

                const toggleProductName = toggleModal.querySelector('#toggleProductName');
                const toggleForm = toggleModal.querySelector('#toggleForm');
                const toggleTitle = toggleModal.querySelector('#toggleModalLabel');
                const toggleButton = toggleModal.querySelector('button[type="submit"]');

                toggleProductName.textContent = productName;
                toggleForm.action = toggleUrl;

                // Atualiza o título e texto do modal com base na ação
                toggleTitle.textContent = action === 'Desativar' ? 'Confirmar Desativação' :
                    'Confirmar Ativação';
                toggleButton.textContent = action;
            });
        }

        // Modal de restauração
        const restoreModal = document.getElementById('restoreModal');
        if (restoreModal) {
            restoreModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const restoreUrl = button.getAttribute('data-restore-url');
                const productName = button.getAttribute('data-product-name');

                const restoreProductName = restoreModal.querySelector('#restoreProductName');
                const restoreForm = restoreModal.querySelector('#restoreForm');

                restoreProductName.textContent = productName;
                restoreForm.action = restoreUrl;
            });
        }
    });
</script>
@endpush
