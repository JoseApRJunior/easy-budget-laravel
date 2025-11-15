@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-arrow-down text-success"></i> Entrada de Produtos no Estoque
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('inventory.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Voltar ao Inventário
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('inventory.store-entry') }}" id="entryForm">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">Dados da Entrada</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="product_id">Produto *</label>
                                                    <select name="product_id" id="product_id" class="form-control @error('product_id') is-invalid @enderror" required>
                                                        <option value="">Selecione um produto...</option>
                                                        @foreach($products as $product)
                                                            <option value="{{ $product->id }}" 
                                                                    data-current-quantity="{{ $product->inventory ? $product->inventory->current_quantity : 0 }}"
                                                                    data-unit-value="{{ $product->inventory ? $product->inventory->unit_value : $product->sale_price }}"
                                                                    {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                                                {{ $product->name }} ({{ $product->code }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('product_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="quantity">Quantidade *</label>
                                                    <input type="number" name="quantity" id="quantity" 
                                                           class="form-control @error('quantity') is-invalid @enderror" 
                                                           value="{{ old('quantity', 1) }}" min="1" required>
                                                    @error('quantity')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="unit_value">Valor Unitário (R$) *</label>
                                                    <input type="number" name="unit_value" id="unit_value" 
                                                           class="form-control @error('unit_value') is-invalid @enderror" 
                                                           value="{{ old('unit_value') }}" step="0.01" min="0.01" required>
                                                    @error('unit_value')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="total_value">Valor Total (R$)</label>
                                                    <input type="number" name="total_value" id="total_value" 
                                                           class="form-control" value="{{ old('total_value') }}" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="supplier_id">Fornecedor</label>
                                                    <select name="supplier_id" id="supplier_id" class="form-control @error('supplier_id') is-invalid @enderror">
                                                        <option value="">Selecione um fornecedor...</option>
                                                        @foreach($suppliers as $supplier)
                                                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                                {{ $supplier->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('supplier_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="invoice_number">Número da Nota Fiscal</label>
                                                    <input type="text" name="invoice_number" id="invoice_number" 
                                                           class="form-control @error('invoice_number') is-invalid @enderror" 
                                                           value="{{ old('invoice_number') }}">
                                                    @error('invoice_number')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="reason">Motivo/Observações *</label>
                                                    <textarea name="reason" id="reason" rows="3" 
                                                              class="form-control @error('reason') is-invalid @enderror" 
                                                              placeholder="Descreva o motivo da entrada (ex: Compra de estoque, Devolução, etc.)" required>{{ old('reason') }}</textarea>
                                                    @error('reason')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">Resumo da Entrada</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-info-circle"></i> Informações Atuais</h6>
                                            <div id="currentInfo" class="mt-2">
                                                <p><strong>Produto:</strong> <span id="selectedProductName">Nenhum produto selecionado</span></p>
                                                <p><strong>Estoque Atual:</strong> <span id="currentQuantity" class="badge badge-secondary">0</span></p>
                                                <p><strong>Valor Unitário Atual:</strong> <span id="currentUnitValue">R$ 0,00</span></p>
                                            </div>
                                        </div>

                                        <div class="alert alert-success">
                                            <h6><i class="fas fa-chart-line"></i> Previsão Após Entrada</h6>
                                            <div class="mt-2">
                                                <p><strong>Novo Estoque:</strong> <span id="newQuantity" class="badge badge-success">0</span></p>
                                                <p><strong>Valor Total da Entrada:</strong> <span id="entryTotalValue">R$ 0,00</span></p>
                                                <p><strong>Custo Médio:</strong> <span id="averageCost">R$ 0,00</span></p>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" class="btn btn-success btn-block btn-lg" id="submitBtn">
                                                <i class="fas fa-save"></i> Confirmar Entrada
                                            </button>
                                        </div>

                                        <div class="text-center">
                                            <a href="{{ route('inventory.index') }}" class="btn btn-secondary btn-block">
                                                <i class="fas fa-times"></i> Cancelar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .card-header.bg-success {
        background-color: #28a745 !important;
    }
    .card-header.bg-info {
        background-color: #17a2b8 !important;
    }
    .alert {
        margin-bottom: 1rem;
    }
    .badge {
        font-size: 1em;
        padding: 0.5em 0.75em;
    }
    #submitBtn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>
@stop

@section('js')
<script>
    $(document).ready(function() {
        // Inicializar select2 para melhor usabilidade
        $('#product_id').select2({
            theme: 'bootstrap4',
            placeholder: 'Selecione um produto...',
            allowClear: true
        });

        $('#supplier_id').select2({
            theme: 'bootstrap4',
            placeholder: 'Selecione um fornecedor...',
            allowClear: true
        });

        // Atualizar informações quando produto for selecionado
        $('#product_id').on('change', function() {
            updateProductInfo();
            calculateValues();
        });

        // Calcular valores quando quantidade ou valor unitário mudar
        $('#quantity, #unit_value').on('input', function() {
            calculateValues();
        });

        // Validação do formulário
        $('#entryForm').on('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }

            $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processando...');
        });
    });

    function updateProductInfo() {
        const selectedOption = $('#product_id option:selected');
        const productName = selectedOption.text().split(' (')[0];
        const currentQuantity = selectedOption.data('current-quantity') || 0;
        const unitValue = selectedOption.data('unit-value') || 0;

        $('#selectedProductName').text(productName);
        $('#currentQuantity').text(currentQuantity).removeClass('badge-secondary badge-warning badge-danger')
            .addClass(currentQuantity <= 0 ? 'badge-danger' : currentQuantity <= 10 ? 'badge-warning' : 'badge-secondary');
        $('#currentUnitValue').text('R$ ' + parseFloat(unitValue).toFixed(2).replace('.', ','));

        // Preencher valor unitário sugerido
        if ($('#unit_value').val() === '' || $('#unit_value').val() === '0.00') {
            $('#unit_value').val(parseFloat(unitValue).toFixed(2));
        }
    }

    function calculateValues() {
        const quantity = parseInt($('#quantity').val()) || 0;
        const unitValue = parseFloat($('#unit_value').val()) || 0;
        const currentQuantity = parseInt($('#currentQuantity').text()) || 0;

        const totalValue = quantity * unitValue;
        const newQuantity = currentQuantity + quantity;
        const averageCost = newQuantity > 0 ? totalValue / newQuantity : 0;

        $('#total_value').val(totalValue.toFixed(2));
        $('#entryTotalValue').text('R$ ' + totalValue.toFixed(2).replace('.', ','));
        $('#newQuantity').text(newQuantity);
        $('#averageCost').text('R$ ' + averageCost.toFixed(2).replace('.', ','));
    }

    function validateForm() {
        const productId = $('#product_id').val();
        const quantity = parseInt($('#quantity').val());
        const unitValue = parseFloat($('#unit_value').val());
        const reason = $('#reason').val().trim();

        if (!productId) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Por favor, selecione um produto.'
            });
            $('#product_id').focus();
            return false;
        }

        if (!quantity || quantity <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Por favor, informe uma quantidade válida.'
            });
            $('#quantity').focus();
            return false;
        }

        if (!unitValue || unitValue <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Por favor, informe um valor unitário válido.'
            });
            $('#unit_value').focus();
            return false;
        }

        if (!reason) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Por favor, informe o motivo da entrada.'
            });
            $('#reason').focus();
            return false;
        }

        return true;
    }
</script>
@stop