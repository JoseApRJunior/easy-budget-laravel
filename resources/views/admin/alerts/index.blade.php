<x-app-layout title="Gerenciamento de Alertas">
    <x-layout.page-container>
        <x-layout.page-header
            title="Gerenciamento de Alertas"
            icon="exclamation-triangle"
            :breadcrumb-items="[
                'Admin' => route('admin.dashboard'),
                'Alertas' => '#'
            ]">
            <x-slot:actions>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <x-ui.button variant="secondary" outline class="dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false" icon="download" label="Exportar" feature="manage-alerts" />
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.alerts.export', 'excel') }}">
                                    <i class="bi bi-file-earmark-excel me-2 text-success"></i>Excel
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.alerts.export', 'csv') }}">
                                    <i class="bi bi-file-earmark-text me-2 text-primary"></i>CSV
                                </a>
                            </li>
                        </ul>
                    </div>
                    <x-ui.button :href="route('admin.alerts.create')" variant="primary" icon="plus-circle" label="Novo Alerta" feature="manage-alerts" />
                </div>
            </x-slot:actions>
        </x-layout.page-header>

        <!-- Cards de Estatísticas -->
        <x-layout.grid-row class="mb-4">
            <x-dashboard.stat-card 
                col="col-md-3"
                title="Total de Alertas" 
                :value="$stats['total']"
                icon="exclamation-triangle"
                variant="primary"
            />
            <x-dashboard.stat-card 
                col="col-md-3"
                title="Alertas Ativos" 
                :value="$stats['active']"
                icon="bell"
                variant="warning"
            />
            <x-dashboard.stat-card 
                col="col-md-3"
                title="Resolvidos" 
                :value="$stats['resolved']"
                icon="check-circle"
                variant="success"
            />
            <x-dashboard.stat-card 
                col="col-md-3"
                title="Críticos" 
                :value="$stats['critical']"
                icon="lightning"
                variant="danger"
            />
        </x-layout.grid-row>

        <!-- Filtros -->
        <x-ui.card class="mb-4">
            <div class="p-2">
                <form method="GET">
                    <x-layout.grid-row class="g-3">
                        <div class="col-md-3">
                            <x-ui.form.select name="type" label="Tipo" :selected="request('type')">
                                <option value="">Todos os Tipos</option>
                                <option value="system">Sistema</option>
                                <option value="security">Segurança</option>
                                <option value="performance">Performance</option>
                                <option value="business">Negócio</option>
                            </x-ui.form.select>
                        </div>
                        <div class="col-md-3">
                            <x-ui.form.select name="severity" label="Severidade" :selected="request('severity')">
                                <option value="">Todas</option>
                                <option value="danger">Crítico</option>
                                <option value="warning">Aviso</option>
                                <option value="info">Informativo</option>
                            </x-ui.form.select>
                        </div>
                        <div class="col-md-3">
                            <x-ui.form.select name="status" label="Status" :selected="request('status')">
                                <option value="">Todos</option>
                                <option value="active">Ativo</option>
                                <option value="resolved">Resolvido</option>
                            </x-ui.form.select>
                        </div>
                        <div class="col-md-3">
                            <x-ui.form.input type="date" name="date_from" label="Data Início" :value="request('date_from')" />
                        </div>
                        <div class="col-md-3">
                            <x-ui.form.input type="date" name="date_to" label="Data Fim" :value="request('date_to')" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.form.input name="search" label="Buscar" placeholder="Buscar por título ou mensagem..." :value="request('search')" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label d-none d-md-block">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <x-ui.button type="submit" variant="primary" icon="search" label="Filtrar" class="flex-grow-1" feature="manage-alerts" />
                                <x-ui.button :href="route('admin.alerts.index')" variant="secondary" icon="x-circle" label="Limpar" feature="manage-alerts" />
                            </div>
                        </div>
                    </x-layout.grid-row>
                </form>
            </div>
        </x-ui.card>

        <!-- Lista de Alertas -->
        <x-resource.resource-list-card
            title="Lista de Alertas"
            icon="list-ul"
            :total="$alerts->total()"
            :actions="[]"
        >
            <x-resource.resource-table :headers="['ID', 'Tipo', 'Título', 'Mensagem', 'Severidade', 'Status', 'Data', 'Ações']">
                @forelse($alerts as $alert)
                    <x-resource.table-row>
                        <x-resource.table-cell>{{ $alert['id'] }}</x-resource.table-cell>
                        <x-resource.table-cell>
                            <span class="badge bg-secondary">{{ ucfirst($alert['type']) }}</span>
                        </x-resource.table-cell>
                        <x-resource.table-cell><strong>{{ $alert['title'] }}</strong></x-resource.table-cell>
                        <x-resource.table-cell>{{ Str::limit($alert['message'], 50) }}</x-resource.table-cell>
                        <x-resource.table-cell>
                            @php
                                $badgeClass = match ($alert['severity']) {
                                    'danger' => 'bg-danger',
                                    'warning' => 'bg-warning text-dark',
                                    'info' => 'bg-info',
                                    default => 'bg-secondary',
                                };
                                $severityText = match ($alert['severity']) {
                                    'danger' => 'Crítico',
                                    'warning' => 'Aviso',
                                    'info' => 'Informativo',
                                    default => 'Desconhecido',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $severityText }}</span>
                        </x-resource.table-cell>
                        <x-resource.table-cell>
                            @php
                                $statusClass = match ($alert['status']) {
                                    'active' => 'bg-success',
                                    'resolved' => 'bg-secondary',
                                    default => 'bg-light text-dark',
                                };
                                $statusText = match ($alert['status']) {
                                    'active' => 'Ativo',
                                    'resolved' => 'Resolvido',
                                    default => 'Desconhecido',
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                        </x-resource.table-cell>
                        <x-resource.table-cell>{{ $alert['created_at']->format('d/m/Y H:i') }}</x-resource.table-cell>
                        <x-resource.table-cell>
                            <x-resource.action-buttons>
                                <x-ui.button :href="route('admin.alerts.show', $alert['id'])" variant="info" size="sm" icon="eye" title="Ver Detalhes" feature="manage-alerts" />
                                <x-ui.button :href="route('admin.alerts.edit', $alert['id'])" variant="primary" size="sm" icon="pencil-square" title="Editar" feature="manage-alerts" />
                                <x-ui.button 
                                    type="button" 
                                    variant="danger" 
                                    size="sm" 
                                    icon="trash" 
                                    title="Excluir"
                                    onclick="confirmDelete({{ $alert['id'] }})" 
                                    feature="manage-alerts"
                                />
                            </x-resource.action-buttons>
                        </x-resource.table-cell>
                    </x-resource.table-row>
                @empty
                    <x-resource.table-row>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
                            <h5 class="text-muted">Nenhum alerta encontrado</h5>
                            <p class="text-muted mb-4">Não há alertas para exibir com os filtros aplicados.</p>
                            <x-ui.button :href="route('admin.alerts.create')" variant="primary" icon="plus-circle" label="Criar Primeiro Alerta" feature="manage-alerts" />
                        </td>
                    </x-resource.table-row>
                @endforelse
            </x-resource.resource-table>

            @if ($alerts->hasPages())
                <div class="mt-4 d-flex justify-content-between align-items-center">
                    <div>
                        {{ $alerts->links() }}
                    </div>
                    <div>
                        <x-ui.form.select name="per_page" class="form-control-sm" onchange="changePerPage(this.value)" :selected="$alerts->perPage()">
                            <option value="10">10 por página</option>
                            <option value="20">20 por página</option>
                            <option value="50">50 por página</option>
                        </x-ui.form.select>
                    </div>
                </div>
            @endif
        </x-resource.resource-list-card>
    </x-layout.page-container>

    <!-- Modal de confirmação de exclusão -->
    <x-ui.confirm-modal 
        id="deleteModal" 
        title="Confirmar Exclusão" 
        message="Tem certeza que deseja excluir este alerta?" 
        submessage="Esta ação não pode ser desfeita."
        confirmLabel="Excluir"
        variant="danger"
        type="delete" 
    />

    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</x-app-layout>

@push('scripts')
    <script>
        function confirmDelete(id) {
            const form = document.getElementById('deleteForm');
            form.action = `/admin/alerts/${id}`;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
            
            // Vincular o botão de confirmação do modal ao envio do formulário
            const confirmBtn = document.querySelector('#deleteModal .btn-danger');
            confirmBtn.onclick = function() {
                form.submit();
            };
        }

        function changePerPage(perPage) {
            const url = new URL(window.location);
            url.searchParams.set('per_page', perPage);
            window.location = url.toString();
        }
    </script>
@endpush
