@extends('layouts.app')

@section('title', 'Criar Compartilhamento')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-share me-2"></i>Criar Novo Compartilhamento
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('budget-share.store') }}" method="POST" id="createShareForm">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Selecionar Orçamento</h5>
                                
                                <div class="mb-3">
                                    <label for="budget_id" class="form-label">
                                        Orçamento <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('budget_id') is-invalid @enderror" 
                                            id="budget_id" name="budget_id" required>
                                        <option value="">Selecione um orçamento...</option>
                                        @foreach($budgets as $budget)
                                            <option value="{{ $budget->id }}" 
                                                    data-customer="{{ $budget->customer->name }}"
                                                    data-value="{{ $budget->total_value }}"
                                                    data-status="{{ $budget->status }}"
                                                    {{ old('budget_id') == $budget->id ? 'selected' : '' }}>
                                                #{{ str_pad($budget->id, 6, '0', STR_PAD_LEFT) }} - 
                                                {{ $budget->customer->name }} - 
                                                R$ {{ number_format($budget->total_value, 2, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('budget_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div id="budgetPreview" class="mb-3" style="display: none;">
                                    <label class="form-label fw-bold">Prévia do Orçamento</label>
                                    <div class="card border">
                                        <div class="card-body py-2">
                                            <div class="row text-sm">
                                                <div class="col-6">
                                                    <strong>Cliente:</strong>
                                                    <div id="previewCustomer" class="text-muted"></div>
                                                </div>
                                                <div class="col-6">
                                                    <strong>Valor:</strong>
                                                    <div id="previewValue" class="text-success fw-bold"></div>
                                                </div>
                                                <div class="col-6 mt-2">
                                                    <strong>Status:</strong>
                                                    <div id="previewStatus"></div>
                                                </div>
                                                <div class="col-6 mt-2">
                                                    <strong>Data:</strong>
                                                    <div id="previewDate" class="text-muted"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Configurações de Acesso</h5>
                                
                                <div class="mb-3">
                                    <label for="expires_at" class="form-label">Data de Expiração</label>
                                    <input type="datetime-local" 
                                           class="form-control @error('expires_at') is-invalid @enderror" 
                                           id="expires_at" name="expires_at" 
                                           value="{{ old('expires_at') }}">
                                    @error('expires_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Deixe em branco para sem expiração
                                    </small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Permissões de Acesso</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               id="can_view" name="permissions[can_view]" 
                                               value="1" {{ old('permissions.can_view', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="can_view">
                                            Pode visualizar o orçamento
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               id="can_approve" name="permissions[can_approve]" 
                                               value="1" {{ old('permissions.can_approve') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="can_approve">
                                            Pode aprovar o orçamento
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               id="can_comment" name="permissions[can_comment]" 
                                               value="1" {{ old('permissions.can_comment') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="can_comment">
                                            Pode adicionar comentários
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               id="can_print" name="permissions[can_print]" 
                                               value="1" {{ old('permissions.can_print', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="can_print">
                                            Pode imprimir o orçamento
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Observações</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" name="notes" rows="3" 
                                              placeholder="Informações adicionais sobre este compartilhamento...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Estas observações não serão visíveis para quem acessar o orçamento
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('budget-share.index') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-1"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-share me-1"></i>Criar Compartilhamento
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const budgetSelect = document.getElementById('budget_id');
    const budgetPreview = document.getElementById('budgetPreview');
    const previewCustomer = document.getElementById('previewCustomer');
    const previewValue = document.getElementById('previewValue');
    const previewStatus = document.getElementById('previewStatus');
    const previewDate = document.getElementById('previewDate');

    function updateBudgetPreview() {
        const selectedOption = budgetSelect.options[budgetSelect.selectedIndex];
        
        if (selectedOption.value) {
            const customer = selectedOption.getAttribute('data-customer');
            const value = selectedOption.getAttribute('data-value');
            const status = selectedOption.getAttribute('data-status');
            
            previewCustomer.textContent = customer;
            previewValue.textContent = 'R$ ' + parseFloat(value).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            
            const statusBadge = document.createElement('span');
            statusBadge.className = 'badge bg-' + (status === 'approved' ? 'success' : 
                                                   (status === 'pending' ? 'warning' : 'secondary'));
            statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            
            previewStatus.innerHTML = '';
            previewStatus.appendChild(statusBadge);
            
            const today = new Date();
            previewDate.textContent = today.toLocaleDateString('pt-BR') + ' ' + 
                                    today.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
            
            budgetPreview.style.display = 'block';
        } else {
            budgetPreview.style.display = 'none';
        }
    }

    budgetSelect.addEventListener('change', updateBudgetPreview);
    
    // Validação do formulário
    document.getElementById('createShareForm').addEventListener('submit', function(e) {
        const budgetId = document.getElementById('budget_id').value;
        const permissions = document.querySelectorAll('input[name^="permissions["]:checked');
        
        if (!budgetId) {
            e.preventDefault();
            showToast('Por favor, selecione um orçamento.', 'error');
            return;
        }
        
        if (permissions.length === 0) {
            e.preventDefault();
            showToast('Por favor, selecione pelo menos uma permissão.', 'error');
            return;
        }
    });
});

function showToast(message, type = 'info') {
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    const toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    toastContainer.innerHTML = toastHtml;
    document.body.appendChild(toastContainer);
    
    const toast = new bootstrap.Toast(toastContainer.querySelector('.toast'));
    toast.show();
    
    setTimeout(() => {
        document.body.removeChild(toastContainer);
    }, 3000);
}

// Definir data mínima para expiração (hoje)
document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    
    const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
    document.getElementById('expires_at').min = minDateTime;
});
</script>
@endsection