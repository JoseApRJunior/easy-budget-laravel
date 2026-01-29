@extends('layouts.app')

@section('title', 'Detalhes do Compartilhamento')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Detalhes do Compartilhamento"
            icon="share"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Orçamentos' => route('provider.budgets.index'),
                'Compartilhamentos' => route('provider.budgets.shares.index'),
                'Detalhes' => '#'
            ]">
            <x-slot:actions>
                <div class="d-flex gap-2">
                    <x-ui.button :href="route('provider.budgets.shares.edit', $share->id)" variant="primary" icon="pencil" label="Editar" />
                    
                    <x-ui.button 
                        type="button" 
                        variant="danger" 
                        outline 
                        icon="trash" 
                        label="Revogar" 
                        data-bs-toggle="modal" 
                        data-bs-target="#revokeModal" 
                        data-action-url="{{ route('provider.budgets.shares.destroy', $share->id) }}"
                        data-item-name="{{ $share->budget->code }}"
                    />
                    
                    <x-ui.button :href="route('provider.budgets.shares.index')" variant="secondary" outline icon="arrow-left" label="Voltar" />
                </div>
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <div class="col-12">
                <x-ui.card>
                    <x-slot:header>
                        <h4 class="card-title mb-0 text-primary fw-bold">
                            <i class="bi bi-share me-2"></i>Informações do Compartilhamento
                        </h4>
                    </x-slot:header>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-primary fw-bold mb-3 border-bottom pb-2">Informações do Compartilhamento</h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Token de Acesso</label>
                                <div><code class="bg-light px-2 py-1 rounded">{{ $share->token }}</code></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Status</label>
                                <div>
                                    @if($share->expires_at && \Carbon\Carbon::parse($share->expires_at)->isPast())
                                        <span class="badge bg-danger">Expirado</span>
                                    @else
                                        <span class="badge bg-success">Ativo</span>
                                    @endif
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Link de Acesso</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" 
                                           value="{{ route('budgets.public.shared.view', $share->token) }}" readonly>
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="copyLink('{{ route('budgets.public.shared.view', $share->token) }}')">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Data de Expiração</label>
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
                                <label class="form-label fw-bold small text-uppercase text-muted">Data de Criação</label>
                                <p class="form-control-plaintext">
                                    {{ \Carbon\Carbon::parse($share->created_at)->format('d/m/Y H:i') }}
                                    <small class="text-muted">
                                        ({{ \Carbon\Carbon::parse($share->created_at)->diffForHumans() }})
                                    </small>
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5 class="text-primary fw-bold mb-3 border-bottom pb-2">Orçamento Compartilhado</h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Número do Orçamento</label>
                                <p class="form-control-plaintext">
                                    <a href="{{ route('provider.budgets.show', $share->budget->code) }}" class="text-decoration-none fw-bold">
                                        #{{ $share->budget->code }}
                                    </a>
                                </p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Cliente</label>
                                <p class="form-control-plaintext">
                                    <a href="{{ route('provider.customers.show', $share->budget->customer->id) }}" class="text-decoration-none">
                                        {{ $share->budget->customer->name }}
                                    </a>
                                </p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Valor Total</label>
                                <p class="form-control-plaintext text-success fw-bold fs-5">
                                    R$ {{ number_format($share->budget->total, 2, ',', '.') }}
                                </p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Status do Orçamento</label>
                                <div>
                                    <span class="badge" style="background-color: {{ $share->budget->budgetStatus->color }};">
                                        {{ $share->budget->budgetStatus->name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="text-primary fw-bold mb-3 border-bottom pb-2">Permissões de Acesso</h5>
                            @php
                                $permissions = json_decode($share->permissions, true) ?? [];
                                $permissionLabels = [
                                    'view_values' => 'Visualizar Valores',
                                    'approve' => 'Aprovar Orçamento',
                                    'reject' => 'Rejeitar Orçamento',
                                    'download' => 'Baixar PDF',
                                    'print' => 'Baixar PDF'
                                ];
                            @endphp
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($permissions as $permission)
                                    <span class="badge bg-info text-dark">
                                        <i class="bi bi-check-circle me-1"></i>
                                        {{ $permissionLabels[$permission] ?? $permission }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    @if($share->notes)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="text-primary fw-bold mb-3 border-bottom pb-2">Observações</h5>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                {{ $share->notes }}
                            </div>
                        </div>
                    </div>
                    @endif
                </x-ui.card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>

    <x-ui.confirm-modal 
        id="revokeModal" 
        title="Confirmar Revogação" 
        message="Tem certeza que deseja revogar o compartilhamento do orçamento <strong id='revokeModalItemName'></strong>?" 
        submessage="O link de acesso será invalidado e não poderá mais ser usado."
        confirmLabel="Revogar Compartilhamento"
        variant="danger"
        type="delete" 
        resource="compartilhamento"
    />

@endsection

@push('scripts')
<script>
function copyLink(link) {
    navigator.clipboard.writeText(link).then(function() {
        alert('Link copiado para a área de transferência!');
    }).catch(function() {
        alert('Erro ao copiar link');
    });
}
</script>
@endpush
