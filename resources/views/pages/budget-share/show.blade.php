@extends('layouts.app')

@section('title', 'Detalhes do Compartilhamento')

@section('content')
<div class="container-fluid py-1">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-share me-2"></i>
            Detalhes do Compartilhamento
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('provider.budgets.index') }}">Orçamentos</a></li>
                <li class="breadcrumb-item"><a href="{{ route('provider.budgets.shares.index') }}">Compartilhamentos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detalhes</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-share me-2"></i>Informações do Compartilhamento
                    </h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('provider.budgets.shares.edit', $share->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i>Editar
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="confirmRevoke({{ $share->id }})">
                            <i class="bi bi-trash me-1"></i>Revogar
                        </button>
                        <a href="{{ route('provider.budgets.shares.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Voltar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">Informações do Compartilhamento</h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Token de Acesso</label>
                                @include('components.share-token', ['token' => $share->token])
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Status</label>
                                @include('components.share-status-badge', ['share' => $share])
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Link de Acesso</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" 
                                           value="{{ route('budget-share.public', $share->token) }}" readonly>
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="copyLink('{{ route('budget-share.public', $share->token) }}')">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Data de Expiração</label>
                                <p class="form-control-plaintext">
                                    @if($share->expires_at)
                                        {{ \Carbon\Carbon::parse($share->expires_at)->format('d/m/Y H:i') }}
                                        <small class="text-muted">
                                            ({{ \Carbon\Carbon::parse($share->expires_at)->diffForHumans() }})
                                        </small>
                                    @else
                                        <span class="text-success">Sem expiração</span>
                                    @endif
                                </p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Data de Criação</label>
                                <p class="form-control-plaintext">
                                    {{ \Carbon\Carbon::parse($share->created_at)->format('d/m/Y H:i') }}
                                    <small class="text-muted">
                                        ({{ \Carbon\Carbon::parse($share->created_at)->diffForHumans() }})
                                    </small>
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">Orçamento Compartilhado</h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Número do Orçamento</label>
                                <p class="form-control-plaintext">
                                    <a href="{{ route('provider.budgets.show', $share->budget->code) }}" class="text-decoration-none">
                                        #{{ str_pad($share->budget->id, 6, '0', STR_PAD_LEFT) }}
                                    </a>
                                </p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Cliente</label>
                                <p class="form-control-plaintext">
                                    <a href="{{ route('customers.show', $share->budget->customer->id) }}" class="text-decoration-none">
                                        {{ $share->budget->customer->name }}
                                    </a>
                                </p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Valor Total</label>
                                <p class="form-control-plaintext text-success fw-bold">
                                    R$ {{ number_format($share->budget->total_value, 2, ',', '.') }}
                                </p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Status do Orçamento</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-{{ $share->budget->status === 'approved' ? 'success' : 
                                                           ($share->budget->status === 'pending' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($share->budget->status) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">Permissões de Acesso</h5>
                            @include('components.share-permissions', ['permissions' => json_decode($share->permissions, true)])
                        </div>
                    </div>

                    @if($share->notes)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">Observações</h5>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                {{ $share->notes }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="revokeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Revogação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja revogar este compartilhamento?</p>
                <p class="text-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Após revogar, o link de acesso será invalidado e não poderá mais ser usado.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="revokeForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Revogar Compartilhamento</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function copyLink(link) {
    navigator.clipboard.writeText(link).then(function() {
        showToast('Link copiado para a área de transferência!', 'success');
    }).catch(function() {
        showToast('Erro ao copiar link', 'error');
    });
}

function confirmRevoke(shareId) {
    const form = document.getElementById('revokeForm');
    form.action = `/budget-share/${shareId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('revokeModal'));
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

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Adiciona animação ao copiar token
    const copyTokenBtn = document.querySelector('#copyTokenBtn');
    if (copyTokenBtn) {
        copyTokenBtn.addEventListener('click', function() {
            this.innerHTML = '<i class="bi bi-check"></i>';
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-success');
            
            setTimeout(() => {
                this.innerHTML = '<i class="bi bi-clipboard"></i>';
                this.classList.remove('btn-success');
                this.classList.add('btn-outline-secondary');
            }, 2000);
        });
    }
});
</script>
@endsection
