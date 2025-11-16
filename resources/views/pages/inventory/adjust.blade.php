@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-sliders-h text-warning"></i> Ajuste de Estoque
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('provider.inventory.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Voltar ao Inventário
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('provider.inventory.store-adjustment') }}" id="adjustForm">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0">Dados do Ajuste</h5>
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
                                                                    data-min-quantity="{{ $product->inventory ? $product->inventory->minimum_quantity : 0 }}"
                                                                    {{ request('product_id') == $product->id || old('product_id') == $product->id ? 'selected' : '' }}>
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
                                                    <label for="adjustment_type">Tipo de Ajuste *</label>
                                                    <select name="adjustment_type" id="adjustment_type" class="form-control @error('adjustment_type') is-invalid @enderror" required>
                                                        <option value="">Selecione o tipo...</option>
                                                        <option value="positive" {{ old('adjustment_type') == 'positive' ? 'selected' : '' }}>
                                                            Ajuste Positivo (+)
                                                        </option>
                                                        <option value="negative" {{ old('adjustment_type') == 'negative' ? 'selected' : '' }}>
                                                            Ajuste Negativo (-)
                                                        </option>
                                                        <option value="value" {{ old('adjustment_type') == 'value' ? 'selected' : '' }}>
                                                            Ajuste de Valor Unitário
                                                        </option>
                                                    </select>
                                                    @error('adjustment_type')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row" id="quantityAdjustmentRow" style="display: none;">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="quantity_adjustment">Quantidade de Ajuste *</label>
                                                    <input type="number" name="quantity_adjustment" id="quantity_adjustment" 
                                                           class="form-control @error('quantity_adjustment') is-invalid @enderror" 
                                                           value="{{ old('quantity_adjustment', 1) }}" min="1">
                                                    <small class="form-text text-muted" id="quantityAdjustmentHelp"></small>
                                                    @error('quantity_adjustment')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="new_quantity">Novo Estoque</label>
                                                    <input type="number" name="new_quantity" id="new_quantity" 
                                                           class="form-control" value="{{ old('new_quantity') }}" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row" id="valueAdjustmentRow" style="display: none;">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="new_unit_value">Novo Valor Unitário (R$) *</label>
                                                    <input type="number" name="new_unit_value" id="new_unit_value" 
                                                           class="form-control @error('new_unit_value') is-invalid @enderror" 
                                                           value="{{ old('new_unit_value') }}" step="0.01" min="0.01">
                                                    @error('new_unit_value')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="current_unit_value">Valor Unitário Atual</label>
                                                    <input type="number" name="current_unit_value" id="current_unit_value" 
                                                           class="form-control" value="{{ old('current_unit_value') }}" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="reason">Motivo do Ajuste *</label>
                                                    <textarea name="reason" id="reason" rows="3" 
                                                              class="form-control @error('reason') is-invalid @enderror" 
                                                              placeholder="Descreva detalhadamente o motivo do ajuste (ex: Correção de inventário, Perda de estoque, Encontrado em auditoria, etc.)" required>{{ old('reason') }}</textarea>
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
                                        <h5 class="mb-0">Resumo do Ajuste</h5>
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

                                        <div class="alert alert-warning" id="adjustmentPreview" style="display: none;">
                                            <h6><i class="fas fa-chart-line"></i> Previsão do Ajuste</h6>
                                            <div class="mt-2" id="adjustmentDetails">
                                                <!-- Conteúdo será preenchido dinamicamente -->
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" class="btn btn-warning btn-block btn-lg" id="submitBtn" disabled>
                                                <i class="fas fa-save"></i> Confirmar Ajuste
                                            </button>
                                        </div>

                                        <div class="text-center">
                                            <a href="{{ route('provider.inventory.index') }}" class="btn btn-secondary btn-block">
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
    .card-header.bg-warning {
        background-color: #ffc107 !important;
        color: #212529 !important;
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

        // Atualizar informações quando produto for selecionado
        $('#product_id').on('change', function() {
            updateProductInfo();
            updateAdjustmentPreview();
        });

        // Mostrar/ocultar campos baseado no tipo de ajuste
        $('#adjustment_type').on('change', function() {
            const adjustmentType = $(this).val();
            showAdjustmentFields(adjustmentType);
            updateAdjustmentPreview();
        });

        // Atualizar preview quando valores mudarem
        $('#quantity_adjustment, #new_unit_value').on('input', function() {
            updateAdjustmentPreview();
        });

        // Validação do formulário
        $('#adjustForm').on('submit', function(e) {
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
        const currentQuantity = parseInt(selectedOption.data('current-quantity')) || 0;
        const unitValue = parseFloat(selectedOption.data('unit-value')) || 0;
        const minQuantity = parseInt(selectedOption.data('min-quantity')) || 0;

        $('#selectedProductName').text(productName);
        $('#currentQuantity').text(currentQuantity).removeClass('badge-secondary badge-warning badge-danger')
            .addClass(currentQuantity <= 0 ? 'badge-danger' : currentQuantity <= minQuantity ? 'badge-warning' : 'badge-secondary');
        $('#minimumQuantity').text(minQuantity);
        $('#currentUnitValue').text('R$ ' + unitValue.toFixed(2).replace('.', ','));
        $('#current_unit_value').val(unitValue.toFixed(2));
        $('#new_unit_value').val(unitValue.toFixed(2));

        updateAdjustmentPreview();
    }

    function showAdjustmentFields(type) {
        $('#quantityAdjustmentRow, #valueAdjustmentRow').hide();
        
        if (type === 'positive' || type === 'negative') {
            $('#quantityAdjustmentRow').show();
            const label = type === 'positive' ? 'Quantidade a Adicionar' : 'Quantidade a Remover';
            $('#quantity_adjustment').prev('label').text(label + ' *');
            $('#quantityAdjustmentHelp').text(type === 'positive' ? 
                'Estoque será aumentado' : 'Estoque será diminuído');
        } else if (type === 'value') {
            $('#valueAdjustmentRow').show();
        }
    }

    function updateAdjustmentPreview() {
        const productId = $('#product_id').val();
        const adjustmentType = $('#adjustment_type').val();
        const currentQuantity = parseInt($('#currentQuantity').text()) || 0;
        const currentUnitValue = parseFloat($('#currentUnitValue').text().replace('R$ ', '').replace(',', '.')) || 0;

        if (!productId || !adjustmentType) {
            $('#adjustmentPreview').hide();
            $('#submitBtn').prop('disabled', true);
            return;
        }

        let previewHtml = '';
        let isValid = false;

        if (adjustmentType === 'positive' || adjustmentType === 'negative') {
            const quantityAdjustment = parseInt($('#quantity_adjustment').val()) || 0;
            
            if (quantityAdjustment > 0) {
                const newQuantity = adjustmentType === 'positive' ? 
                    currentQuantity + quantityAdjustment : 
                    currentQuantity - quantityAdjustment;
                
                const adjustmentLabel = adjustmentType === 'positive' ? 'Adicionar' : 'Remover';
                
                previewHtml = `
                    <p><strong>Tipo:</strong> Ajuste ${adjustmentLabel === 'Adicionar' ? 'Positivo' : 'Negativo'}</p>
                    <p><strong>Quantidade:</strong> ${quantityAdjustment}</p>
                    <p><strong>Estoque Atual:</strong> <span class="badge badge-secondary">${currentQuantity}</span></p>
                    <p><strong>Novo Estoque:</strong> <span class="badge badge-${newQuantity < 0 ? 'danger' : 'success'}">${newQuantity}</span></p>
                `;
                
                isValid = newQuantity >= 0; // Não permitir estoque negativo
            }
        } else if (adjustmentType === 'value') {
            const newUnitValue = parseFloat($('#new_unit_value').val()) || 0;
            
            if (newUnitValue > 0) {
                const valueDifference = newUnitValue - currentUnitValue;
                const differenceLabel = valueDifference >= 0 ? 'Aumento' : 'Redução';
                
                previewHtml = `
                    <p><strong>Tipo:</strong> Ajuste de Valor Unitário</p>
                    <p><strong>Valor Atual:</strong> R$ ${currentUnitValue.toFixed(2).replace('.', ',')}</p>
                    <p><strong>Novo Valor:</strong> R$ ${newUnitValue.toFixed(2).replace('.', ',')}</p>
                    <p><strong>${differenceLabel}:</strong> R$ ${Math.abs(valueDifference).toFixed(2).replace('.', ',')}</p>
                `;
                
                isValid = true;
            }
        }

        if (previewHtml) {
            $('#adjustmentDetails').html(previewHtml);
            $('#adjustmentPreview').show();
            $('#submitBtn').prop('disabled', !isValid);
        } else {
            $('#adjustmentPreview').hide();
            $('#submitBtn').prop('disabled', true);
        }
    }

    function validateForm() {
        const productId = $('#product_id').val();
        const adjustmentType = $('#adjustment_type').val();
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

        if (!adjustmentType) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Por favor, selecione o tipo de ajuste.'
            });
            $('#adjustment_type').focus();
            return false;
        }

        if (adjustmentType === 'positive' || adjustmentType === 'negative') {
            const quantityAdjustment = parseInt($('#quantity_adjustment').val());
            if (!quantityAdjustment || quantityAdjustment <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Por favor, informe uma quantidade de ajuste válida.'
                });
                $('#quantity_adjustment').focus();
                return false;
            }

            // Validar estoque negativo para ajuste negativo
            if (adjustmentType === 'negative') {
                const currentQuantity = parseInt($('#currentQuantity').text()) || 0;
                if (quantityAdjustment > currentQuantity) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Atenção',
                        text: 'Quantidade de ajuste maior que o estoque disponível.'
                    });
                    $('#quantity_adjustment').focus();
                    return false;
                }
            }
        } else if (adjustmentType === 'value') {
            const newUnitValue = parseFloat($('#new_unit_value').val());
            if (!newUnitValue || newUnitValue <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Por favor, informe um valor unitário válido.'
                });
                $('#new_unit_value').focus();
                return false;
            }
        }

        if (!reason) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Por favor, informe o motivo do ajuste.'
            });
            $('#reason').focus();
            return false;
        }

        return true;
    }
</script>
@stop