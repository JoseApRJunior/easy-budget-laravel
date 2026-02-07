<x-app-layout :title="'Assinantes do Plano: ' . $plan->name">
<div class="container-fluid py-4">
    <x-layout.page-header
        :title="'Assinantes do Plano: ' . $plan->name"
        icon="people"
        :breadcrumb-items="[
            'Dashboard' => route('admin.dashboard'),
            'Planos' => route('admin.plans.index'),
            $plan->name => route('admin.plans.show', $plan),
            'Assinantes' => '#'
        ]">
        <div class="d-flex gap-2">
            <x-ui.button type="link" :href="route('admin.plans.show', $plan)" variant="secondary" icon="arrow-left" label="Voltar ao Plano" />
            <x-ui.button type="link" :href="route('admin.plans.export', ['format' => 'csv'])" variant="primary" icon="download" label="Exportar" />
        </div>
    </x-layout.page-header>

    <!-- Plan Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <x-ui.card class="bg-primary text-white">
                <h5 class="card-title">Total de Assinantes</h5>
                <h3>{{ $subscribers->total() }}</h3>
            </x-ui.card>
        </div>
        <div class="col-md-3">
            <x-ui.card class="bg-success text-white">
                <h5 class="card-title">Assinaturas Ativas</h5>
                <h3>{{ $plan->planSubscriptions()->where('status', 'active')->count() }}</h3>
            </x-ui.card>
        </div>
        <div class="col-md-3">
            <x-ui.card class="bg-warning text-dark">
                <h5 class="card-title">Assinaturas em Trial</h5>
                <h3>{{ $plan->planSubscriptions()->where('status', 'trial')->count() }}</h3>
            </x-ui.card>
        </div>
        <div class="col-md-3">
            <x-ui.card class="bg-info text-white">
                <h5 class="card-title">Receita Total</h5>
                <h3>{{ \App\Helpers\CurrencyHelper::format($plan->planSubscriptions()->sum('transaction_amount')) }}</h3>
            </x-ui.card>
        </div>
    </div>

    <!-- Search and Filter -->
    <x-ui.card class="mb-4">
        <form method="GET" action="{{ route('admin.plans.subscribers', $plan) }}">
            <div class="row g-3">
                <div class="col-md-6">
                    <x-ui.form.input name="search" label="Pesquisar" :value="$search" placeholder="Nome do tenant ou usuário..." />
                </div>
                <div class="col-md-3">
                    <x-ui.form.select 
                        name="status" 
                        label="Status" 
                        :options="[
                            'active' => 'Ativa',
                            'trial' => 'Trial',
                            'cancelled' => 'Cancelada',
                            'pending' => 'Pendente'
                        ]"
                        :selected="$status"
                        placeholder="Todos"
                    />
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">&nbsp;</label>
                    <div class="d-grid">
                        <x-ui.button type="submit" variant="primary" icon="search" label="Filtrar" />
                    </div>
                </div>
            </div>
        </form>
    </x-ui.card>

    <!-- Subscribers Table -->
    <x-ui.card>
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
                                        <x-ui.button type="link" :href="route('admin.subscriptions.show', $subscription)" variant="info" size="sm" icon="eye" title="Ver detalhes" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $subscribers->links() }}
            </div>
        @else
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                Nenhum assinante encontrado para este plano.
            </div>
        @endif
    </x-ui.card>
</div>
</x-app-layout>
