@extends('layouts.app')

@section('title', 'Editar Compartilhamento')

@section('content')
<div class="container-fluid py-1">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-pencil me-2"></i>
            Editar Compartilhamento
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('provider.budgets.index') }}">Orçamentos</a></li>
                <li class="breadcrumb-item"><a href="{{ route('provider.budgets.shares.index') }}">Compartilhamentos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-pencil me-2"></i>Configurações do Compartilhamento
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('provider.budgets.shares.update', $share->id) }}" method="POST" id="editShareForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Informações Atuais</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Token de Acesso</label>
                                    @include('components.share-token', ['token' => $share->token])
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Orçamento Atual</label>
                                    <p class="form-control-plaintext">
                                        <a href="{{ route('budgets.show', $share->budget->id) }}" class="text-decoration-none">
                                            #{{ str_pad($share->budget->id, 6, '0', STR_PAD_LEFT) }} - 
                                            {{ $share->budget->customer->name }} - 
                                            R$ {{ number_format($share->budget->total_value, 2, ',', '.') }}
                                        </a>
                                    </p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Status Atual</label>
                                    @include('components.share-status-badge', ['share' => $share])
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Criado em</label>
                                    <p class="form-control-plaintext">
                                        {{ \Carbon\Carbon::parse($share->created_at)->format('d/m/Y H:i') }}
                                        <small class="text-muted">
                                            ({{ \Carbon\Carbon::parse($share->created_at)->diffForHumans() }})
                                        </small>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Alterar Configurações</h5>
                                
                                <div class="mb-3">
                                    <label for="budget_id" class="form-label">Alterar Orçamento</label>
                                    <select class="form-select @error('budget_id') is-invalid @enderror" 
                                            id="budget_id" name="budget_id">
                                        <option value="{{ $share->budget->id }}">Manter orçamento atual</option>
                                        @foreach($budgets as $budget)
                                            @if($budget->id !== $share->budget->id)
                                                <option value="{{ $budget->id }}" 
                                                        data-customer="{{ $budget->customer->name }}"
                                                        data-value="{{ $budget->total_value }}"
                                                        data-status="{{ $budget->status }}"
                                                        {{ old('budget_id') == $budget->id ? 'selected' : '' }}>
                                                    #{{ str_pad($budget->id, 6, '0', STR_PAD_LEFT) }} - 
                                                    {{ $budget->customer->name }} - 
                                                    R$ {{ number_format($budget->total_value, 2, ',', '.') }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('budget_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Selecione um novo orçamento para compartilhar
                                    </small>
                                </div>

                                <div id="budgetPreview" class="mb-3" style="display: none;">
                                    <label class="form-label fw-bold">Prévia do Novo Orçamento</label>
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

                                <div class="mb-3">
                                    <label for="expires_at" class="form-label">Data de Expiração</label>
                                    <input type="datetime-local" 
                                           class="form-control @error('expires_at') is-invalid @enderror" 
                                           id="expires_at" name="expires_at" 
                                           value="{{ old('expires_at', $share->expires_at ? \Carbon\Carbon::parse($share->expires_at)->format('Y-m-d\TH:i') : '') }}">
                                    @error('expires_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Deixe em branco para sem expiração
                                    </small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" 
                                               id="is_active" name="is_active" value="1" 
                                               {{ old('is_active', $share->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Compartilhamento ativo
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Desative para revogar temporariamente o acesso
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">Permissões de Acesso</h5>
                                
                                @php
                                    $currentPermissions = json_decode($share->permissions, true);
                                @endphp
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="can_view" name="permissions[can_view]" 
                                                   value="1" {{ old('permissions.can_view', $currentPermissions['can_view'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="can_view">
                                                Pode visualizar
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="can_approve" name="permissions[can_approve]" 
                                                   value="1" {{ old('permissions.can_approve', $currentPermissions['can_approve'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="can_approve">
                                                Pode aprovar
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="can_comment" name="permissions[can_comment]" 
                                                   value="1" {{ old('permissions.can_comment', $currentPermissions['can_comment'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="can_comment">
                                                Pode comentar
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="can_print" name="permissions[can_print]" 
                                                   value="1" {{ old('permissions.can_print', $currentPermissions['can_print'] ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="can_print">
                                                Pode imprimir
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @error('permissions')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Observações</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" name="notes" rows="3" 
                                              placeholder="Informações adicionais sobre este compartilhamento...">{{ old('notes', $share->notes) }}</textarea>
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
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('provider.budgets.shares.show', $share->id) }}" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left me-1"></i>Voltar
                                        </a>
                                        <button type="button" class="btn btn-outline-warning" onclick="regenerateToken()">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Regenerar Token
                                        </button>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                                            <i class="bi bi-trash me-1"></i>Excluir
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-1"></i>Salvar Alterações
                                        </button>
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

<!-- Modal de Regenerar Token -->
<div class="modal fade" id="regenerateTokenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Regeneração de Token</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja regenerar o token de acesso?</p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Atenção:</strong> O link antigo será invalidado e um novo será gerado.
                    Qualquer pessoa com o link antigo não conseguirá mais acessar o orçamento.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="regenerateForm" method="POST" style="display: inline;">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="btn btn-warning">Regenerar Token</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este compartilhamento?</p>
                <p class="text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Esta ação não pode ser desfeita. O link de acesso será permanentemente removido.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Excluir Compartilhamento</button>
                </form>
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
        
        if (selectedOption.value && selectedOption.value != {{ $share->budget->id }}) {
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
    document.getElementById('editShareForm').addEventListener('submit', function(e) {
        const permissions = document.querySelectorAll('input[name^="permissions["]:checked');
        
        if (permissions.length === 0) {
            e.preventDefault();
            showToast('Por favor, selecione pelo menos uma permissão.', 'error');
            return;
        }
    });
});

function regenerateToken() {
    const form = document.getElementById('regenerateForm');
    form.action = `/provider/budgets/shares/{{ $share->id }}/regenerate`;
    
    const modal = new bootstrap.Modal(document.getElementById('regenerateTokenModal'));
    modal.show();
}

function confirmDelete() {
    const form = document.getElementById('deleteForm');
    form.action = `/provider/budgets/shares/{{ $share->id }}`;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

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