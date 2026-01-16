@extends('layouts.app')

@section('title', 'Detalhes do Agendamento')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Detalhes do Agendamento #{{ $schedule->id }}"
            icon="calendar-check"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Agendamentos' => route('provider.schedules.index'),
                'Detalhes' => '#'
            ]">
            <x-slot:actions>
                <div class="d-flex gap-2">
                    <x-ui.button type="link" :href="route('provider.schedules.index')" variant="secondary" icon="arrow-left" label="Voltar" />
                    <x-ui.button type="link" :href="route('provider.schedules.edit', $schedule)" variant="primary" icon="pencil" label="Editar" />
                </div>
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <div class="col-12">
                <x-ui.card>
                    <x-layout.grid-row>
                        <div class="col-md-6">
                            <h5 class="text-primary fw-bold mb-3 border-bottom pb-2">Informações do Agendamento</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <tr>
                                        <th class="w-50 text-muted small text-uppercase">ID</th>
                                        <td class="fw-bold">{{ $schedule->id }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted small text-uppercase">Data/Hora Início</th>
                                        <td>{{ $schedule->start_date_time->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted small text-uppercase">Data/Hora Término</th>
                                        <td>{{ $schedule->end_date_time->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted small text-uppercase">Local</th>
                                        <td>{{ $schedule->location ?? 'Não definido' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted small text-uppercase">Status</th>
                                        <td>
                                            @if ($schedule->start_date_time > now())
                                                <span class="badge bg-primary">Agendado</span>
                                            @elseif($schedule->end_date_time < now())
                                                <span class="badge bg-success">Concluído</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Em Andamento</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted small text-uppercase">Criado em</th>
                                        <td>{{ $schedule->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5 class="text-primary fw-bold mb-3 border-bottom pb-2">Informações do Serviço</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <tr>
                                        <th class="w-50 text-muted small text-uppercase">Código</th>
                                        <td>
                                            <a href="{{ route('provider.services.show', $schedule->service->code) }}" class="text-decoration-none fw-bold">
                                                {{ $schedule->service->code }}
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted small text-uppercase">Título</th>
                                        <td>{{ $schedule->service->title }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted small text-uppercase">Status do Serviço</th>
                                        <td>
                                            <span class="badge bg-{{ $schedule->service->status->getBadgeClass() }}">
                                                {{ $schedule->service->status->label() }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted small text-uppercase">Cliente</th>
                                        <td>
                                            <a href="{{ route('provider.customers.show', $schedule->service->customer) }}" class="text-decoration-none fw-bold">
                                                {{ $schedule->service->customer->name }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            @if ($schedule->userConfirmationToken)
                                <h5 class="text-primary fw-bold mt-4 mb-3 border-bottom pb-2">Token de Confirmação</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <tr>
                                            <th class="w-50 text-muted small text-uppercase">Token</th>
                                            <td><code class="bg-light px-2 py-1 rounded">{{ $schedule->userConfirmationToken->token }}</code></td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted small text-uppercase">Expira em</th>
                                            <td>{{ $schedule->userConfirmationToken->expires_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted small text-uppercase">Link Público</th>
                                            <td>
                                                <x-ui.button 
                                                    href="{{ route('services.view-status', ['code' => $schedule->service->code, 'token' => $schedule->userConfirmationToken->token]) }}" 
                                                    target="_blank" 
                                                    variant="outline-info" 
                                                    size="sm"
                                                    icon="box-arrow-up-right"
                                                    label="Ver Status"
                                                />
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </x-layout.grid-row>

                    <!-- Footer -->
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <x-ui.button 
                            href="{{ url()->previous() }}" 
                            variant="secondary"
                            icon="arrow-left"
                            label="Voltar"
                        />
                        <small class="text-muted d-none d-md-block">
                            Última atualização: {{ $schedule->updated_at->format('d/m/Y H:i') }}
                        </small>
                        <div class="d-flex gap-2">
                            @can('update', $schedule)
                                <x-ui.button 
                                    href="{{ route('provider.schedules.edit', $schedule) }}" 
                                    variant="warning"
                                    icon="pencil"
                                    label="Editar"
                                />
                            @endcan
                            @can('delete', $schedule)
                                <x-ui.button 
                                    type="button"
                                    variant="danger"
                                    icon="trash"
                                    label="Excluir"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteModal" 
                                    data-delete-url="{{ route('provider.schedules.destroy', $schedule) }}"
                                    data-item-name="o agendamento #{{ $schedule->id }}"
                                />
                            @endcan
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>

    <x-ui.confirm-modal 
        id="deleteModal" 
        title="Confirmar Exclusão" 
        message="Tem certeza que deseja excluir <strong id='deleteModalItemName'></strong>?" 
        submessage="Esta ação não pode ser desfeita."
        confirmLabel="Excluir"
        variant="danger"
        type="delete" 
        resource="agendamento"
    />
@endsection
