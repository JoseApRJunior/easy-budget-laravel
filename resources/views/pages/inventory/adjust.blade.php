@extends('layouts.app')

@section('title', 'Ajustar Estoque - ' . $product->name)

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Ajuste de Estoque"
        icon="sliders"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Inventário' => route('provider.inventory.dashboard'),
            $product->name => route('provider.products.show', $product->sku),
            'Ajuste' => '#'
        ]">
        <p class="text-muted mb-0">Corrigir saldo ou divergências de estoque</p>
    </x-layout.page-header>

    <div class="row">
        <div class="col-12">
            <x-ui.card>
                <form action="{{ route('provider.inventory.adjust.store', $product->sku) }}" method="POST">
                    @csrf
                    <div class="p-2">
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
                                    <span class="badge bg-primary w-100 py-2 fs-6">{{ $inventory->quantity ?? 0 }}</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block mb-1">Reservado</small>
                                    <span class="badge bg-info w-100 py-2 fs-6 text-white">{{ $inventory->reserved_quantity ?? 0 }}</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block mb-1">Disponível</small>
                                    <span class="badge bg-success w-100 py-2 fs-6">{{ $inventory->available_quantity ?? 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <x-ui.form.input 
                                    label="Quantidade Atual" 
                                    :value="$inventory->quantity ?? 0" 
                                    disabled 
                                />
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="new_quantity" class="form-label fw-bold small text-muted text-uppercase">Nova Quantidade <span class="text-danger">*</span></label>
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
                                <x-ui.form.textarea 
                                    name="reason" 
                                    label="Motivo do Ajuste" 
                                    required 
                                    rows="3" 
                                    minlength="10" 
                                    maxlength="500" 
                                    placeholder="Descreva detalhadamente o motivo deste ajuste de estoque..."
                                    help="Mínimo 10 caracteres. Este motivo será registrado no histórico de movimentações."
                                >{{ old('reason') }}</x-ui.form.textarea>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Importante:</strong> Este ajuste irá alterar a quantidade atual do produto no estoque.
                            A diferença entre a quantidade atual e a nova quantidade será registrada como uma movimentação de estoque.
                        </div>
                    </div>

                    <div class="mt-4 p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <x-ui.back-button index-route="provider.inventory.index" />
                            <x-ui.button type="submit" variant="primary" icon="check-lg" label="Confirmar Ajuste" feature="inventory" />
                        </div>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-layout.page-container>
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
