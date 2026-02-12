<x-app-layout :title="'Detalhes do Plano: ' . $plan->name">
    <x-layout.page-container>
        <x-layout.page-header
            :title="'Detalhes do Plano: ' . $plan->name"
            icon="box-seam"
            :breadcrumb-items="[
                'Dashboard' => route('admin.dashboard'),
                'Planos' => route('admin.plans.index'),
                $plan->name => '#'
            ]">
            <x-slot:actions>
                <div class="d-flex gap-2">
                    <x-ui.button :href="route('admin.plans.index')" variant="secondary" outline icon="arrow-left" label="Voltar" feature="manage-plans" />
                    <x-ui.button :href="route('admin.plans.edit', $plan)" variant="primary" icon="pencil-square" label="Editar" feature="manage-plans" />
                    <x-ui.button :href="route('admin.plans.subscribers', $plan)" variant="info" icon="people" label="Assinantes" feature="manage-plans" />
                </div>
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <!-- Plan Information -->
            <div class="col-md-4">
                <x-ui.card class="h-100">
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-info-circle me-2"></i>Informações do Plano
                        </h5>
                    </x-slot:header>
                    <table class="table table-borderless table-sm">
                        <tr>
                            <th class="text-muted text-end w-50">ID:</th>
                            <td class="fw-bold ps-3">{{ $plan->id }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted text-end">Nome:</th>
                            <td class="ps-3">{{ $plan->name }}</td>
                        </tr>
                        @if($plan->slug)
                        <tr>
                            <th class="text-muted text-end">Slug:</th>
                            <td class="ps-3"><code>{{ $plan->slug }}</code></td>
                        </tr>
                        @endif
                        <tr>
                            <th class="text-muted text-end">Descrição:</th>
                            <td class="ps-3">{{ $plan->description ?? 'Nenhuma descrição' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted text-end">Preço:</th>
                            <td class="ps-3">{{ \App\Helpers\CurrencyHelper::format($plan->price) }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted text-end">Status:</th>
                            <td class="ps-3">
                                @php
                                    $statusClass = match($plan->status) {
                                        'active' => 'success',
                                        'inactive' => 'danger',
                                        'draft' => 'secondary',
                                        default => 'secondary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $statusClass }}">{{ ucfirst($plan->status) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted text-end">Criado em:</th>
                            <td class="ps-3">{{ $plan->created_at ? $plan->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted text-end">Atualizado em:</th>
                            <td class="ps-3">{{ $plan->updated_at ? $plan->updated_at->format('d/m/Y H:i') : 'N/A' }}</td>
                        </tr>
                    </table>
                </x-ui.card>
            </div>

            <!-- Plan Limits -->
            <div class="col-md-4">
                <x-ui.card class="h-100">
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-speedometer2 me-2"></i>Limites do Plano
                        </h5>
                    </x-slot:header>
                    <table class="table table-borderless table-sm">
                        <tr>
                            <th class="text-muted text-end w-50">Máx. Orçamentos:</th>
                            <td class="fw-bold ps-3">{{ $plan->max_budgets }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted text-end">Máx. Clientes:</th>
                            <td class="fw-bold ps-3">{{ $plan->max_clients }}</td>
                        </tr>
                    </table>

                    @if($plan->features)
                        <hr>
                        <h6 class="text-dark fw-bold mb-3">Recursos Incluídos:</h6>
                        <ul class="list-unstyled mb-0">
                            @foreach($plan->features as $feature)
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card>
            </div>

            <!-- Plan Statistics -->
            <div class="col-md-4">
                <x-ui.card class="h-100">
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-graph-up me-2"></i>Estatísticas
                        </h5>
                    </x-slot:header>
                    <table class="table table-borderless table-sm">
                        <tr>
                            <th class="text-muted text-end w-50">Total de Assinaturas:</th>
                            <td class="ps-3"><span class="badge bg-primary">{{ $stats['total_subscriptions'] }}</span></td>
                        </tr>
                        <tr>
                            <th class="text-muted text-end">Assinaturas Ativas:</th>
                            <td class="ps-3"><span class="badge bg-success">{{ $stats['active_subscriptions'] }}</span></td>
                        </tr>
                        <tr>
                            <th class="text-muted text-end">Canceladas:</th>
                            <td class="ps-3"><span class="badge bg-danger">{{ $stats['cancelled_subscriptions'] }}</span></td>
                        </tr>
                        <tr>
                            <th class="text-muted text-end">Em Trial:</th>
                            <td class="ps-3"><span class="badge bg-info">{{ $stats['trial_subscriptions'] }}</span></td>
                        </tr>
                        <tr>
                            <th class="text-muted text-end">Receita Total:</th>
                            <td class="ps-3"><strong class="text-success">{{ \App\Helpers\CurrencyHelper::format($stats['total_revenue']) }}</strong></td>
                        </tr>
                        <tr>
                            <th class="text-muted text-end">Receita Mensal:</th>
                            <td class="ps-3"><strong class="text-success">{{ \App\Helpers\CurrencyHelper::format($stats['monthly_revenue']) }}</strong></td>
                        </tr>
                        <tr>
                            <th class="text-muted text-end">Churn Rate:</th>
                            <td class="ps-3">{{ \App\Helpers\CurrencyHelper::format($stats['churn_rate'], 2, false) }}%</td>
                        </tr>
                        <tr>
                            <th class="text-muted text-end">Conversão:</th>
                            <td class="ps-3">{{ \App\Helpers\CurrencyHelper::format($stats['conversion_rate'], 2, false) }}%</td>
                        </tr>
                    </table>
                </x-ui.card>
            </div>
        </x-layout.grid-row>

        <!-- Recent Subscriptions -->
        <x-layout.grid-row class="mt-4">
            <div class="col-12">
                <x-resource.resource-list-card
                    title="Assinaturas Recentes"
                    icon="clock-history"
                    :total="$subscriptions->count()"
                    :actions="[
                        [
                            'label' => 'Ver Todas',
                            'icon' => 'list-ul',
                            'route' => route('admin.plans.subscribers', $plan),
                            'variant' => 'primary',
                            'outline' => true,
                            'size' => 'sm',
                            'feature' => 'manage-plans'
                        ]
                    ]"
                >
                    <x-resource.resource-table :headers="['ID', 'Tenant', 'Usuário', 'Status', 'Início', 'Fim', 'Valor', 'Ações']">
                        @forelse($subscriptions as $subscription)
                            <x-resource.table-row>
                                <x-resource.table-cell>{{ $subscription->id }}</x-resource.table-cell>
                                <x-resource.table-cell>{{ $subscription->tenant->name ?? 'N/A' }}</x-resource.table-cell>
                                <x-resource.table-cell>{{ $subscription->provider->name ?? 'N/A' }}</x-resource.table-cell>
                                <x-resource.table-cell>
                                    @php
                                        $statusClass = match($subscription->status) {
                                            'active' => 'success',
                                            'cancelled' => 'danger',
                                            'trial' => 'info',
                                            'pending' => 'warning',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ ucfirst($subscription->status) }}</span>
                                </x-resource.table-cell>
                                <x-resource.table-cell>{{ $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('d/m/Y') : 'N/A' }}</x-resource.table-cell>
                                <x-resource.table-cell>{{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('d/m/Y') : 'N/A' }}</x-resource.table-cell>
                                <x-resource.table-cell>{{ \App\Helpers\CurrencyHelper::format($subscription->transaction_amount) }}</x-resource.table-cell>
                                <x-resource.table-cell>
                                    <x-resource.action-buttons>
                                        <x-ui.button :href="route('admin.plans.subscribers', [$plan, 'search' => $subscription->id])" variant="info" outline size="sm" icon="eye" title="Ver detalhes" feature="manage-plans" />
                                    </x-resource.action-buttons>
                                </x-resource.table-cell>
                            </x-resource.table-row>
                        @empty
                            <x-resource.table-row>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-people display-4 d-block mb-3"></i>
                                    Nenhuma assinatura encontrada para este plano.
                                </td>
                            </x-resource.table-row>
                        @endforelse
                    </x-resource.resource-table>
                </x-resource.resource-list-card>
            </div>
        </x-layout.grid-row>

        <!-- Action Buttons Footer -->
        <div class="row mt-4">
            <div class="col-12">
                <x-ui.card>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            @if(!$plan->planSubscriptions()->exists())
                                <x-ui.button 
                                    type="button" 
                                    variant="danger" 
                                    icon="trash" 
                                    label="Excluir Plano" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteModal" 
                                    data-delete-url="{{ route('admin.plans.destroy', $plan) }}"
                                    data-item-name="{{ $plan->name }}"
                                    feature="manage-plans"
                                />
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            <x-ui.button :href="route('admin.plans.duplicate', $plan)" variant="secondary" outline icon="copy" label="Duplicar" feature="manage-plans" />
                            <x-ui.button :href="route('admin.plans.analytics', $plan)" variant="primary" outline icon="graph-up" label="Análises" feature="manage-plans" />
                            <x-ui.button :href="route('admin.plans.edit', $plan)" variant="primary" icon="pencil-square" label="Editar Plano" feature="manage-plans" />
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </x-layout.page-container>

    <x-ui.confirm-modal 
        id="deleteModal" 
        title="Confirmar Exclusão" 
        message="Tem certeza que deseja excluir o plano <strong id='deleteModalItemName'></strong>?" 
        submessage="Esta ação não pode ser desfeita."
        confirmLabel="Excluir"
        variant="danger"
        type="delete" 
        resource="plano"
    />
</x-app-layout>
