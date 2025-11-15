@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-arrow-up text-danger"></i> Saída de Produtos do Estoque
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('inventory.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Voltar ao Inventário
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('inventory.store-exit') }}" id="exitForm">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header bg-danger text-white">
                                        <h5 class="mb-0">Dados da Saída</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="product_id">Produto *</label>
                                                    <select name="product_id" id="product_id" class="form-control @error('product_id') is-invalid @enderror" required>
                                                        <option value="">Selecione um produto...</option>
                                                        @foreach($products as $product)
                                                            @if($product->inventory && $product->inventory->current_quantity > 0)
                                                                <option value="{{ $product->id }}" 
                                                                        data-current-quantity="{{ $product->inventory->current_quantity }}"
                                                                        data-unit-value="{{ $product->inventory->unit_value }}"
                                                                        data-min-quantity="{{ $product->inventory->minimum_quantity }}"
                                                                        {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                                                    {{ $product->name }} ({{ $product->code }}) - Estoque: {{ $product->inventory->current_quantity }}
                                                                </option>
                                                            @endif
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
                                                    <small class="form-text text-muted" id="quantityHelp">
                                                        Máximo disponível: <span id="maxQuantity">0</span>
                                                    </small>
                                                    @error('quantity')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="service_id">Serviço Relacionado</label>
                                                    <select name="service_id" id="service_id" class="form-control @error('service_id') is-invalid @enderror">
                                                        <option value="">Selecione um serviço...</option>
                                                        @foreach($services as $service)
                                                            <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>
                                                                #{{ $service->id }} - {{ $service->customer->name }} - {{ $service->serviceType->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="form-text text-muted">
                        Vincular esta saída a um serviço específico
                                                    </small>
                                                    @error('service_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="exit_date">Data da Saída</label>
                                                    <input type="date" name="exit_date" id="exit_date" 
                                                           class="form-control @error('exit_date') is-invalid @enderror" 
                                                           value="{{ old('exit_date', date('Y-m-d')) }}">
                                                    @error('exit_date')
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
                                                              placeholder="Descreva o motivo da saída (ex: Uso em serviço, Venda, Perda, etc.)" required>{{ old('reason') }}</textarea>
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
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0">Resumo da Saída</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-info-circle"></i> Informações Atuais</h6>
                                            <div id="currentInfo" class="mt-2">
                                                <p><strong>Produto:</strong> <span id="selectedProductName">Nenhum produto selecionado</span></p>
                                                <p><strong>Estoque Atual:</strong> <span id="currentQuantity" class="badge badge-secondary">0</span></p>
                                                <p><strong>Estoque Mínimo:</strong> <span id="minimumQuantity" class="badge badge-warning">0</span></p>
                                                <p><strong>Valor Unitário:</strong> <span id="currentUnitValue">R$ 0,00</span></p>
                                            </div>
                                        </div>

                                        <div class="alert alert-warning">
                                            <h6><i class="fas fa-exclamation-triangle"></i> Previsão Após Saída</h6>
                                            <div class="mt-2">
                                                <p><strong>Novo Estoque:</strong> <span id="newQuantity" class="badge badge-danger">0</span></p>
                                                <p><strong>Status:</strong> <span id="stockStatus" class="badge badge-success">OK</span></p>
                                                <p><strong>Valor Total da Saída:</strong> <span id="exitTotalValue">R$ 0,00</span></p>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" class="btn btn-danger btn-block btn-lg" id="submitBtn">
                                                <i class="fas fa-save"></i> Confirmar Saída
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
    .card-header.bg-danger {
        background-color: #dc3545 !important;
    }
    .card-header.bg-warning {
        background-color: #ffc107 !important;
        color: #212529 !important;
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
    .form-text.text-muted {
        font-size: 0.8em;
        margin-top: 0.25rem;
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

        $('#service_id').select2({
            theme: 'bootstrap4',
            placeholder: 'Selecione um serviço...',
            allowClear: true
        });

        // Atualizar informações quando produto for selecionado
        $('#product_id').on('change', function() {
            updateProductInfo();
            calculateValues();
        });

        // Calcular valores quando quantidade mudar
        $('#quantity').on('input', function() {
            calculateValues();
        });

        // Validação do formulário
        $('#exitForm').on('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }

            $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processando...');
        });
    });

    function updateProductInfo() {
        const selectedOption = $('#product_id option:selected');
        const productName = selectedOption.text().split(' - Estoque:')[0];
        const currentQuantity = parseInt(selectedOption.data('current-quantity')) || 0;
        const unitValue = parseFloat(selectedOption.data('unit-value')) || 0;
        const minQuantity = parseInt(selectedOption.data('min-quantity')) || 0;

        $('#selectedProductName').text(productName);
        $('#currentQuantity').text(currentQuantity).removeClass('badge-secondary badge-warning badge-danger')
            .addClass(currentQuantity <= 0 ? 'badge-danger' : currentQuantity <= minQuantity ? 'badge-warning' : 'badge-secondary');
        $('#minimumQuantity').text(minQuantity);
        $('#currentUnitValue').text('R$ ' + unitValue.toFixed(2).replace('.', ','));
        $('#maxQuantity').text(currentQuantity);

        // Atualizar validação da quantidade
        $('#quantity').attr('max', currentQuantity);
        if (parseInt($('#quantity').val()) > currentQuantity) {
            $('#quantity').val(currentQuantity);
        }

        calculateValues();
    }

    function calculateValues() {
        const quantity = parseInt($('#quantity').val()) || 0;
        const unitValue = parseFloat($('#currentUnitValue').text().replace('R$ ', '').replace(',', '.')) || 0;
        const currentQuantity = parseInt($('#currentQuantity').text()) || 0;
        const minQuantity = parseInt($('#minimumQuantity').text()) || 0;

        const totalValue = quantity * unitValue;
        const newQuantity = currentQuantity - quantity;

        $('#exitTotalValue').text('R$ ' + totalValue.toFixed(2).replace('.', ','));
        $('#newQuantity').text(newQuantity).removeClass('badge-success badge-warning badge-danger')
            .addClass(newQuantity <= 0 ? 'badge-danger' : newQuantity <= minQuantity ? 'badge-warning' : 'badge-success');

        // Atualizar status do estoque
        let statusText, statusClass;
        if (newQuantity <= 0) {
            statusText = 'SEM ESTOQUE';
            statusClass = 'badge-danger';
        } else if (newQuantity <= minQuantity) {
            statusText = 'ESTOQUE BAIXO';
            statusClass = 'badge-warning';
        } else {
            statusText = 'OK';
            statusClass = 'badge-success';
        }
        $('#stockStatus').text(statusText).removeClass('badge-success badge-warning badge-danger').addClass(statusClass);
    }

    function validateForm() {
        const productId = $('#product_id').val();
        const quantity = parseInt($('#quantity').val());
        const currentQuantity = parseInt($('#currentQuantity').text()) || 0;
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

        if (quantity > currentQuantity) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Quantidade solicitada maior que o estoque disponível.'
            });
            $('#quantity').focus();
            return false;
        }

        if (!reason) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Por favor, informe o motivo da saída.'
            });
            $('#reason').focus();
            return false;
        }

        // Confirmação adicional para estoque baixo
        const newQuantity = currentQuantity - quantity;
        const minQuantity = parseInt($('#minimumQuantity').text()) || 0;
        
        if (newQuantity <= minQuantity) {
            let warningMessage = newQuantity <= 0 ? 
                'Esta saída deixará o produto SEM ESTOQUE. Deseja continuar?' :
                'Esta saída deixará o produto com ESTOQUE BAIXO. Deseja continuar?';

            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: warningMessage,
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, continuar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#exitForm').off('submit').submit();
                }
            });
            return false;
        }

        return true;
    }
</script>
@stop