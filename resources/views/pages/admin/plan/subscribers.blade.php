@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-people me-2"></i>Assinantes do Plano: {{ $plan->name }}</h1>
        <div class="d-flex gap-2">
            <x-button type="link" :href="route('admin.plans.show', $plan)" variant="secondary" icon="arrow-left" label="Voltar ao Plano" />
            <x-button type="link" :href="route('admin.plans.export', ['format' => 'csv'])" variant="primary" icon="download" label="Exportar" />
        </div>
    </div>

    <!-- Plan Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total de Assinantes</h5>
                    <h3>{{ $subscribers->total() }}</h3>
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
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Assinaturas em Trial</h5>
                    <h3>{{ $plan->planSubscriptions()->where('status', 'trial')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Receita Total</h5>
                    <h3>{{ \App\Helpers\CurrencyHelper::format($plan->planSubscriptions()->sum('transaction_amount')) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.plans.subscribers', $plan) }}">
                <div class="row">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Pesquisar</label>
                        <input type="text" class="form-control" id="search" name="search" value="{{ $search }}" placeholder="Nome do tenant ou usuário...">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Todos</option>
                            <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Ativa</option>
                            <option value="trial" {{ $status == 'trial' ? 'selected' : '' }}>Trial</option>
                            <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                            <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pendente</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <x-button type="submit" variant="primary" icon="search" label="Filtrar" />
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Subscribers Table -->
    <div class="card">
        <div class="card-body">
            @if($subscribers->count() > 0)
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
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subscribers as $subscription)
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
                                                default => 'bg-light text-dark'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ ucfirst($subscription->status) }}</span>
                                    </td>
                                    <td>{{ $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('d/m/Y') : 'N/A' }}</td>
                                    <td>{{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('d/m/Y') : 'N/A' }}</td>
                                    <td>{{ \App\Helpers\CurrencyHelper::format($subscription->transaction_amount) }}</td>
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
                    {{ $subscribers->links() }}
                </div>
            @else
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    Nenhum assinante encontrado para este plano.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection