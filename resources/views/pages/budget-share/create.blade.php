@extends('layouts.app')

@section('title', 'Criar Compartilhamento')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Criar Novo Compartilhamento"
            icon="share"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Orçamentos' => route('provider.budgets.index'),
                'Compartilhamentos' => route('provider.budgets.shares.index'),
                'Criar' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button :href="route('provider.budgets.shares.index')" variant="secondary" outline icon="arrow-left" label="Voltar" feature="budgets" />
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <div class="col-12">
                <x-ui.card>
                    <x-slot:header>
                        <h4 class="card-title mb-0 text-primary fw-bold">
                            <i class="bi bi-share me-2"></i>Configurações do Compartilhamento
                        </h4>
                    </x-slot:header>

                    <form action="{{ route('provider.budgets.shares.store') }}" method="POST" id="createShareForm">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3 border-bottom pb-2">Selecionar Orçamento</h5>

                                <div class="mb-3">
                                    <label for="budget_id" class="form-label fw-bold text-muted text-uppercase small">
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
                                                    {{ (old('budget_id', $selectedBudgetId) == $budget->id) ? 'selected' : '' }}>
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

                                <div class="mb-3">
                                    <label for="recipient_name" class="form-label fw-bold text-muted text-uppercase small">
                                        Nome do Destinatário <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('recipient_name') is-invalid @enderror"
                                           id="recipient_name" name="recipient_name"
                                           value="{{ old('recipient_name') }}" required
                                           placeholder="Nome de quem receberá o link">
                                    @error('recipient_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label fw-bold text-muted text-uppercase small">
                                        E-mail do Destinatário <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email"
                                           value="{{ old('email') }}" required
                                           placeholder="email@exemplo.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div id="budgetPreview" class="mb-3" style="display: none;">
                                    <label class="form-label fw-bold text-muted text-uppercase small">Prévia do Orçamento</label>
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
                            </div>

                            <div class="col-md-6">
                                <h5 class="text-primary mb-3 border-bottom pb-2">Configurações de Acesso</h5>

                                <div class="mb-3">
                                    <label for="expires_at" class="form-label fw-bold text-muted text-uppercase small">Data de Expiração</label>
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
                                    <label class="form-label fw-bold text-muted text-uppercase small">Permissões de Acesso</label>
                                    <div class="card p-3 bg-light border-0">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox"
                                                   id="can_view" name="permissions[can_view]"
                                                   value="1" {{ is_array(old('permissions')) ? (isset(old('permissions')['can_view']) ? 'checked' : '') : 'checked' }}>
                                            <label class="form-check-label" for="can_view">
                                                Pode visualizar o orçamento
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox"
                                                   id="can_approve" name="permissions[can_approve]"
                                                   value="1" {{ is_array(old('permissions')) ? (isset(old('permissions')['can_approve']) ? 'checked' : '') : 'checked' }}>
                                            <label class="form-check-label" for="can_approve">
                                                Pode aprovar o orçamento
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox"
                                                   id="can_reject" name="permissions[can_reject]"
                                                   value="1" {{ is_array(old('permissions')) ? (isset(old('permissions')['can_reject']) ? 'checked' : '') : 'checked' }}>
                                            <label class="form-check-label" for="can_reject">
                                                Pode rejeitar o orçamento
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox"
                                                   id="can_comment" name="permissions[can_comment]"
                                                   value="1" {{ is_array(old('permissions')) ? (isset(old('permissions')['can_comment']) ? 'checked' : '') : 'checked' }}>
                                            <label class="form-check-label" for="can_comment">
                                                Pode adicionar comentários
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   id="can_print" name="permissions[can_print]"
                                                   value="1" {{ is_array(old('permissions')) ? (isset(old('permissions')['can_print']) ? 'checked' : '') : 'checked' }}>
                                            <label class="form-check-label" for="can_print">
                                                Pode imprimir o orçamento
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label fw-bold text-muted text-uppercase small">Observações</label>
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

                        <div class="row mt-4 pt-3 border-top">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <x-ui.button :href="route('provider.budgets.shares.index')" variant="secondary" outline label="Cancelar" feature="budgets" />
                                    <x-ui.button type="submit" variant="primary" icon="share" label="Criar Compartilhamento" feature="budgets" />
                                </div>
                            </div>
                        </div>
                    </form>
                </x-ui.card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>
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

    // Trigger preview if already selected
    if (budgetSelect.value) {
        updateBudgetPreview();
    }

    // Validação do formulário
    document.getElementById('createShareForm').addEventListener('submit', function(e) {
        const budgetId = document.getElementById('budget_id').value;
        const permissions = document.querySelectorAll('input[name^="permissions["]:checked');

        if (!budgetId) {
            e.preventDefault();
            alert('Por favor, selecione um orçamento.');
            return;
        }

        if (permissions.length === 0) {
            e.preventDefault();
            alert('Por favor, selecione pelo menos uma permissão.');
            return;
        }
    });
});

// Definir data mínima para expiração (hoje)
document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    // Adjust to local ISO string for datetime-local input
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    const minDateTime = now.toISOString().slice(0, 16);

    const expiresInput = document.getElementById('expires_at');
    if(expiresInput) {
        expiresInput.min = minDateTime;
    }
});
</script>
@endpush
