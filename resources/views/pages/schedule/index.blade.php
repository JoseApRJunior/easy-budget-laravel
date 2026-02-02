@extends('layouts.app')

@section('title', 'Agendamentos')

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Agendamentos"
        icon="calendar-check"
        :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Agendamentos' => '#'
            ]">
        <x-slot:actions>
            <div class="d-flex gap-2">
                <x-ui.button :href="route('provider.schedules.calendar')" variant="secondary" outline icon="calendar3" label="Ver Calendário" />
            </div>
        </x-slot:actions>
    </x-layout.page-header>

    <!-- Filtros de Busca -->
    <x-ui.card class="mb-4">
        <x-slot:header>
            <h5 class="mb-0 text-primary fw-bold">
                <i class="bi bi-filter me-2"></i>Filtros de Busca
            </h5>
        </x-slot:header>
        <form method="GET" action="{{ route('provider.schedules.index') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <x-ui.form.input
                        type="date"
                        name="date_from"
                        id="date_from"
                        label="Data Inicial"
                        value="{{ request('date_from', date('Y-m-d')) }}" />
                </div>
                <div class="col-md-3">
                    <x-ui.form.input
                        type="date"
                        name="date_to"
                        id="date_to"
                        label="Data Final"
                        value="{{ request('date_to', date('Y-m-d', strtotime('+30 days'))) }}" />
                </div>
                <div class="col-md-3">
                    <label for="per_page" class="form-label small fw-bold text-muted text-uppercase">Por página</label>
                    <select class="form-select" id="per_page" name="per_page">
                        @php($pp = (int) (request('per_page') ?? 10))
                        <option value="10" {{ $pp === 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ $pp === 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ $pp === 50 ? 'selected' : '' }}>50</option>
                    </select>
                </div>
                <div class="col-12">
                    <div class="d-flex gap-2 justify-content-end">
                        <x-ui.button :href="route('provider.schedules.index')" variant="secondary" outline icon="x" label="Limpar" />
                        <x-ui.button type="submit" variant="primary" icon="search" label="Filtrar" />
                    </div>
                </div>
            </div>
        </form>
    </x-ui.card>

    <x-layout.grid-row>
        <div class="col-12">
            <x-resource.resource-list-card
                title="Lista de Agendamentos"
                icon="list-ul"
                :total="$schedules instanceof \Illuminate\Pagination\LengthAwarePaginator ? $schedules->total() : $schedules->count()"
                :actions="[]">
                <x-resource.resource-table :headers="['Serviço', 'Cliente', 'Início', 'Fim', 'Local', 'Status', 'Ações']">
                    @forelse($schedules as $schedule)
                    <x-resource.table-row>
                        <x-resource.table-cell>
                            <a href="{{ route('provider.services.show', $schedule->service->code) }}" class="fw-bold text-decoration-none">
                                {{ $schedule->service->description ?? $schedule->service->code }}
                            </a>
                        </x-resource.table-cell>
                        <x-resource.table-cell>
                            <a href="{{ route('provider.customers.show', $schedule->service->customer->id) }}" class="text-decoration-none">
                                {{ $schedule->service->customer->commonData->first_name ?? ($schedule->service->customer->name ?? 'N/A') }}
                            </a>
                        </x-resource.table-cell>
                        <x-resource.table-cell>
                            {{ \Carbon\Carbon::parse($schedule->start_date_time)->format('d/m/Y H:i') }}
                        </x-resource.table-cell>
                        <x-resource.table-cell>
                            {{ \Carbon\Carbon::parse($schedule->end_date_time)->format('d/m/Y H:i') }}
                        </x-resource.table-cell>
                        <x-resource.table-cell>
                            {{ $schedule->location ?? 'N/A' }}
                        </x-resource.table-cell>
                        <x-resource.table-cell>
                            <span class="badge bg-{{ $schedule->status ? 'secondary' : 'info' }}">
                                {{ $schedule->status ? ucfirst(str_replace('_', ' ', $schedule->status)) : 'Agendado' }}
                            </span>
                        </x-resource.table-cell>
                        <x-resource.table-cell>
                            <x-resource.action-buttons>
                                <x-ui.button :href="route('provider.schedules.show', $schedule->id)" variant="info" outline size="sm" icon="eye" title="Visualizar" />
                                <x-ui.button :href="route('provider.schedules.edit', $schedule->id)" variant="primary" outline size="sm" icon="pencil" title="Editar" />
                                <x-ui.button
                                    type="button"
                                    variant="danger"
                                    outline
                                    size="sm"
                                    icon="trash"
                                    title="Excluir"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteModal"
                                    data-delete-url="{{ route('provider.schedules.destroy', $schedule->id) }}"
                                    data-item-name="{{ $schedule->service->description ?? $schedule->service->code }}" />
                            </x-resource.action-buttons>
                        </x-resource.table-cell>
                    </x-resource.table-row>
                    @empty
                    <x-resource.table-row>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x display-4 d-block mb-3"></i>
                            Nenhum agendamento encontrado
                        </td>
                    </x-resource.table-row>
                    @endforelse
                </x-resource.resource-table>

                @if($schedules instanceof \Illuminate\Pagination\LengthAwarePaginator && $schedules->hasPages())
                <div class="mt-4">
                    {{ $schedules->appends(request()->query())->links() }}
                </div>
                @endif
            </x-resource.resource-list-card>
        </div>
    </x-layout.grid-row>

    @if(isset($upcomingSchedules) && $upcomingSchedules->count() > 0)
    <div class="row mt-4">
        <div class="col-md-6">
            <x-ui.card>
                <x-slot:header>
                    <h5 class="mb-0 text-primary fw-bold">
                        <i class="bi bi-clock me-2"></i>Próximos Agendamentos
                    </h5>
                </x-slot:header>

                <div class="list-group list-group-flush">
                    @foreach ($upcomingSchedules as $schedule)
                    <a href="{{ route('provider.schedules.show', $schedule) }}"
                        class="list-group-item list-group-item-action border-0 px-0">
                        <div class="d-flex w-100 justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 text-dark fw-bold">
                                    {{ $schedule->service->description ?? $schedule->service->code }}
                                </h6>
                                <p class="mb-1 text-muted small">
                                    {{ $schedule->service->customer->commonData->first_name ?? ($schedule->service->customer->name ?? 'N/A') }}
                                </p>
                                <small class="text-primary fw-bold">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    {{ $schedule->start_date_time->format('d/m/Y H:i') }} -
                                    {{ $schedule->end_date_time->format('H:i') }}
                                </small>
                            </div>
                            <small class="text-muted badge bg-light text-dark border">
                                {{ $schedule->start_date_time->diffForHumans() }}
                            </small>
                        </div>
                    </a>
                    @endforeach
                </div>
            </x-ui.card>
        </div>
    </div>
    @endif
</x-layout.page-container>

<x-ui.confirm-modal
    id="deleteModal"
    title="Confirmar Exclusão"
    message="Tem certeza que deseja excluir o agendamento <strong id='deleteModalItemName'></strong>?"
    submessage="Esta ação não pode ser desfeita."
    confirmLabel="Excluir"
    variant="danger"
    type="delete"
    resource="agendamento" />
@endsection
