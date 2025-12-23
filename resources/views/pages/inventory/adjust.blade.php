@extends('layouts.app')

@section('title', 'Ajustar Estoque - ' . $product->name)

@section('content')
<div class="container-fluid py-1">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-sliders text-secondary me-2"></i>
                        Ajustar Estoque - {{ $product->name }}
                    </h3>
                </div>
                <form action="{{ route('provider.inventory.adjust.store', $product->sku) }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <!-- Informações do Produto -->
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Produto:</strong> {{ $product->name }}<br>
                            <strong>SKU:</strong> {{ $product->sku }}<br>
                            <strong>Estoque Atual:</strong> <span class="badge bg-primary">{{ $inventory->quantity ?? 0 }}</span>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="current_quantity" class="form-label">Quantidade Atual</label>
                                    <input type="number"
                                           class="form-control"
                                           value="{{ $inventory->quantity ?? 0 }}"
                                           disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="new_quantity" class="form-label">Nova Quantidade <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <button type="button" class="btn btn-outline-secondary quantity-decrement">-</button>
                                        <input type="number"
                                               name="new_quantity"
                                               id="new_quantity"
                                               class="form-control quantity-input @error('new_quantity') is-invalid @enderror"
                                               min="0"
                                               step="1"
                                               inputmode="numeric"
                                               required
                                               value="{{ old('new_quantity', $inventory->quantity ?? 0) }}">
                                        <button type="button" class="btn btn-outline-secondary quantity-increment">+</button>
                                    </div>
                                    @error('new_quantity')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Digite a quantidade que deseja definir como estoque atual
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group mb-3">
                                    <label for="reason" class="form-label">Motivo do Ajuste <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('reason') is-invalid @enderror"
                                              id="reason"
                                              name="reason"
                                              rows="3"
                                              minlength="10"
                                              maxlength="500"
                                              required
                                              placeholder="Descreva detalhadamente o motivo deste ajuste de estoque...">{{ old('reason') }}</textarea>
                                    @error('reason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Mínimo 10 caracteres. Este motivo será registrado no histórico de movimentações.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Importante:</strong> Este ajuste irá alterar a quantidade atual do produto no estoque.
                            A diferença entre a quantidade atual e a nova quantidade será registrada como uma movimentação de estoque.
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check me-1"></i> Confirmar Ajuste
                            </button>
                            <a href="{{ route('provider.inventory.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x me-1"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
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
        const current = parseInt(quantityInput.value || '0', 10);
        quantityInput.value = (isNaN(current) ? 0 : current + 1);
    });

    // Decremento
    decBtn.addEventListener('click', function() {
        const current = parseInt(quantityInput.value || '0', 10);
        quantityInput.value = Math.max(0, isNaN(current) ? 0 : current - 1);
    });
});
</script>
@endpush
