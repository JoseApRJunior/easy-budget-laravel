@extends('layouts.admin')

@section('title', 'Ajustar Estoque - ' . $product->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Ajustar Estoque</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('inventory.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventário</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory.show', $product) }}">{{ $product->name }}</a></li>
                    <li class="breadcrumb-item active">Ajustar Estoque</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informações do Produto</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>SKU:</strong> {{ $product->sku }}</p>
                            <p><strong>Nome:</strong> {{ $product->name }}</p>
                            <p><strong>Categoria:</strong> {{ $product->category->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Estoque Atual:</strong> 
                                <span class="badge badge-primary">{{ $inventory->quantity ?? 0 }}</span>
                            </p>
                            <p><strong>Estoque Mínimo:</strong> 
                                <span class="badge badge-warning">{{ $inventory->min_quantity ?? 0 }}</span>
                            </p>
                            @if($inventory && $inventory->max_quantity)
                                <p><strong>Estoque Máximo:</strong> 
                                    <span class="badge badge-info">{{ $inventory->max_quantity }}</span>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Ajuste de Estoque</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('inventory.adjust', $product) }}" method="POST">
                        @csrf
                        
                        <div class="form-group">
                            <label for="current_quantity">Quantidade Atual</label>
                            <input type="number" class="form-control" value="{{ $inventory->quantity ?? 0 }}" disabled>
                        </div>

                        <div class="form-group">
                            <label for="new_quantity">Nova Quantidade <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('new_quantity') is-invalid @enderror" 
                                   id="new_quantity" 
                                   name="new_quantity" 
                                   value="{{ old('new_quantity', $inventory->quantity ?? 0) }}"
                                   min="0"
                                   required>
                            @error('new_quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Digite a quantidade que deseja definir como estoque atual
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="reason">Motivo do Ajuste <span class="text-danger">*</span></label>
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

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Importante:</strong> Este ajuste irá alterar a quantidade atual do produto no estoque. 
                            A diferença entre a quantidade atual e a nova quantidade será registrada como uma movimentação de estoque.
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Confirmar Ajuste
                            </button>
                            <a href="{{ route('inventory.show', $product) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Resumo da Operação</h3>
                </div>
                <div class="card-body">
                    <p><strong>Produto:</strong> {{ $product->name }}</p>
                    <p><strong>Quantidade Atual:</strong> <span id="current-qty">{{ $inventory->quantity ?? 0 }}</span></p>
                    <p><strong>Nova Quantidade:</strong> <span id="new-qty">{{ old('new_quantity', $inventory->quantity ?? 0) }}</span></p>
                    <hr>
                    <p><strong>Diferença:</strong> <span id="difference" class="badge">0</span></p>
                    
                    <div id="difference-info" class="mt-3">
                        <!-- JavaScript will populate this -->
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Últimas Movimentações</h3>
                </div>
                <div class="card-body">
                    @php
                        $recentMovements = \App\Models\InventoryMovement::where('product_id', $product->id)
                            ->where('tenant_id', auth()->user()->tenant_id)
                            ->orderBy('created_at', 'desc')
                            ->limit(5)
                            ->get();
                    @endphp
                    
                    @if($recentMovements->count() > 0)
                        <div class="timeline">
                            @foreach($recentMovements as $movement)
                                <div class="timeline-item">
                                    <div class="timeline-item-marker">
                                        <div class="timeline-item-marker-text">{{ $movement->created_at->format('d/m') }}</div>
                                        <div class="timeline-item-marker-indicator 
                                            {{ $movement->type === 'entry' ? 'bg-success' : 'bg-warning' }}"></div>
                                    </div>
                                    <div class="timeline-item-content">
                                        {{ ucfirst($movement->type) }}: {{ $movement->quantity }}
                                        <br>
                                        <small class="text-muted">{{ $movement->reason }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">Nenhuma movimentação recente.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentQty = {{ $inventory->quantity ?? 0 }};
    const newQtyInput = document.getElementById('new_quantity');
    const newQtySpan = document.getElementById('new-qty');
    const differenceSpan = document.getElementById('difference');
    const differenceInfo = document.getElementById('difference-info');

    function updateSummary() {
        const newQty = parseFloat(newQtyInput.value) || 0;
        const difference = newQty - currentQty;
        
        newQtySpan.textContent = newQty;
        differenceSpan.textContent = difference > 0 ? `+${difference}` : difference;
        
        if (difference > 0) {
            differenceSpan.className = 'badge badge-success';
            differenceInfo.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-arrow-up"></i> 
                    <strong>Entrada de Estoque:</strong> ${difference} unidades serão adicionadas ao estoque.
                </div>
            `;
        } else if (difference < 0) {
            differenceSpan.className = 'badge badge-danger';
            differenceInfo.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-arrow-down"></i> 
                    <strong>Saída de Estoque:</strong> ${Math.abs(difference)} unidades serão removidas do estoque.
                </div>
            `;
        } else {
            differenceSpan.className = 'badge badge-secondary';
            differenceInfo.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-minus"></i> 
                    <strong>Sem Alteração:</strong> O estoque permanecerá inalterado.
                </div>
            `;
        }
    }

    newQtyInput.addEventListener('input', updateSummary);
    updateSummary(); // Initial update
});
</script>
@endsection