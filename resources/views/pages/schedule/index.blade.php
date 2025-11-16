@extends( 'layouts.admin' )

@section( 'title', 'Agendamentos' )

@section( 'content' )
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Agendamentos</h3>
                            <div class="btn-group">
                                <a href="{{ route( 'provider.schedules.calendar' ) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-calendar-alt"></i> Calendário
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route( 'provider.schedules.index' ) }}" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="start_date">Data Inicial:</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control"
                                        value="{{ $startDate ?? date( 'Y-m-d' ) }}">
                                </div>
                                <div class="col-md-4">
                                    <label for="end_date">Data Final:</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control"
                                        value="{{ $endDate ?? date( 'Y-m-d', strtotime( '+30 days' ) ) }}">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Serviço</th>
                                        <th>Cliente</th>
                                        <th>Data/Hora Início</th>
                                        <th>Data/Hora Fim</th>
                                        <th>Local</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse( $schedules as $schedule )
                                        <tr>
                                            <td>{{ $schedule->id }}</td>
                                            <td>
                                                <a href="{{ route( 'services.show', $schedule->service ) }}">
                                                    {{ $schedule->service->title }}
                                                </a>
                                            </td>
                                            <td>
                                                <a href="{{ route( 'customers.show', $schedule->service->customer ) }}">
                                                    {{ $schedule->service->customer->name }}
                                                </a>
                                            </td>
                                            <td>{{ $schedule->start_date_time->format( 'd/m/Y H:i' ) }}</td>
                                            <td>{{ $schedule->end_date_time->format( 'd/m/Y H:i' ) }}</td>
                                            <td>{{ $schedule->location ?? 'Não definido' }}</td>
                                            <td>
                                                @if( $schedule->start_date_time > now() )
                                                    <span class="badge badge-primary">Agendado</span>
                                                @elseif( $schedule->end_date_time < now() )
                                                    <span class="badge badge-success">Concluído</span>
                                                @else
                                                    <span class="badge badge-warning">Em Andamento</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route( 'provider.schedules.show', $schedule ) }}"
                                                        class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route( 'provider.schedules.edit', $schedule ) }}"
                                                        class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route( 'provider.schedules.destroy', $schedule ) }}" method="POST"
                                                        class="d-inline">
                                                        @csrf
                                                        @method( 'DELETE' )
                                                        <button type="submit" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Tem certeza que deseja excluir este agendamento?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">Nenhum agendamento encontrado</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if( isset( $upcomingSchedules ) && $upcomingSchedules->count() > 0 )
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Próximos Agendamentos</h4>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                @foreach( $upcomingSchedules as $schedule )
                                    <a href="{{ route( 'provider.schedules.show', $schedule ) }}"
                                        class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1">{{ $schedule->service->title }}</h5>
                                            <small>{{ $schedule->start_date_time->diffForHumans() }}</small>
                                        </div>
                                        <p class="mb-1">{{ $schedule->service->customer->name }}</p>
                                        <small>{{ $schedule->start_date_time->format( 'd/m/Y H:i' ) }} -
                                            {{ $schedule->end_date_time->format( 'H:i' ) }}</small>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
