@extends('layouts.app')

@section('title', 'Detalhes do Produto: ' . $product->name)

@section('content')
<div class="container-fluid py-4 d-flex flex-column" style="min-height: calc(100vh - 200px);">
    <div class="flex-grow-1">
        <x-page-header
            title="Detalhes do Produto"
            icon="box-seam"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Produtos' => route('provider.products.dashboard'),
                $product->name => '#'
            ]">
            <p class="text-muted mb-0">Visualize as informações completas do produto</p>
        </x-page-header>

        <div class="row">
            <!-- Coluna Esquerda: Imagem e Status -->
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                            class="img-fluid rounded shadow-sm mb-3" style="max-height: 300px; object-fit: cover;">

                        <div class="d-grid gap-2">
                            @if ($product->deleted_at)
                            <div class="alert alert-danger py-2 mb-0">
                                <i class="bi bi-trash-fill me-1"></i> Produto Deletado
                            </div>
                            @elseif ($product->active)
                            <div class="alert alert-success py-2 mb-0">
                                <i class="bi bi-check-circle-fill me-1"></i> Produto Ativo
                            </div>
                            @else
                            <div class="alert alert-warning py-2 mb-0">
                                <i class="bi bi-x-circle-fill me-1"></i> Produto Inativo
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita: Detalhes e Abas -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" id="productTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="details-tab" data-bs-toggle="tab"
                                    data-bs-target="#details" type="button" role="tab" aria-selected="true">
                                    <i class="bi bi-info-circle me-1"></i> Detalhes
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory"
                                    type="button" role="tab" aria-selected="false">
                                    <i class="bi bi-boxes me-1"></i> Inventário
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="productTabsContent">
                            <!-- Aba Detalhes -->
                            <div class="tab-pane fade show active" id="details" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="text-muted small text-uppercase fw-bold">Preço de Venda</label>
                                        <p class="h4 text-success">{{ $product->formatted_price }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small text-uppercase fw-bold">Preço de Custo</label>
                                        <p class="h4 text-muted">{{ $product->formatted_cost_price }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted small text-uppercase fw-bold">Margem de Lucro</label>
                                        <p class="h4">
                                            @if($product->cost_price > 0)
                                                <span class="text-{{ $product->profit_margin_percentage >= 30 ? 'success' : ($product->profit_margin_percentage >= 15 ? 'warning' : 'danger') }}">
                                                    {{ number_format($product->profit_margin_percentage, 1, ',', '.') }}%
                                                </span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small text-uppercase fw-bold">Categoria</label>
                                        <p class="h5">
                                            @if ($product->category)
                                            <span class="badge bg-primary">
                                                @if ($product->category->parent_id)
                                                {{ $product->category->getFormattedHierarchy() }}
                                                @else
                                                {{ $product->category->name }}
                                                @endif
                                            </span>
                                            @else
                                            <span class="text-muted">Sem categoria</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small text-uppercase fw-bold">Unidade</label>
                                        <p class="h5">{{ $product->unit ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small text-uppercase fw-bold">Criado em</label>
                                        <p>{{ $product->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <div class="col-12">
                                        <label class="text-muted small text-uppercase fw-bold">Descrição</label>
                                        <div class="p-3 rounded border">
                                            {{ $product->description ?? 'Nenhuma descrição informada.' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Inventário -->
                            <div class="tab-pane fade" id="inventory" role="tabpanel">
                                @php
                                $inventory = $product->inventory->first();
                                $quantity = $inventory ? $inventory->quantity : 0;
                                $minQuantity = $inventory ? $inventory->min_quantity : 0;
                                $maxQuantity = $inventory ? $inventory->max_quantity : null;

                                $statusClass = 'success';
                                $statusLabel = 'Estoque OK';

                                if ($quantity <= 0) {
                                    $statusClass='danger' ;
                                    $statusLabel='Sem Estoque' ;
                                    } elseif ($quantity <=$minQuantity) {
                                    $statusClass='warning' ;
                                    $statusLabel='Estoque Baixo' ;
                                    }
                                    @endphp

                                    <div class="row mb-4 ">
                                    <div class="col-md-4 text-center">
                                        <div class="p-3 border rounded">
                                            <small class="text-muted text-uppercase">Quantidade Atual</small>
                                            <h2 class="display-4 fw-bold text-{{ $statusClass }} mb-0">
                                                {{ $quantity }}
                                            </h2>
                                            <span
                                                class="modern-badge {{ $statusClass === 'success' ? 'badge-active' : ($statusClass === 'danger' ? 'badge-inactive' : 'badge-warning') }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-8 mt-2">
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <div class="p-3 border rounded position-relative">
                                                    <small class="text-muted">Mínimo</small>
                                                    <p class="h4 mb-0">{{ $minQuantity }}</p>
                                                    @can('adjustInventory', $product)
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0 position-absolute top-0 end-0 m-1" data-bs-toggle="modal" data-bs-target="#updateLimitsModal" title="Editar Limites">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-3 border rounded position-relative">
                                                    <small class="text-muted">Máximo</small>
                                                    <p class="h4 mb-0">{{ $maxQuantity ?? '∞' }}</p>
                                                    @can('adjustInventory', $product)
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0 position-absolute top-0 end-0 m-1" data-bs-toggle="modal" data-bs-target="#updateLimitsModal" title="Editar Limites">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex gap-2 mt-2">
                                                    <x-button type="link" :href="route('provider.inventory.entry', $product->sku)" variant="success" class="flex-grow-1" icon="arrow-down-circle" label="Entrada" />
                                                    <x-button type="link" :href="route('provider.inventory.exit', $product->sku)" variant="warning" class="flex-grow-1" icon="arrow-up-circle" label="Saída" />
                                                    <x-button type="link" :href="route('provider.inventory.adjust', $product->sku)" variant="secondary" class="flex-grow-1" icon="sliders" label="Ajustar" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Para ver o histórico completo de movimentações, acesse o <a
                                    href="{{ route('provider.inventory.show', $product->sku) }}"
                                    class="alert-link">Painel de Inventário</a> deste produto.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer com Ações -->
    <div class="mt-auto pt-4 pb-2">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-auto order-2 order-md-1">
                <x-back-button index-route="provider.products.index" class="w-100 w-md-auto px-md-3" />
            </div>

            <div class="col-12 col-md text-center d-none d-md-block order-md-2">
                <small class="text-muted">
                    Última atualização: {{ $product->updated_at?->format('d/m/Y H:i') }}
                </small>
            </div>

            <div class="col-12 col-md-auto order-1 order-md-3">
                <div class="d-grid d-md-flex gap-2">
                    @if ($product->deleted_at)
                    <x-button variant="success" style="min-width: 120px;" data-bs-toggle="modal" data-bs-target="#restoreModal"
                        data-restore-url="{{ route('provider.products.restore', $product->sku) }}"
                        data-product-name="{{ $product->name }}" icon="arrow-counterclockwise" label="Restaurar" />
                    @else
                    <x-button type="link" :href="route('provider.products.edit', $product->sku)" style="min-width: 120px;" icon="pencil-fill" label="Editar" />

                    <x-button :variant="$product->active ? 'warning' : 'success'" style="min-width: 120px;"
                        data-bs-toggle="modal" data-bs-target="#toggleModal"
                        data-toggle-url="{{ route('provider.products.toggle-status', $product->sku) }}"
                        data-product-name="{{ $product->name }}"
                        data-action="{{ $product->active ? 'Desativar' : 'Ativar' }}"
                        :icon="$product->active ? 'slash-circle' : 'check-lg'"
                        :label="$product->active ? 'Desativar' : 'Ativar'" />

                    <x-button variant="danger" style="min-width: 120px;" data-bs-toggle="modal" data-bs-target="#deleteModal"
                        data-delete-url="{{ route('provider.products.destroy', $product->sku) }}"
                        data-product-name="{{ $product->name }}" icon="trash-fill" label="Excluir" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

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
                <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                <form id="toggleForm" action="#" method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <x-button type="submit" :variant="$product->active ? 'warning' : 'success'"
                        :icon="$product->active ? 'slash-circle' : 'check-lg'"
                        :label="$product->active ? 'Desativar' : 'Ativar'" />
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
                <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                <form id="deleteForm" action="#" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" label="Excluir" />
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
                <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                <form id="restoreForm" action="#" method="POST" class="d-inline">
                    @csrf
                    <x-button type="submit" variant="success" label="Restaurar" />
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
                    <x-button type="submit" variant="primary" label="Salvar Alterações" icon="check-lg" />
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
