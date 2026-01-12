@extends('layouts.admin')

@section('title', 'Detalhes do Agendamento')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Detalhes do Agendamento #{{ $schedule->id }}"
            icon="calendar-check"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Agendamentos' => route('provider.schedules.index'),
                'Detalhes' => '#'
            ]">
            <div class="d-flex gap-2">
                <x-ui.button type="link" :href="route('provider.schedules.index')" variant="secondary" icon="arrow-left" label="Voltar" />
                <x-ui.button type="link" :href="route('provider.schedules.edit', $schedule)" variant="primary" icon="pencil" label="Editar" />
            </div>
        </x-layout.page-header>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Informações do Agendamento</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <th>ID:</th>
                                        <td>{{ $schedule->id }}</td>
                                    </tr>
                                    <tr>
                                        <th>Data/Hora Início:</th>
                                        <td>{{ $schedule->start_date_time->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Data/Hora Término:</th>
                                        <td>{{ $schedule->end_date_time->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Local:</th>
                                        <td>{{ $schedule->location ?? 'Não definido' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            @if ($schedule->start_date_time > now())
                                                <span class="badge badge-primary">Agendado</span>
                                            @elseif($schedule->end_date_time < now())
                                                <span class="badge badge-success">Concluído</span>
                                            @else
                                                <span class="badge badge-warning">Em Andamento</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Criado em:</th>
                                        <td>{{ $schedule->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h5>Informações do Serviço</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Código:</th>
                                        <td>
                                            <a href="{{ route('services.show', $schedule->service) }}">
                                                {{ $schedule->service->code }}
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Título:</th>
                                        <td>{{ $schedule->service->title }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status do Serviço:</th>
                                        <td>
                                            <span class="badge badge-{{ $schedule->service->status->getBadgeClass() }}">
                                                {{ $schedule->service->status->label() }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Cliente:</th>
                                        <td>
                                            <a href="{{ route('customers.show', $schedule->service->customer) }}">
                                                {{ $schedule->service->customer->name }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                @if ($schedule->userConfirmationToken)
                                    <h5 class="mt-3">Token de Confirmação</h5>
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Token:</th>
                                            <td><code>{{ $schedule->userConfirmationToken->token }}</code></td>
                                        </tr>
                                        <tr>
                                            <th>Expira em:</th>
                                            <td>{{ $schedule->userConfirmationToken->expires_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Link Público:</th>
                                            <td>
                                                <x-ui.button 
                                                    href="{{ route('services.view-status', ['code' => $schedule->service->code, 'token' => $schedule->userConfirmationToken->token]) }}" 
                                                    target="_blank" 
                                                    variant="outline-info" 
                                                    size="sm"
                                                    icon="fas fa-external-link-alt">
                                                    Ver Status
                                                </x-ui.button>
                                            </td>
                                        </tr>
                                    </table>
                                @endif
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <x-ui.button 
                                href="{{ url()->previous() }}" 
                                variant="secondary"
                                icon="bi bi-arrow-left">
                                Voltar
                            </x-ui.button>
                            <small class="text-muted d-none d-md-block">
                                Criado em: {{ $schedule->created_at->format('d/m/Y H:i') }}
                            </small>
                            <div class="d-flex gap-2">
                                @can('update', $schedule)
                                    <x-ui.button 
                                        href="{{ route('schedules.edit', $schedule) }}" 
                                        variant="warning"
                                        icon="bi bi-pencil">
                                        Editar
                                    </x-ui.button>
                                @endcan
                                @can('delete', $schedule)
                                    <form action="{{ route('schedules.destroy', $schedule) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button 
                                            type="submit"
                                            variant="danger"
                                            icon="bi bi-trash"
                                            onclick="return confirm('Tem certeza que deseja excluir este agendamento?')">
                                            Excluir
                                        </x-ui.button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
