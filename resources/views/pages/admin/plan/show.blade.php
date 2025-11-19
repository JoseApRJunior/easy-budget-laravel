@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-box-seam me-2"></i>Detalhes do Plano: {{ $plan->name }}</h1>
        <div>
            <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left me-1"></i>Voltar
            </a>
            <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-warning me-2">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            <a href="{{ route('admin.plans.subscribers', $plan) }}" class="btn btn-info">
                <i class="bi bi-people me-1"></i>Assinantes
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Plan Information -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informações do Plano</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>ID:</th>
                            <td>{{ $plan->id }}</td>
                        </tr>
                        <tr>
                            <th>Nome:</th>
                            <td>{{ $plan->name }}</td>
                        </tr>
                        @if($plan->slug)
                        <tr>
                            <th>Slug:</th>
                            <td><code>{{ $plan->slug }}</code></td>
                        </tr>
                        @endif
                        <tr>
                            <th>Descrição:</th>
                            <td>{{ $plan->description ?? 'Nenhuma descrição' }}</td>
                        </tr>
                        <tr>
                            <th>Preço:</th>
                            <td>R$ {{ number_format($plan->price, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                @php
                                    $statusClass = match($plan->status) {
                                        'active' => 'bg-success',
                                        'inactive' => 'bg-danger',
                                        'draft' => 'bg-secondary',
                                        default => 'bg-light text-dark'
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ ucfirst($plan->status) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Criado em:</th>
                            <td>{{ $plan->created_at ? $plan->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Atualizado em:</th>
                            <td>{{ $plan->updated_at ? $plan->updated_at->format('d/m/Y H:i') : 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Plan Limits -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-limits me-2"></i>Limites do Plano</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>Máximo de Orçamentos:</th>
                            <td>{{ $plan->max_budgets }}</td>
                        </tr>
                        <tr>
                            <th>Máximo de Clientes:</th>
                            <td>{{ $plan->max_clients }}</td>
                        </tr>
                    </table>

                    @if($plan->features)
                        <hr>
                        <h6>Recursos Incluídos:</h6>
                        <ul class="list-unstyled">
                            @foreach($plan->features as $feature)
                                <li><i class="bi bi-check-circle text-success me-2"></i>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <!-- Plan Statistics -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Estatísticas</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>Total de Assinaturas:</th>
                            <td><span class="badge bg-primary">{{ $stats['total_subscriptions'] }}</span></td>
                        </tr>
                        <tr>
                            <th>Assinaturas Ativas:</th>
                            <td><span class="badge bg-success">{{ $stats['active_subscriptions'] }}</span></td>
                        </tr>
                        <tr>
                            <th>Assinaturas Canceladas:</th>
                            <td><span class="badge bg-danger">{{ $stats['cancelled_subscriptions'] }}</span></td>
                        </tr>
                        <tr>
                            <th>Assinaturas em Trial:</th>
                            <td><span class="badge bg-info">{{ $stats['trial_subscriptions'] }}</span></td>
                        </tr>
                        <tr>
                            <th>Receita Total:</th>
                            <td><strong>R$ {{ number_format($stats['total_revenue'], 2, ',', '.') }}</strong></td>
                        </tr>
                        <tr>
                            <th>Receita Mensal:</th>
                            <td><strong>R$ {{ number_format($stats['monthly_revenue'], 2, ',', '.') }}</strong></td>
                        </tr>
                        <tr>
                            <th>Taxa de Churn:</th>
                            <td>{{ number_format($stats['churn_rate'], 2, ',', '.') }}%</td>
                        </tr>
                        <tr>
                            <th>Taxa de Conversão:</th>
                            <td>{{ number_format($stats['conversion_rate'], 2, ',', '.') }}%</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Subscriptions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Assinaturas Recentes</h5>
                    <a href="{{ route('admin.plans.subscribers', $plan) }}" class="btn btn-sm btn-outline-primary">
                        Ver Todas
                    </a>
                </div>
                <div class="card-body">
                    @if($subscriptions->count() > 0)
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
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscriptions as $subscription)
                                        <tr>
                                            <td>{{ $subscription->id }}</td>
                                            <td>{{ $subscription->tenant->name ?? 'N/A' }}</td>
                                            <td>{{ $subscription->provider->name ?? 'N/A' }}</td>
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
                                            <td>R$ {{ number_format($subscription->transaction_amount, 2, ',', '.') }}</td>
                                            <td>
                                                <a href="{{ route('admin.plans.subscribers', [$plan, 'search' => $subscription->id]) }}" class="btn btn-sm btn-primary" title="Ver detalhes">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle me-2"></i>
                            Nenhuma assinatura encontrada para este plano.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <div>
                    @if(!$plan->planSubscriptions()->exists())
                        <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir este plano?')">
                                <i class="bi bi-trash me-1"></i>Excluir Plano
                            </button>
                        </form>
                    @endif
                </div>
                <div>
                    <a href="{{ route('admin.plans.duplicate', $plan) }}" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-copy me-1"></i>Duplicar
                    </a>
                    <a href="{{ route('admin.plans.analytics', $plan) }}" class="btn btn-outline-primary me-2">
                        <i class="bi bi-graph-up me-1"></i>Análises
                    </a>
                    <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i>Editar Plano
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
