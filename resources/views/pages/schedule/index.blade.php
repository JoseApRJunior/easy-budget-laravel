@extends('layouts.app')

@section('title', 'Agendamentos')

@section('content')
    <div class="container-fluid py-4">
        <x-page-header
            title="Agendamentos"
            icon="calendar-check"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Agendamentos' => '#'
            ]">
            <div class="d-flex gap-2">
                <x-button type="link" :href="route('provider.schedules.calendar')" variant="secondary" icon="calendar3" label="Ver Calendário" />
                <x-button type="link" :href="route('provider.schedules.create')" variant="primary" icon="plus-circle" label="Novo Agendamento" />
            </div>
        </x-page-header>

        <div class="row">
            <div class="col-12">
                <!-- Filtros de Busca -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('provider.schedules.index') }}">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="date_from">Data Inicial</label>
                                        <input type="date" class="form-control" id="date_from" name="date_from"
                                            value="{{ request('date_from', date('Y-m-d')) }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="date_to">Data Final</label>
                                        <input type="date" class="form-control" id="date_to" name="date_to"
                                            value="{{ request('date_to', date('Y-m-d', strtotime('+30 days'))) }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="per_page" class="text-nowrap">Por página</label>
                                        <select class="form-control" id="per_page" name="per_page">
                                            @php($pp = (int) (request('per_page') ?? 10))
                                            <option value="10" {{ $pp === 10 ? 'selected' : '' }}>10</option>
                                            <option value="20" {{ $pp === 20 ? 'selected' : '' }}>20</option>
                                            <option value="50" {{ $pp === 50 ? 'selected' : '' }}>50</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex gap-2 flex-nowrap">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-search me-1"></i>Filtrar
                                        </button>
                                        <a href="{{ route('provider.schedules.index') }}" class="btn btn-secondary">
                                            <i class="bi bi-x me-1"></i>Limpar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                                <h5 class="mb-0 d-flex align-items-center flex-wrap">
                                    <span class="me-2">
                                        <i class="bi bi-list-ul me-1"></i>
                                        <span class="d-none d-sm-inline">Lista de Agendamentos</span>
                                        <span class="d-sm-none">Agendamentos</span>
                                    </span>
                                    <span class="text-muted" style="font-size: 0.875rem;">
                                        @if ($schedules instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                            ({{ $schedules->total() }})
                                        @else
                                            ({{ $schedules->count() }})
                                        @endif
                                    </span>
                                </h5>
                            </div>
                            <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                                <div class="d-flex justify-content-start justify-content-lg-end gap-2">
                                    <a href="{{ route('provider.schedules.calendar') }}"
                                        class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-calendar me-1"></i> Calendário
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Mobile View -->
                        <div class="mobile-view">
                            <div class="list-group list-group-flush">
                                @forelse($schedules as $schedule)
                                    <a href="{{ route('provider.schedules.show', $schedule->id) }}"
                                        class="list-group-item list-group-item-action py-3">
                                        <div class="d-flex align-items-start">
                                            <i class="bi bi-calendar-check text-muted me-2 mt-1"></i>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold mb-2">
                                                    {{ $schedule->service->description ?? $schedule->service->code }}
                                                </div>
                                                <div class="d-flex gap-2 flex-wrap mb-2">
                                                    <span class="badge bg-primary-subtle text-primary">
                                                        {{ $schedule->service->customer->commonData->first_name ?? ($schedule->service->customer->name ?? 'N/A') }}
                                                    </span>
                                                    <span
                                                        class="badge {{ $schedule->status ? 'bg-secondary' : 'bg-info-subtle text-info' }}">
                                                        {{ $schedule->status ? ucfirst(str_replace('_', ' ', $schedule->status)) : 'Agendado' }}
                                                    </span>
                                                </div>
                                                <small class="text-muted">
                                                    {{ \Carbon\Carbon::parse($schedule->start_date_time)->format('d/m/Y H:i') }}
                                                    -
                                                    {{ \Carbon\Carbon::parse($schedule->end_date_time)->format('H:i') }}
                                                </small>
                                                @if ($schedule->location)
                                                    <br><small class="text-muted">{{ $schedule->location }}</small>
                                                @endif
                                            </div>
                                            <i class="bi bi-chevron-right text-muted ms-2"></i>
                                        </div>
                                    </a>
                                @empty
                                    <div class="p-4 text-center text-muted">
                                        <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                        <br>
                                        Nenhum agendamento encontrado.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Versão Desktop: Tabela -->
                        <div class="desktop-view">
                            <div class="table-responsive">
                                <table class="modern-table table mb-0">
                                    <thead>
                                        <tr>
                                            <th width="60"><i class="bi bi-calendar-check" aria-hidden="true"></i>
                                            </th>
                                            <th>Serviço</th>
                                            <th>Cliente</th>
                                            <th>Data/Hora Início</th>
                                            <th>Data/Hora Fim</th>
                                            <th>Local</th>
                                            <th width="120">Status</th>
                                            <th width="150" class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($schedules as $schedule)
                                            <tr>
                                                <td>
                                                    <div class="item-icon">
                                                        <i class="bi bi-calendar-check"></i>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="item-name-cell">
                                                        <a
                                                            href="{{ route('provider.services.show', $schedule->service->code) }}">
                                                            {{ $schedule->service->description ?? $schedule->service->code }}
                                                        </a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="item-name-cell">
                                                        <a
                                                            href="{{ route('provider.customers.show', $schedule->service->customer->id) }}">
                                                            {{ $schedule->service->customer->commonData->first_name ?? ($schedule->service->customer->name ?? 'N/A') }}
                                                        </a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ \Carbon\Carbon::parse($schedule->start_date_time)->format('d/m/Y H:i') }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ \Carbon\Carbon::parse($schedule->end_date_time)->format('d/m/Y H:i') }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ $schedule->location ?? 'Não definido' }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="modern-badge badge-secondary">
                                                        {{ $schedule->status ? ucfirst(str_replace('_', ' ', $schedule->status)) : 'Agendado' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <x-button type="link" :href="route('provider.schedules.show', $schedule->id)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                        @php($canEdit = true)
                                                        @php($canDelete = true)
                                                        @if ($canEdit)
                                                            <x-button type="link" :href="route('provider.schedules.edit', $schedule->id)" variant="primary" size="sm" icon="pencil" title="Editar" />
                                                        @endif
                                                        @if ($canDelete)
                                                            <x-button type="button" variant="danger" size="sm" icon="trash"
                                                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                                data-delete-url="{{ route('provider.schedules.destroy', $schedule->id) }}"
                                                                data-schedule-name="{{ $schedule->service->description ?? $schedule->service->code }}"
                                                                title="Excluir" />
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">
                                                    <i class="bi bi-inbox mb-2" aria-hidden="true"
                                                        style="font-size: 2rem;"></i>
                                                    <br>
                                                    Nenhum agendamento encontrado.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @if ($schedules instanceof \Illuminate\Pagination\LengthAwarePaginator && $schedules->hasPages())
                        @include('partials.components.paginator', [
                            'p' => $schedules->appends(request()->query()),
                            'show_info' => true,
                        ])
                    @endif
                </div>
            </div>
        </div>

        @if (isset($upcomingSchedules) && $upcomingSchedules->count() > 0)
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0">
                                <i class="bi bi-clock me-2"></i>
                                <span class="d-none d-sm-inline">Próximos Agendamentos</span>
                                <span class="d-sm-none">Próximos</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                @foreach ($upcomingSchedules as $schedule)
                                    <a href="{{ route('provider.schedules.show', $schedule) }}"
                                        class="list-group-item list-group-item-action border-0 px-0">
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">
                                                    {{ $schedule->service->description ?? $schedule->service->code }}</h6>
                                                <p class="mb-1 text-muted small">
                                                    {{ $schedule->service->customer->commonData->first_name ?? ($schedule->service->customer->name ?? 'N/A') }}
                                                </p>
                                                <small class="text-muted">
                                                    {{ $schedule->start_date_time->format('d/m/Y H:i') }} -
                                                    {{ $schedule->end_date_time->format('H:i') }}
                                                </small>
                                            </div>
                                            <small class="text-muted">
                                                {{ $schedule->start_date_time->diffForHumans() }}
                                            </small>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal de Confirmação de Exclusão -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        Tem certeza de que deseja excluir o agendamento <strong id="deleteScheduleName"></strong>?
                        <br><small class="text-muted">Esta ação não pode ser desfeita.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <form id="deleteForm" action="#" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Excluir</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Modal de confirmação de exclusão
                    const deleteModal = document.getElementById('deleteModal');
                    if (deleteModal) {
                        deleteModal.addEventListener('show.bs.modal', function(event) {
                            const button = event.relatedTarget;
                            const deleteUrl = button.getAttribute('data-delete-url');
                            const scheduleName = button.getAttribute('data-schedule-name');

                            const deleteForm = document.getElementById('deleteForm');
                            const scheduleNameElement = document.getElementById('deleteScheduleName');

                            deleteForm.action = deleteUrl;
                            scheduleNameElement.textContent = scheduleName;
                        });
                    }
                });
            </script>
        @endpush
    </div>
@endsection
