<x-app-layout title="Gestão de Empresas">
    <x-layout.page-container>
        <x-layout.page-header
            title="Gestão de Empresas"
            icon="building"
            :breadcrumb-items="[
                'Admin' => route('admin.dashboard'),
                'Gestão de Empresas' => '#'
            ]">
            <x-slot:actions>
                <div class="d-flex gap-2">
                    <x-ui.button type="button" variant="secondary" outline icon="download" label="Exportar" onclick="exportData()" />
                    <x-ui.button :href="route('admin.enterprises.create')" variant="primary" icon="plus-circle" label="Nova Empresa" />
                </div>
            </x-slot:actions>
        </x-layout.page-header>

        <!-- Cards de Estatísticas -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <x-ui.card class="border-left-primary shadow h-100 py-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total de Empresas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalEnterprises">
                                {{ $statistics['total_enterprises'] }}
                            </div>
                            <div class="text-xs text-muted">
                                <span id="newThisMonth">{{ $statistics['new_this_month'] }}</span> novas este mês
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <x-ui.card class="border-left-success shadow h-100 py-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Empresas Ativas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeEnterprises">
                                {{ $statistics['active_enterprises'] }}
                            </div>
                            <div class="text-xs text-muted">
                                Taxa de ativação: {{ number_format($statistics['activation_rate'], 1) }}%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <x-ui.card class="border-left-warning shadow h-100 py-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Suspensas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="suspendedEnterprises">
                                {{ $statistics['suspended_enterprises'] }}
                            </div>
                            <div class="text-xs text-muted">
                                Requerem atenção
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-pause-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <x-ui.card class="border-left-info shadow h-100 py-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Receita Mensal
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="monthlyRevenue">
                                R$ {{ number_format($statistics['revenue_this_month'], 2, ',', '.') }}
                            </div>
                            <div class="text-xs text-muted">
                                Média: R$ {{ number_format($statistics['avg_revenue_per_enterprise'], 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <!-- Card de Filtros (SEPARADO) -->
        <x-ui.card class="mb-4">
            <form method="GET">
                <div class="row g-3">
                    <div class="col-md-3">
                        <x-ui.form.select 
                            name="status" 
                            label="Status" 
                            :options="[
                                'active' => 'Ativo',
                                'inactive' => 'Inativo',
                                'suspended' => 'Suspenso'
                            ]"
                            :selected="request('status')"
                            placeholder="Todos os Status"
                        />
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="plan" class="form-label">Plano</label>
                            <select class="form-control" name="plan" id="plan">
                                <option value="">Todos os Planos</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}"
                                        {{ request('plan') == $plan->id ? 'selected' : '' }}>{{ $plan->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <x-ui.form.input type="date" name="date_from" label="Data Início" :value="request('date_from')" />
                    </div>
                    <div class="col-md-3">
                        <x-ui.form.input type="date" name="date_to" label="Data Fim" :value="request('date_to')" />
                    </div>
                </div>
                <div class="row mt-3 g-3">
                    <div class="col-md-6">
                        <x-ui.form.input name="search" label="Buscar" placeholder="Buscar por nome ou email..." :value="request('search')" />
                    </div>
                    <div class="col-md-3">
                        <x-ui.form.input type="number" name="min_revenue" label="Receita Mínima" placeholder="0.00" step="0.01" :value="request('min_revenue')" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label d-block">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <x-ui.button type="submit" variant="primary" icon="search" label="Filtrar" />
                            <x-ui.button type="link" :href="route('admin.enterprises.index')" variant="secondary" icon="x-circle" label="Limpar" />
                        </div>
                    </div>
                </div>
            </form>
        </x-ui.card>

        <!-- Card Principal -->
        <x-ui.card>
            @if ($enterprises->count() > 0)
                <!-- Tabela responsiva -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Plano</th>
                                <th>Status</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($enterprises as $enterprise)
                                <tr>
                                    <td>{{ $enterprise->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">{{ $enterprise->name }}</div>
                                                <small class="text-muted">{{ $enterprise->document }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $enterprise->email }}</td>
                                    <td>
                                        @if ($enterprise->plan)
                                            <span class="badge bg-info">{{ $enterprise->plan->name }}</span>
                                        @else
                                            <span class="badge bg-secondary">Sem Plano</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($enterprise->status)
                                            @case('active')
                                                <span class="badge bg-success">Ativo</span>
                                            @break

                                            @case('inactive')
                                                <span class="badge bg-warning">Inativo</span>
                                            @break

                                            @case('suspended')
                                                <span class="badge bg-danger">Suspenso</span>
                                            @break

                                            @default
                                                <span class="badge bg-secondary">{{ $enterprise->status }}</span>
                                        @endswitch
                                    </td>
                                    <td>{{ $enterprise->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <x-ui.button type="link" :href="route('admin.enterprises.show', $enterprise->id)" variant="info" size="sm" icon="eye" title="Ver" />
                                            <x-ui.button type="link" :href="route('admin.enterprises.edit', $enterprise->id)" variant="primary" size="sm" icon="pencil-square" title="Editar" />
                                            @if ($enterprise->status === 'active')
                                                <x-ui.button variant="danger" size="sm" icon="pause" title="Suspender"
                                                    onclick="suspendEnterprise({{ $enterprise->id }}, '{{ $enterprise->name }}')" />
                                            @else
                                                <x-ui.button variant="success" size="sm" icon="play" title="Reativar"
                                                    onclick="reactivateEnterprise({{ $enterprise->id }}, '{{ $enterprise->name }}')" />
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile view -->
                <div class="d-md-none">
                    @foreach ($enterprises as $enterprise)
                        <x-ui.card class="mb-3">
                            <h5 class="card-title">{{ $enterprise->name }}</h5>
                            <p class="card-text">
                                <strong>ID:</strong> {{ $enterprise->id }}<br>
                                <strong>Email:</strong> {{ $enterprise->email }}<br>
                                <strong>Plano:</strong>
                                @if ($enterprise->plan)
                                    <span class="badge bg-info">{{ $enterprise->plan->name }}</span>
                                @else
                                    <span class="badge bg-secondary">Sem Plano</span>
                                @endif
                                <br>
                                <strong>Status:</strong>
                                @switch($enterprise->status)
                                    @case('active')
                                        <span class="badge bg-success">Ativo</span>
                                    @break

                                    @case('inactive')
                                        <span class="badge bg-warning">Inativo</span>
                                    @break

                                    @case('suspended')
                                        <span class="badge bg-danger">Suspenso</span>
                                    @break

                                    @default
                                        <span class="badge bg-secondary">{{ $enterprise->status }}</span>
                                @endswitch
                                <br>
                                <strong>Criado em:</strong> {{ $enterprise->created_at->format('d/m/Y') }}
                            </p>
                            <div class="d-flex gap-2 mt-3">
                                <x-ui.button type="link" :href="route('admin.enterprises.show', $enterprise->id)" variant="info" size="sm" icon="eye" label="Ver" class="flex-grow-1" />
                                <x-ui.button type="link" :href="route('admin.enterprises.edit', $enterprise->id)" variant="primary" size="sm" icon="pencil-square" label="Editar" class="flex-grow-1" />
                                @if ($enterprise->status === 'active')
                                    <x-ui.button variant="danger" size="sm" icon="pause" label="Suspender" class="flex-grow-1"
                                        onclick="suspendEnterprise({{ $enterprise->id }}, '{{ $enterprise->name }}')" />
                                @else
                                    <x-ui.button variant="success" size="sm" icon="play" label="Reativar" class="flex-grow-1"
                                        onclick="reactivateEnterprise({{ $enterprise->id }}, '{{ $enterprise->name }}')" />
                                @endif
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>

                <!-- Paginação -->
                @if ($enterprises->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            <small class="text-muted">
                                Mostrando {{ $enterprises->firstItem() }} a {{ $enterprises->lastItem() }} de
                                {{ $enterprises->total() }} resultados
                            </small>
                        </div>
                        <div>
                            {{ $enterprises->links() }}
                        </div>
                        <div>
                            <select class="form-control form-control-sm" onchange="changePerPage(this.value)">
                                <option value="10" {{ $enterprises->perPage() == 10 ? 'selected' : '' }}>10 por
                                    página</option>
                                <option value="20" {{ $enterprises->perPage() == 20 ? 'selected' : '' }}>20 por
                                    página</option>
                                <option value="50" {{ $enterprises->perPage() == 50 ? 'selected' : '' }}>50 por
                                    página</option>
                            </select>
                        </div>
                    </div>
                @endif
            @else
                <!-- Empty State -->
                <div class="text-center py-5">
                    <i class="bi bi-building fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhuma empresa encontrada</h5>
                    <p class="text-muted">Não há empresas para exibir com os filtros aplicados.</p>
                    <x-ui.button type="link" :href="route('admin.enterprises.create')" variant="primary" icon="plus-circle" label="Criar Primeira Empresa" />
                </div>
            @endif
        </x-ui.card>
    </x-layout.page-container>
</x-app-layout>

@push('styles')
    <style>
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }

        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }

        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }

        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        function suspendEnterprise(id, name) {
            $('#confirmModalTitle').text('Suspender Empresa');
            $('#confirmModalBody').html(`
        <p>Tem certeza que deseja suspender a empresa <strong>${name}</strong>?</p>
        <p class="text-warning">
            <i class="bi bi-exclamation-triangle"></i>
            Esta ação suspenderá todos os usuários e serviços da empresa.
        </p>
    `);

            $('#confirmModalAction').removeClass().addClass('btn btn-warning').text('Suspender');
            $('#confirmModalAction').off('click').on('click', function() {
                executeAction(`/admin/enterprises/${id}/suspend`, 'POST', 'Empresa suspensa com sucesso!');
            });

            $('#confirmModal').modal('show');
        }

        function reactivateEnterprise(id, name) {
            $('#confirmModalTitle').text('Reativar Empresa');
            $('#confirmModalBody').html(`
        <p>Tem certeza que deseja reativar a empresa <strong>${name}</strong>?</p>
        <p class="text-info">
            <i class="bi bi-info-circle"></i>
            Esta ação reativará todos os usuários e serviços da empresa.
        </p>
    `);

            $('#confirmModalAction').removeClass().addClass('btn btn-success').text('Reativar');
            $('#confirmModalAction').off('click').on('click', function() {
                executeAction(`/admin/enterprises/${id}/reactivate`, 'POST', 'Empresa reativada com sucesso!');
            });

            $('#confirmModal').modal('show');
        }

        function executeAction(url, method, successMessage) {
            $.ajax({
                url: url,
                method: method,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#confirmModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    alert('Erro ao executar ação: ' + (xhr.responseJSON?.message || 'Erro desconhecido'));
                }
            });
        }

        function exportData() {
            window.location.href = '/admin/enterprises/export';
        }

        function changePerPage(perPage) {
            const url = new URL(window.location);
            url.searchParams.set('per_page', perPage);
            window.location = url.toString();
        }

        // DataTable initialization (opcional, pode ser removido se não necessário)
        $(document).ready(function() {
            // Inicializar DataTable apenas se necessário
            var table = $('#enterprisesTable');
            if (table.length) {
                table.DataTable({
                    responsive: true,
                    pageLength: 25,
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
                    },
                    order: [
                        [0, 'desc']
                    ]
                });
            }
        });
    </script>
@endpush
