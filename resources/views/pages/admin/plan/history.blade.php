@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-clock-history me-2"></i>Histórico do Plano: {{ $plan->name }}</h1>
        <div class="d-flex gap-2">
            <x-button type="link" :href="route('admin.plans.show', $plan)" variant="secondary" icon="arrow-left" label="Voltar ao Plano" />
            <x-button type="link" :href="route('admin.plans.export', ['format' => 'json'])" variant="primary" icon="download" label="Exportar Dados" />
        </div>
    </div>

    <!-- Plan Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total de Registros</h5>
                    <h3>{{ $history->total() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Assinaturas Ativas</h5>
                    <h3>{{ $plan->planSubscriptions()->where('status', 'active')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Assinaturas em Trial</h5>
                    <h3>{{ $plan->planSubscriptions()->where('status', 'trial')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Receita Total</h5>
                    <h3>{{ \App\Helpers\CurrencyHelper::format($plan->planSubscriptions()->sum('transaction_amount')) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="card">
        <div class="card-body">
            @if($history->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tenant</th>
                                <th>Usuário</th>
                                <th>Status</th>
                                <th>Início</th>
                                <th>Fim</th>
                                <th>Valor</th>
                                <th>Método</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $subscription)
                                <tr>
                                    <td>{{ $subscription->id }}</td>
                                    <td>
                                        @if($subscription->tenant)
                                            <strong>{{ $subscription->tenant->name }}</strong>
                                            <br><small class="text-muted">{{ $subscription->tenant->email }}</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($subscription->provider)
                                            <strong>{{ $subscription->provider->name }}</strong>
                                            <br><small class="text-muted">{{ $subscription->provider->email ?? 'N/A' }}</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($subscription->status) {
                                                'active' => 'bg-success',
                                                'cancelled' => 'bg-danger',
                                                'trial' => 'bg-info',
                                                'pending' => 'bg-warning text-dark',
                                                'expired' => 'bg-secondary',
                                                default => 'bg-light text-dark'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ ucfirst($subscription->status) }}</span>
                                    </td>
                                    <td>{{ $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('d/m/Y') : 'N/A' }}</td>
                                    <td>{{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('d/m/Y') : 'N/A' }}</td>
                                    <td>{{ \App\Helpers\CurrencyHelper::format($subscription->transaction_amount) }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ ucfirst($subscription->payment_method ?? 'N/A') }}</span>
                                    </td>
                                    <td>{{ $subscription->created_at ? $subscription->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <x-button type="link" :href="route('admin.subscriptions.show', $subscription)" variant="info" size="sm" icon="eye" title="Ver detalhes" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $history->links() }}
                </div>
            @else
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    Nenhum histórico encontrado para este plano.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection