@extends('layouts.admin')

@section('title', 'Ajustar Estoque - ' . $product->name)

@section('content')
<div class="container-fluid">
    <x-page-header
        title="Ajustar Estoque"
        icon="plus-slash-minus"
        :breadcrumb-items="[
            'Inventário' => route('provider.inventory.dashboard'),
            'Produtos' => route('provider.inventory.index'),
            'Ajustar Estoque' => '#'
        ]">
    </x-page-header>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ajustar Estoque - {{ $product->name }}</h3>
                </div>
                <div class="card-body">
                    <!-- Informações do Produto -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>SKU:</strong> {{ $product->sku }}<br>
                            <strong>Nome:</strong> {{ $product->name }}<br>
                            <strong>Categoria:</strong> {{ $product->category->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Estoque Atual:</strong> 
                            <span class="badge badge-primary">{{ $inventory->quantity ?? 0 }} {{ $product->unit }}</span><br>
                            <strong>Estoque Mínimo:</strong> 
                            <span class="badge badge-warning">{{ $inventory->min_quantity ?? 0 }} {{ $product->unit }}</span><br>
                            <strong>Estoque Máximo:</strong> 
                            <span class="badge badge-info">{{ $inventory->max_quantity ?? 'N/A' }} {{ $product->unit }}</span>
                        </div>
                    </div>

                    <!-- Formulário de Ajuste -->
                    <form action="{{ route('provider.inventory.adjust.store', $product) }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="adjustment_type">Tipo de Ajuste *</label>
                                    <select name="adjustment_type" id="adjustment_type" class="form-control @error('adjustment_type') is-invalid @enderror" required>
                                        <option value="">Selecione o tipo</option>
                                        <option value="addition" {{ old('adjustment_type') == 'addition' ? 'selected' : '' }}>
                                            Adicionar Estoque
                                        </option>
                                        <option value="subtraction" {{ old('adjustment_type') == 'subtraction' ? 'selected' : '' }}>
                                            Remover Estoque
                                        </option>
                                        <option value="correction" {{ old('adjustment_type') == 'correction' ? 'selected' : '' }}>
                                            Correção de Estoque
                                        </option>
                                    </select>
                                    @error('adjustment_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="quantity">Quantidade *</label>
                                    <input type="number" 
                                           name="quantity" 
                                           id="quantity" 
                                           class="form-control @error('quantity') is-invalid @enderror" 
                                           value="{{ old('quantity') }}" 
                                           step="0.01" 
                                           min="0.01"
                                           required>
                                    <small class="form-text text-muted">Unidade: {{ $product->unit }}</small>
                                    @error('quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="reason">Motivo do Ajuste *</label>
                                    <textarea name="reason" 
                                              id="reason" 
                                              class="form-control @error('reason') is-invalid @enderror" 
                                              rows="3" 
                                              required
                                              placeholder="Descreva o motivo do ajuste de estoque...">{{ old('reason') }}</textarea>
                                    @error('reason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h5><i class="fas fa-info-circle"></i> Importante</h5>
                                    <ul class="mb-0">
                                        <li>Ajustes de estoque serão registrados no histórico de movimentações</li>
                                        <li>O usuário responsável será registrado automaticamente</li>
                                        <li>Verifique sempre o estoque atual antes de confirmar o ajuste</li>
                                        <li>Para correções, informe o motivo detalhadamente</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Confirmar Ajuste
                                </button>
                                <a href="{{ route('provider.inventory.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Histórico de Movimentações -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Movimentações Recentes</h3>
                </div>
                <div class="card-body">
                    @if($recentMovements->count() > 0)
                        <div class="timeline">
                            @foreach($recentMovements as $movement)
                                <div class="time-label">
                                    <span class="bg-info">{{ $movement->created_at->format('d/m/Y') }}</span>
                                </div>
                                <div>
                                    @if($movement->type === 'entry')
                                        <i class="fas fa-plus bg-success"></i>
                                    @elseif($movement->type === 'exit')
                                        <i class="fas fa-minus bg-danger"></i>
                                    @else
                                        <i class="fas fa-exchange-alt bg-warning"></i>
                                    @endif
                                    <div class="timeline-item">
                                        <span class="time"><i class="fas fa-clock"></i> {{ $movement->created_at->format('H:i') }}</span>
                                        <h3 class="timeline-header">
                                            {{ ucfirst($movement->type) }}: {{ $movement->quantity }} {{ $product->unit }}
                                        </h3>
                                        <div class="timeline-body">
                                            {{ $movement->reason }}<br>
                                            <small class="text-muted">Por: {{ $movement->user->name ?? 'Sistema' }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Nenhuma movimentação recente.
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('provider.inventory.movements', $product) }}" class="btn btn-block btn-outline-primary">
                        Ver Todas as Movimentações
                    </a>
                </div>
            </div>

            <!-- Estatísticas -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Estatísticas</h3>
                </div>
                <div class="card-body">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-box"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Estoque Atual</span>
                            <span class="info-box-number">{{ $inventory->quantity ?? 0 }} {{ $product->unit }}</span>
                        </div>
                    </div>

                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Estoque Mínimo</span>
                            <span class="info-box-number">{{ $inventory->min_quantity ?? 0 }} {{ $product->unit }}</span>
                        </div>
                    </div>

                    @if($inventory && $inventory->max_quantity)
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-arrow-up"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Estoque Máximo</span>
                                <span class="info-box-number">{{ $inventory->max_quantity }} {{ $product->unit }}</span>
                            </div>
                        </div>
                    @endif

                    <div class="info-box">
                        <span class="info-box-icon bg-primary"><i class="fas fa-calendar"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Última Movimentação</span>
                            <span class="info-box-number">
                                @if($lastMovement)
                                    {{ $lastMovement->created_at->diffForHumans() }}
                                @else
                                    Nunca
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Validação do formulário
    $('#quantity').on('input', function() {
        var quantity = parseFloat($(this).val());
        var adjustmentType = $('#adjustment_type').val();
        var currentStock = {{ $inventory->quantity ?? 0 }};
        
        if (adjustmentType === 'subtraction' && quantity > currentStock) {
            if (window.easyAlert) {
                window.easyAlert.warning('Atenção: A quantidade a remover é maior que o estoque atual!');
            } else {
                alert('Atenção: A quantidade a remover é maior que o estoque atual!');
            }
        }
    });

    $('#adjustment_type').on('change', function() {
        var type = $(this).val();
        var quantityField = $('#quantity');
        
        if (type === 'subtraction') {
            quantityField.attr('max', {{ $inventory->quantity ?? 0 }});
        } else {
            quantityField.removeAttr('max');
        }
    });
});
</script>
@endsection