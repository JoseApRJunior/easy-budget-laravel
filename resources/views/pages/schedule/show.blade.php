@extends( 'layouts.admin' )

@section( 'title', 'Detalhes do Agendamento' )

@section( 'content' )
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Detalhes do Agendamento #{{ $schedule->id }}</h3>
                            <div class="btn-group">
                                <a href="{{ route( 'provider.schedules.index' ) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-list"></i> Lista
                                </a>
                                <a href="{{ route( 'schedules.calendar' ) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-calendar-alt"></i> Calendário
                                </a>
                            </div>
                        </div>
                    </div>
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
                                        <td>{{ $schedule->start_date_time->format( 'd/m/Y H:i' ) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Data/Hora Término:</th>
                                        <td>{{ $schedule->end_date_time->format( 'd/m/Y H:i' ) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Local:</th>
                                        <td>{{ $schedule->location ?? 'Não definido' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            @if( $schedule->start_date_time > now() )
                                                <span class="badge badge-primary">Agendado</span>
                                            @elseif( $schedule->end_date_time < now() )
                                                <span class="badge badge-success">Concluído</span>
                                            @else
                                                <span class="badge badge-warning">Em Andamento</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Criado em:</th>
                                        <td>{{ $schedule->created_at->format( 'd/m/Y H:i' ) }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h5>Informações do Serviço</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Código:</th>
                                        <td>
                                            <a href="{{ route( 'services.show', $schedule->service ) }}">
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
                                            <a href="{{ route( 'customers.show', $schedule->service->customer ) }}">
                                                {{ $schedule->service->customer->name }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                @if( $schedule->userConfirmationToken )
                                    <h5 class="mt-3">Token de Confirmação</h5>
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Token:</th>
                                            <td><code>{{ $schedule->userConfirmationToken->token }}</code></td>
                                        </tr>
                                        <tr>
                                            <th>Expira em:</th>
                                            <td>{{ $schedule->userConfirmationToken->expires_at->format( 'd/m/Y H:i' ) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Link Público:</th>
                                            <td>
                                                <a href="{{ route( 'services.view-status', [ 'code' => $schedule->service->code, 'token' => $schedule->userConfirmationToken->token ] ) }}"
                                                    target="_blank" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-external-link-alt"></i> Ver Status
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                @endif
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="btn-group">
                                    @can( 'update', $schedule )
                                        <a href="{{ route( 'schedules.edit', $schedule ) }}" class="btn btn-warning">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                    @endcan

                                    @can( 'delete', $schedule )
                                        <form action="{{ route( 'schedules.destroy', $schedule ) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method( 'DELETE' )
                                            <button type="submit" class="btn btn-danger"
                                                onclick="return confirm('Tem certeza que deseja excluir este agendamento?')">
                                                <i class="fas fa-trash"></i> Excluir
                                            </button>
                                        </form>
                                    @endcan

                                    <a href="{{ route( 'services.show', $schedule->service ) }}" class="btn btn-info">
                                        <i class="fas fa-arrow-left"></i> Ver Serviço
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
