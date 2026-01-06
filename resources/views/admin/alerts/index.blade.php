@extends('layouts.admin')

@section('content')
    <div class="container-fluid py-4">
        <x-page-header
            title="Gerenciamento de Alertas"
            icon="exclamation-triangle"
            :breadcrumb-items="[
                'Admin' => route('admin.dashboard'),
                'Alertas' => '#'
            ]">
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download me-1"></i> Exportar
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                        <li><a class="dropdown-item" href="{{ route('admin.alerts.export', 'excel') }}">
                                <i class="bi bi-file-earmark-excel me-2 text-success"></i>Excel
                            </a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.alerts.export', 'csv') }}">
                                <i class="bi bi-file-earmark-text me-2 text-primary"></i>CSV
                            </a></li>
                    </ul>
                </div>
                <x-button :href="route('admin.alerts.create')" variant="primary" icon="plus-circle" label="Novo Alerta" />
            </div>
        </x-page-header>

        <!-- Cards de Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total de Alertas</h6>
                                <h2 class="mb-0">{{ $stats['total'] }}</h2>
                            </div>
                            <i class="bi bi-exclamation-triangle fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Alertas Ativos</h6>
                                <h2 class="mb-0">{{ $stats['active'] }}</h2>
                            </div>
                            <i class="bi bi-bell fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Resolvidos</h6>
                                <h2 class="mb-0">{{ $stats['resolved'] }}</h2>
                            </div>
                            <i class="bi bi-check-circle fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Críticos</h6>
                                <h2 class="mb-0">{{ $stats['critical'] }}</h2>
                            </div>
                            <i class="bi bi-lightning fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card de Filtros (SEPARADO) -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="type">Tipo</label>
                                <select class="form-control" name="type" id="type">
                                    <option value="">Todos os Tipos</option>
                                    <option value="system">Sistema</option>
                                    <option value="security">Segurança</option>
                                    <option value="performance">Performance</option>
                                    <option value="business">Negócio</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="severity">Severidade</label>
                                <select class="form-control" name="severity" id="severity">
                                    <option value="">Todas</option>
                                    <option value="danger">Crítico</option>
                                    <option value="warning">Aviso</option>
                                    <option value="info">Informativo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" name="status" id="status">
                                    <option value="">Todos</option>
                                    <option value="active">Ativo</option>
                                    <option value="resolved">Resolvido</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date_from">Data Início</label>
                                <input type="date" class="form-control" name="date_from" id="date_from"
                                    value="{{ request('date_from') }}">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date_to">Data Fim</label>
                                <input type="date" class="form-control" name="date_to" id="date_to"
                                    value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="search">Buscar</label>
                                <input type="text" class="form-control" name="search" id="search"
                                    placeholder="Buscar por título ou mensagem..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <x-button type="submit" variant="primary" icon="search" label="Filtrar" />
                                    <x-button type="link" :href="route('admin.alerts.index')" variant="secondary" icon="x-circle" label="Limpar" />
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card Principal -->
        <div class="card">
            <div class="card-body">
                @if ($alerts->count() > 0)
                    <!-- Tabela responsiva -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Título</th>
                                    <th>Mensagem</th>
                                    <th>Severidade</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($alerts as $alert)
                                    <tr>
                                        <td>{{ $alert['id'] }}</td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ ucfirst($alert['type']) }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>{{ $alert['title'] }}</strong>
                                        </td>
                                        <td>
                                            {{ Str::limit($alert['message'], 50) }}
                                        </td>
                                        <td>
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
                                            <span class="badge {{ $badgeClass }}">
                                                {{ $severityText }}
                                            </span>
                                        </td>
                                        <td>
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
                                            <span class="badge {{ $statusClass }}">
                                                {{ $statusText }}
                                            </span>
                                        </td>
                                        <td>{{ $alert['created_at']->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <x-button type="link" :href="route('admin.alerts.show', $alert['id'])" variant="info" size="sm" icon="eye" title="Ver Detalhes" />
                                                <x-button type="link" :href="route('admin.alerts.edit', $alert['id'])" variant="primary" size="sm" icon="pencil-square" title="Editar" />
                                                <x-button variant="danger" size="sm" icon="trash" title="Excluir"
                                                    onclick="confirmDelete({{ $alert['id'] }})" />
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile view -->
                    <div class="d-md-none">
                        @foreach ($alerts as $alert)
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $alert['title'] }}</h5>
                                    <p class="card-text">
                                        <strong>ID:</strong> {{ $alert['id'] }}<br>
                                        <strong>Tipo:</strong>
                                        <span class="badge bg-secondary">{{ ucfirst($alert['type']) }}</span><br>
                                        <strong>Severidade:</strong>
                                        @php
                                            $badgeClass = match ($alert['severity']) {
                                                'danger' => 'bg-danger',
                                                'warning' => 'bg-warning text-dark',
                                                'info' => 'bg-info',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ $severityText ?? 'Desconhecido' }}
                                        </span><br>
                                        <strong>Status:</strong>
                                        @php
                                            $statusClass = match ($alert['status']) {
                                                'active' => 'bg-success',
                                                'resolved' => 'bg-secondary',
                                                default => 'bg-light text-dark',
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">
                                            {{ $statusText ?? 'Desconhecido' }}
                                        </span><br>
                                        <strong>Data:</strong> {{ $alert['created_at']->format('d/m/Y H:i') }}
                                    </p>
                                    <div class="d-flex gap-2 mt-3">
                                        <x-button type="link" :href="route('admin.alerts.show', $alert['id'])" variant="info" size="sm" icon="eye" label="Ver" class="flex-grow-1" />
                                        <x-button type="link" :href="route('admin.alerts.edit', $alert['id'])" variant="primary" size="sm" icon="pencil-square" label="Editar" class="flex-grow-1" />
                                        <x-button variant="danger" size="sm" icon="trash" label="Excluir" class="flex-grow-1"
                                            onclick="confirmDelete({{ $alert['id'] }})" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Paginação -->
                    @if ($alerts->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div>
                                <small class="text-muted">
                                    Mostrando {{ $alerts->firstItem() }} a {{ $alerts->lastItem() }} de
                                    {{ $alerts->total() }} resultados
                                </small>
                            </div>
                            <div>
                                {{ $alerts->links() }}
                            </div>
                            <div>
                                <select class="form-control form-control-sm" onchange="changePerPage(this.value)">
                                    <option value="10" {{ $alerts->perPage() == 10 ? 'selected' : '' }}>10 por página
                                    </option>
                                    <option value="20" {{ $alerts->perPage() == 20 ? 'selected' : '' }}>20 por página
                                    </option>
                                    <option value="50" {{ $alerts->perPage() == 50 ? 'selected' : '' }}>50 por página
                                    </option>
                                </select>
                            </div>
                        </div>
                    @endif
                @else
                    <!-- Empty State -->
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="text-muted">Nenhum alerta encontrado</h5>
                        <p class="text-muted mb-4">Não há alertas para exibir com os filtros aplicados.</p>
                        <x-button type="link" :href="route('admin.alerts.create')" variant="primary" icon="plus-circle" label="Criar Primeiro Alerta" />
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal de confirmação de exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja excluir este alerta? Esta ação não pode ser desfeita.
                </div>
                <div class="modal-footer">
                    <x-button variant="secondary" label="Cancelar" data-bs-dismiss="modal" />
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger" label="Excluir" />
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function confirmDelete(id) {
            const form = document.getElementById('deleteForm');
            form.action = `/admin/alerts/${id}`;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        function changePerPage(perPage) {
            const url = new URL(window.location);
            url.searchParams.set('per_page', perPage);
            window.location = url.toString();
        }

        // Adicionar animação aos cards de estatísticas
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';

                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    </script>
@endpush
