@extends('layouts.app')

@section('title', 'Detalhes do Produto: ' . $product->name)

@section('content')
    <div class="container-fluid py-1">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-box-seam me-2"></i>Detalhes do Produto
                </h1>
                <p class="text-muted mb-0">Visualize as informações completas do produto</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('provider.products.index') }}">Produtos</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <!-- Coluna Esquerda: Imagem e Status -->
            <div class="col-md-4 mb-4">
                <div class="card">
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
                <div class="card">
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
                                    <div class="col-md-6">
                                        <label class="text-muted small text-uppercase fw-bold">Preço</label>
                                        <p class="h4 text-success">R$ {{ number_format($product->price, 2, ',', '.') }}
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
                                        $statusClass = 'danger';
                                        $statusLabel = 'Sem Estoque';
                                    } elseif ($quantity <= $minQuantity) {
                                        $statusClass = 'warning';
                                        $statusLabel = 'Estoque Baixo';
                                    }
                                @endphp

                                <div class="row mb-4 ">
                                    <div class="col-md-4 text-center">
                                        <div class="p-3 border rounded">
                                            <small class="text-muted text-uppercase">Quantidade Atual</small>
                                            <h2 class="display-4 fw-bold text-{{ $statusClass }} mb-0">
                                                {{ $quantity }}</h2>
                                            <span
                                                class="modern-badge {{ $statusClass === 'success' ? 'badge-active' : ($statusClass === 'danger' ? 'badge-inactive' : 'badge-warning') }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-8 mt-2">
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <div class="p-3 border rounded">
                                                    <small class="text-muted">Mínimo</small>
                                                    <p class="h4 mb-0">{{ $minQuantity }}</p>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-3 border rounded">
                                                    <small class="text-muted">Máximo</small>
                                                    <p class="h4 mb-0">{{ $maxQuantity ?? '∞' }}</p>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex gap-2 mt-2">
                                                    <a href="{{ route('provider.inventory.entry', $product->sku) }}"
                                                        class="btn btn-success flex-grow-1">
                                                        <i class="bi bi-arrow-down-circle me-1"></i> Entrada
                                                    </a>
                                                    <a href="{{ route('provider.inventory.exit', $product->sku) }}"
                                                        class="btn btn-warning flex-grow-1">
                                                        <i class="bi bi-arrow-up-circle me-1"></i> Saída
                                                    </a>
                                                    <a href="{{ route('provider.inventory.adjust', $product->sku) }}"
                                                        class="btn btn-secondary flex-grow-1">
                                                        <i class="bi bi-sliders me-1"></i> Ajustar
                                                    </a>
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
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center mt-4">
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary w-100 mb-2 mb-md-0">
                <i class="bi bi-arrow-left me-2"></i>Voltar
            </a>
            <small class="text-muted d-none d-md-block">
                Última atualização: {{ $product->updated_at?->format('d/m/Y H:i') }}
            </small>
            <div class="d-grid gap-2 d-md-flex flex-wrap justify-content-md-end w-100 w-md-auto">
                @if ($product->deleted_at)
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#restoreModal"
                        data-restore-url="{{ route('provider.products.restore', $product->sku) }}"
                        data-product-name="{{ $product->name }}">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Restaurar
                    </button>
                @else
                    <a href="{{ route('provider.products.edit', $product->sku) }}" class="btn btn-primary">
                        <i class="bi bi-pencil-fill me-2"></i>Editar
                    </a>
                    <button type="button" class="btn {{ $product->active ? 'btn-warning' : 'btn-success' }}"
                        data-bs-toggle="modal" data-bs-target="#toggleModal"
                        data-toggle-url="{{ route('provider.products.toggle-status', $product->sku) }}"
                        data-product-name="{{ $product->name }}"
                        data-action="{{ $product->active ? 'Desativar' : 'Ativar' }}">
                        <i class="bi bi-{{ $product->active ? 'slash-circle' : 'check-lg' }} me-2"></i>
                        {{ $product->active ? 'Desativar' : 'Ativar' }}
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
                        data-delete-url="{{ route('provider.products.destroy', $product->sku) }}"
                        data-product-name="{{ $product->name }}">
                        <i class="bi bi-trash-fill me-2"></i>Excluir
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Ativação/Desativação -->
    <div class="modal fade" id="toggleModal" tabindex="-1" aria-labelledby="toggleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="toggleModalLabel">Confirmar
                        {{ $product->active ? 'Desativação' : 'Ativação' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    Tem certeza de que deseja {{ $product->active ? 'desativar' : 'ativar' }} o produto <strong
                        id="toggleProductName"></strong>?
                    <br><small class="text-muted">Esta ação pode afetar a disponibilidade do produto.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="toggleForm" action="#" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn {{ $product->active ? 'btn-warning' : 'btn-success' }}">
                            <i class="bi bi-{{ $product->active ? 'slash-circle' : 'check-lg' }} me-2"></i>
                            {{ $product->active ? 'Desativar' : 'Ativar' }}
                        </button>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" action="#" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Excluir</button>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="restoreForm" action="#" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">Restaurar</button>
                    </form>
                </div>
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
