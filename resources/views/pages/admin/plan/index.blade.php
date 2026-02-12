<x-app-layout title="Gerenciamento de Planos">
    <x-layout.page-container>
        <x-layout.page-header
            title="Gerenciamento de Planos"
            icon="box-seam"
            :breadcrumb-items="[
                'Dashboard' => route('admin.dashboard'),
                'Planos' => '#'
            ]">
            <x-slot:actions>
                <div class="d-flex gap-2">
                    <x-ui.button type="link" :href="route('admin.plans.create')" variant="primary" icon="plus-circle" label="Novo Plano" feature="manage-plans" />
                    <x-ui.button type="link" :href="route('admin.plans.export', ['format' => 'csv'])" variant="secondary" icon="download" label="Exportar" feature="manage-plans" />
                </div>
            </x-slot:actions>
        </x-layout.page-header>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <x-dashboard.stat-card 
                col="col-md-3"
                title="Total de Planos"
                :value="$stats['total']"
                icon="box-seam"
                variant="primary"
            />
            <x-dashboard.stat-card 
                col="col-md-3"
                title="Planos Ativos"
                :value="$stats['active']"
                icon="check-circle"
                variant="success"
            />
            <x-dashboard.stat-card 
                col="col-md-3"
                title="Assinaturas Ativas"
                :value="$stats['active_subscriptions']"
                icon="people"
                variant="info"
            />
            <x-dashboard.stat-card 
                col="col-md-3"
                title="Receita Mensal"
                :value="\App\Helpers\CurrencyHelper::format($stats['monthly_revenue'])"
                icon="currency-dollar"
                variant="warning"
            />
        </div>

        <!-- Search and Filter -->
        <x-ui.card class="mb-4">
            <x-slot:header>
                <h5 class="mb-0 text-primary fw-bold">
                    <i class="bi bi-funnel me-2"></i>Filtros
                </h5>
            </x-slot:header>
            <form method="GET" action="{{ route('admin.plans.index') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <x-ui.form.input 
                            name="search" 
                            id="search" 
                            label="Pesquisar" 
                            value="{{ $search }}" 
                            placeholder="Nome ou descrição..." 
                        />
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label small fw-bold text-muted text-uppercase">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Todos</option>
                            <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Ativo</option>
                            <option value="inactive" {{ $status == 'inactive' ? 'selected' : '' }}>Inativo</option>
                            <option value="draft" {{ $status == 'draft' ? 'selected' : '' }}>Rascunho</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="sort" class="form-label small fw-bold text-muted text-uppercase">Ordenar por</label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="name" {{ $sort == 'name' ? 'selected' : '' }}>Nome</option>
                            <option value="price" {{ $sort == 'price' ? 'selected' : '' }}>Preço</option>
                            <option value="status" {{ $sort == 'status' ? 'selected' : '' }}>Status</option>
                            <option value="created_at" {{ $sort == 'created_at' ? 'selected' : '' }}>Data de Criação</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="direction" class="form-label small fw-bold text-muted text-uppercase">Direção</label>
                        <select class="form-select" id="direction" name="direction">
                            <option value="asc" {{ $direction == 'asc' ? 'selected' : '' }}>Ascendente</option>
                            <option value="desc" {{ $direction == 'desc' ? 'selected' : '' }}>Descendente</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <x-ui.button type="link" :href="route('admin.plans.index')" variant="secondary" outline icon="x-circle" label="Limpar" />
                        <x-ui.button type="submit" variant="primary" icon="search" label="Filtrar" />
                    </div>
                </div>
            </form>
        </x-ui.card>

        <!-- Plans Table -->
        <x-resource.resource-list-card
            title="Lista de Planos"
            icon="list-ul"
            :total="$plans->total() ?? 0"
        >
            <x-resource.resource-table :headers="['ID', 'Nome', 'Descrição', 'Preço', 'Status', 'Assinaturas', 'Criado em', 'Ações']">
                @forelse($plans as $plan)
                    <x-resource.table-row>
                        <x-resource.table-cell>{{ $plan->id }}</x-resource.table-cell>
                        <x-resource.table-cell>
                            <strong>{{ $plan->name }}</strong>
                            @if($plan->slug)
                                <br><small class="text-muted">{{ $plan->slug }}</small>
                            @endif
                        </x-resource.table-cell>
                        <x-resource.table-cell>{{ Str::limit($plan->description, 50) }}</x-resource.table-cell>
                        <x-resource.table-cell>{{ \App\Helpers\CurrencyHelper::format($plan->price) }}</x-resource.table-cell>
                        <x-resource.table-cell>
                            @php
                                $statusClass = match($plan->status) {
                                    'active' => 'success',
                                    'inactive' => 'danger',
                                    'draft' => 'secondary',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $statusClass }}">{{ ucfirst($plan->status) }}</span>
                        </x-resource.table-cell>
                        <x-resource.table-cell>
                            <span class="badge bg-info">{{ $plan->planSubscriptions()->count() }}</span>
                        </x-resource.table-cell>
                        <x-resource.table-cell>{{ $plan->created_at ? $plan->created_at->format('d/m/Y') : 'N/A' }}</x-resource.table-cell>
                        <x-resource.table-cell>
                            <x-resource.action-buttons>
                                <x-ui.button :href="route('admin.plans.show', $plan)" variant="info" outline size="sm" icon="eye" title="Visualizar" feature="manage-plans" />
                                <x-ui.button :href="route('admin.plans.edit', $plan)" variant="primary" outline size="sm" icon="pencil-square" title="Editar" feature="manage-plans" />
                                <x-ui.button :href="route('admin.plans.subscribers', $plan)" variant="warning" outline size="sm" icon="people" title="Assinantes" feature="manage-plans" />
                                <x-ui.button 
                                    type="button" 
                                    variant="danger" 
                                    outline 
                                    size="sm" 
                                    icon="trash" 
                                    title="Excluir"
                                    :disabled="$plan->planSubscriptions()->exists()"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteModal" 
                                    data-delete-url="{{ route('admin.plans.destroy', $plan) }}"
                                    data-item-name="{{ $plan->name }}"
                                    feature="manage-plans"
                                />
                            </x-resource.action-buttons>
                        </x-resource.table-cell>
                    </x-resource.table-row>
                @empty
                    <x-resource.table-row>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-box-seam display-4 d-block mb-3"></i>
                            Nenhum plano encontrado
                        </td>
                    </x-resource.table-row>
                @endforelse
            </x-resource.resource-table>

            @if(method_exists($plans, 'links'))
                <div class="mt-4">
                    {{ $plans->links() }}
                </div>
            @endif
        </x-resource.resource-list-card>
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
