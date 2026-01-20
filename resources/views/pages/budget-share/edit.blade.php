@extends('layouts.app')

@section('title', 'Editar Compartilhamento')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Editar Compartilhamento"
            icon="pencil"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Orçamentos' => route('provider.budgets.index'),
                'Compartilhamentos' => route('provider.budgets.shares.index'),
                'Editar' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button :href="route('provider.budgets.shares.index')" variant="secondary" outline icon="arrow-left" label="Voltar" />
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <div class="col-12">
                <x-ui.card>
                    <x-slot:header>
                        <h4 class="card-title mb-0 text-primary fw-bold">
                            <i class="bi bi-pencil me-2"></i>Configurações do Compartilhamento
                        </h4>
                    </x-slot:header>

                    <form action="{{ route('provider.budgets.shares.update', $share->id) }}" method="POST" id="editShareForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3 border-bottom pb-2">Informações Atuais</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-muted text-uppercase small">Token de Acesso</label>
                                    <div><code class="bg-light px-2 py-1 rounded">{{ $share->token }}</code></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold text-muted text-uppercase small">Orçamento Atual</label>
                                    <p class="form-control-plaintext">
                                        <a href="{{ route('provider.budgets.show', $share->budget->code) }}" class="text-decoration-none fw-bold">
                                            #{{ str_pad($share->budget->id, 6, '0', STR_PAD_LEFT) }} - 
                                            {{ $share->budget->customer->name }} - 
                                            R$ {{ number_format($share->budget->total_value, 2, ',', '.') }}
                                        </a>
                                    </p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold text-muted text-uppercase small">Status Atual</label>
                                    <div>
                                        @if($share->expires_at && \Carbon\Carbon::parse($share->expires_at)->isPast())
                                            <span class="badge bg-danger">Expirado</span>
                                        @elseif(!$share->is_active)
                                            <span class="badge bg-secondary">Inativo</span>
                                        @else
                                            <span class="badge bg-success">Ativo</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold text-muted text-uppercase small">Criado em</label>
                                    <p class="form-control-plaintext">
                                        {{ \Carbon\Carbon::parse($share->created_at)->format('d/m/Y H:i') }}
                                        <small class="text-muted">
                                            ({{ \Carbon\Carbon::parse($share->created_at)->diffForHumans() }})
                                        </small>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5 class="text-primary mb-3 border-bottom pb-2">Alterar Configurações</h5>
                                
                                <div class="mb-3">
                                    <label for="budget_id" class="form-label fw-bold text-muted text-uppercase small">Alterar Orçamento</label>
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
                                    <label class="form-label fw-bold text-muted text-uppercase small">Prévia do Novo Orçamento</label>
                                    <div class="card bg-light border-0">
                                        <div class="card-body py-2">
                                            <div class="row text-sm">
                                                <div class="col-6">
                                                    <strong class="text-muted small text-uppercase">Cliente:</strong>
                                                    <div id="previewCustomer" class="fw-bold"></div>
                                                </div>
                                                <div class="col-6">
                                                    <strong class="text-muted small text-uppercase">Valor:</strong>
                                                    <div id="previewValue" class="text-success fw-bold"></div>
                                                </div>
                                                <div class="col-6 mt-2">
                                                    <strong class="text-muted small text-uppercase">Status:</strong>
                                                    <div id="previewStatus"></div>
                                                </div>
                                                <div class="col-6 mt-2">
                                                    <strong class="text-muted small text-uppercase">Data:</strong>
                                                    <div id="previewDate" class="text-muted"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="expires_at" class="form-label fw-bold text-muted text-uppercase small">Data de Expiração</label>
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
                                    <label class="form-label fw-bold text-muted text-uppercase small">Status</label>
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
                                <h5 class="text-primary mb-3 border-bottom pb-2">Permissões de Acesso</h5>
                                
                                @php
                                    $currentPermissions = json_decode($share->permissions, true);
                                @endphp
                                
                                <div class="card p-3 bg-light border-0">
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
                                </div>
                                @error('permissions')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label fw-bold text-muted text-uppercase small">Observações</label>
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

                        <div class="row mt-4 pt-3 border-top">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex gap-2">
                                        <x-ui.button :href="route('provider.budgets.shares.show', $share->id)" variant="secondary" outline icon="arrow-left" label="Voltar" />
                                        
                                        <x-ui.button 
                                            type="button" 
                                            variant="warning" 
                                            outline 
                                            icon="arrow-clockwise" 
                                            label="Regenerar Token" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#regenerateTokenModal" 
                                            data-action-url="{{ route('provider.budgets.shares.regenerate', $share->id) }}"
                                        />
                                    </div>
                                    <div class="d-flex gap-2">
                                        <x-ui.button 
                                            type="button" 
                                            variant="danger" 
                                            outline 
                                            icon="trash" 
                                            label="Excluir" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal" 
                                            data-delete-url="{{ route('provider.budgets.shares.destroy', $share->id) }}"
                                        />
                                        
                                        <x-ui.button type="submit" variant="primary" icon="save" label="Salvar Alterações" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </x-ui.card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>

    <x-ui.confirm-modal 
        id="regenerateTokenModal" 
        title="Confirmar Regeneração de Token" 
        message="Tem certeza que deseja regenerar o token de acesso?" 
        submessage="O link antigo será invalidado e um novo será gerado. Qualquer pessoa com o link antigo não conseguirá mais acessar o orçamento."
        confirmLabel="Regenerar Token"
        variant="warning"
        type="confirm" 
        method="POST"
        resource="token"
    />

    <x-ui.confirm-modal 
        id="deleteModal" 
        title="Confirmar Exclusão" 
        message="Tem certeza que deseja excluir este compartilhamento?" 
        submessage="Esta ação não pode ser desfeita. O link de acesso será permanentemente removido."
        confirmLabel="Excluir Compartilhamento"
        variant="danger"
        type="delete" 
        resource="compartilhamento"
    />
@endsection

@push('scripts')
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
            alert('Por favor, selecione pelo menos uma permissão.');
            return;
        }
    });
});
</script>
@endpush
