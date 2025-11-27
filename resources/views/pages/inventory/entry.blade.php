@extends('layouts.app')

@section('title', 'Entrada de Estoque')

@section('content')
<div class="container-fluid py-1">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-arrow-down text-success me-2"></i>
                        Entrada de Estoque - {{ $product->name }}
                    </h3>
                </div>
                <form action="{{ route('provider.inventory.entry.store', $product->sku) }}" method="POST">
                    @csrf
                    <div class="card-body">
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

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Produto:</strong> {{ $product->name }}<br>
                            <strong>SKU:</strong> {{ $product->sku }}
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check me-1"></i> Confirmar Entrada
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
