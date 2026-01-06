@extends('layouts.app')

@section('title', 'Entrada de Estoque')

@section('content')
<div class="container-fluid py-4">
    <x-page-header
        title="Entrada de Estoque"
        icon="arrow-down"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Inventário' => route('provider.inventory.dashboard'),
            $product->name => route('provider.products.show', $product->sku),
            'Entrada' => '#'
        ]">
        <p class="text-muted mb-0">Registrar entrada de estoque físico</p>
    </x-page-header>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <form action="{{ route('provider.inventory.entry.store', $product->sku) }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <!-- Informações do Produto -->
                        <div class="alert alert-info border-0 shadow-sm mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                                <h6 class="mb-0">Informações do Produto</h6>
                            </div>
                            <div class="row g-2">
                                <div class="col-12">
                                    <small class="text-muted d-block">Produto</small>
                                    <a href="{{ route('provider.products.edit', $product->id) }}" class="fw-bold text-primary text-decoration-none">
                                        {{ $product->name }}
                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">SKU</small>
                                    <span class="badge bg-light text-dark border">{{ $product->sku }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block text-end">Categoria</small>
                                    <span class="text-muted d-block text-end small">{{ $product->category->name ?? 'Sem categoria' }}</span>
                                </div>
                            </div>
                            <hr class="my-2 opacity-10">
                            <div class="row g-3 text-center">
                                <div class="col-4">
                                    <small class="text-muted d-block mb-1">Físico</small>
                                    <span class="badge bg-primary w-100 py-2 fs-6">{{ $product->inventory->quantity ?? 0 }}</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block mb-1">Reservado</small>
                                    <span class="badge bg-info w-100 py-2 fs-6 text-white">{{ $product->inventory->reserved_quantity ?? 0 }}</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block mb-1">Disponível</small>
                                    <span class="badge bg-success w-100 py-2 fs-6">{{ $product->inventory->available_quantity ?? 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="quantity" class="form-label">Quantidade <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <button type="button" class="btn btn-outline-secondary quantity-decrement">-</button>
                                        <input type="number"
                                               name="quantity"
                                               id="quantity"
                                               class="form-control quantity-input @error('quantity') is-invalid @enderror"
                                               min="1"
                                               step="1"
                                               inputmode="numeric"
                                               required
                                               value="{{ old('quantity', 1) }}">
                                        <button type="button" class="btn btn-outline-secondary quantity-increment">+</button>
                                    </div>
                                    @error('quantity')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="reason" class="form-label">Motivo</label>
                                    <input type="text"
                                           name="reason"
                                           id="reason"
                                           class="form-control @error('reason') is-invalid @enderror"
                                           placeholder="Ex: Compra de fornecedor"
                                           value="{{ old('reason') }}">
                                    @error('reason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-md-auto order-2 order-md-1">
                            <x-back-button index-route="provider.inventory.index" class="w-100 w-md-auto px-md-3" />
                        </div>
                        <div class="col-12 col-md text-center d-none d-md-block order-md-2">
                            <!-- Espaçador central para alinhar com o padrão show -->
                        </div>
                        <div class="col-12 col-md-auto order-1 order-md-3">
                            <button type="submit" class="btn btn-success w-100 px-4">
                                <i class="bi bi-check-lg me-1"></i> Confirmar Entrada
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.querySelector('.quantity-input');
    const incBtn = document.querySelector('.quantity-increment');
    const decBtn = document.querySelector('.quantity-decrement');

    // Bloquear entrada não numérica
    quantityInput.addEventListener('keypress', function(e) {
        if (!/[0-9]/.test(e.key)) {
            e.preventDefault();
        }
    });

    // Incremento
    incBtn.addEventListener('click', function() {
        const current = parseInt(quantityInput.value || '1', 10);
        quantityInput.value = (isNaN(current) ? 1 : current + 1);
    });

    // Decremento
    decBtn.addEventListener('click', function() {
        const current = parseInt(quantityInput.value || '1', 10);
        quantityInput.value = Math.max(1, isNaN(current) ? 1 : current - 1);
    });
});
</script>
@endpush
