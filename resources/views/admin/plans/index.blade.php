@extends('layouts.admin')

@section('title', 'Gerenciamento de Assinaturas')

@section('breadcrumb')
    <li class="breadcrumb-item active">Assinaturas</li>
@endsection

@section('page_actions')
    @if(isset($isHistoryPage) && $isHistoryPage)
        <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Voltar para Assinaturas Ativas
        </a>
    @else
        <a href="{{ route('admin.plans.history') }}" class="btn btn-outline-info">
            <i class="bi bi-clock-history me-1"></i>Ver Histórico
        </a>
    @endif
@endsection

@section('admin_content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>{{ $pageTitle ?? 'Gerenciamento de Assinaturas' }}</h2>
                @if(isset($isHistoryPage) && $isHistoryPage)
                    <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Voltar para Assinaturas Ativas
                    </a>
                @endif
            </div>

            @if(isset($subscriptions) && count($subscriptions) > 0)
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Prestador</th>
                                        <th scope="col">Plano</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Início</th>
                                        <th scope="col">Fim</th>
                                        <th scope="col">Valor Pago</th>
                                        <th scope="col">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscriptions as $subscription)
                                    <tr>
                                        <th scope="row">{{ $subscription['id'] ?? $subscription->id }}</th>
                                        <td>{{ $subscription['provider_name'] ?? $subscription->provider_name }}</td>
                                        <td>{{ $subscription['plan_name'] ?? $subscription->plan_name }}</td>
                                        <td>
                                            @php
                                                $status = $subscription['status'] ?? $subscription->status ?? 'unknown';
                                                $statusMap = [
                                                    'active' => ['class' => 'bg-success', 'text' => 'Ativa'],
                                                    'pending' => ['class' => 'bg-warning text-dark', 'text' => 'Pendente'],
                                                    'cancelled' => ['class' => 'bg-dark', 'text' => 'Cancelada'],
                                                    'expired' => ['class' => 'bg-secondary', 'text' => 'Expirada']
                                                ];
                                                $statusInfo = $statusMap[$status] ?? ['class' => 'bg-light text-dark border', 'text' => ucfirst($status)];
                                            @endphp
                                            <span class="badge {{ $statusInfo['class'] }}">
                                                {{ $statusInfo['text'] }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $startDate = $subscription['start_date'] ?? $subscription->start_date ?? null;
                                            @endphp
                                            {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : 'N/A' }}
                                        </td>
                                        <td>
                                            @php
                                                $endDate = $subscription['end_date'] ?? $subscription->end_date ?? null;
                                            @endphp
                                            {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : 'N/A' }}
                                        </td>
                                        <td>R$ {{ number_format($subscription['transaction_amount'] ?? $subscription->transaction_amount ?? 0, 2, ',', '.') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.plans.show', $subscription['id'] ?? $subscription->id) }}"
                                                   class="btn btn-sm btn-primary" title="Ver Detalhes">
                                                    <i class="bi bi-eye-fill"></i>
                                                </a>
                                                @if(($subscription['status'] ?? $subscription->status) === 'active')
                                                    <button type="button" class="btn btn-sm btn-warning"
                                                            title="Suspender" onclick="suspendSubscription({{ $subscription['id'] ?? $subscription->id }})">
                                                        <i class="bi bi-pause"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                            title="Cancelar" onclick="cancelSubscription({{ $subscription['id'] ?? $subscription->id }})">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Estatísticas das Assinaturas --}}
                @if(isset($subscriptionStats))
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body">
                                <i class="bi bi-check-circle text-success fs-1 mb-2"></i>
                                <h3 class="text-success">{{ $subscriptionStats['active'] ?? 0 }}</h3>
                                <p class="text-muted mb-0">Assinaturas Ativas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body">
                                <i class="bi bi-clock text-warning fs-1 mb-2"></i>
                                <h3 class="text-warning">{{ $subscriptionStats['pending'] ?? 0 }}</h3>
                                <p class="text-muted mb-0">Pendentes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body">
                                <i class="bi bi-x-circle text-danger fs-1 mb-2"></i>
                                <h3 class="text-danger">{{ $subscriptionStats['cancelled'] ?? 0 }}</h3>
                                <p class="text-muted mb-0">Canceladas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body">
                                <i class="bi bi-graph-up text-info fs-1 mb-2"></i>
                                <h3 class="text-info">R$ {{ number_format($subscriptionStats['total_revenue'] ?? 0, 2, ',', '.') }}</h3>
                                <p class="text-muted mb-0">Receita Total</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

            @else
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-credit-card text-muted fs-1 mb-3"></i>
                        <h5 class="text-muted">Nenhuma assinatura encontrada</h5>
                        <p class="text-muted mb-4">
                            @if(isset($isHistoryPage) && $isHistoryPage)
                                Não há assinaturas no histórico.
                            @else
                                Ainda não há assinaturas ativas no sistema.
                            @endif
                        </p>
                        @if(!isset($isHistoryPage) || !$isHistoryPage)
                            <a href="{{ route('admin.plans.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Criar Primeira Assinatura
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function suspendSubscription(subscriptionId) {
    if (confirm('Tem certeza que deseja suspender esta assinatura?')) {
        // Implementar lógica de suspensão
        console.log('Suspending subscription:', subscriptionId);
        // Exemplo de requisição AJAX
        fetch(`/admin/plans/${subscriptionId}/suspend`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro ao suspender assinatura: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao suspender assinatura. Tente novamente.');
        });
    }
}

function cancelSubscription(subscriptionId) {
    if (confirm('Tem certeza que deseja cancelar esta assinatura? Esta ação não pode ser desfeita.')) {
        // Implementar lógica de cancelamento
        console.log('Cancelling subscription:', subscriptionId);
        // Exemplo de requisição AJAX
        fetch(`/admin/plans/${subscriptionId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro ao cancelar assinatura: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao cancelar assinatura. Tente novamente.');
        });
    }
}
</script>
@endpush
