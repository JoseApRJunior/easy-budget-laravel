@extends( 'layouts.app' )

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
                                    <input type="date" name="date_from" id="start_date" class="form-control"
                                        value="{{ request('date_from', date('Y-m-d')) }}">
                                </div>
                                <div class="col-md-4">
                                    <label for="end_date">Data Final:</label>
                                    <input type="date" name="date_to" id="end_date" class="form-control"
                                        value="{{ request('date_to', date('Y-m-d', strtotime('+30 days'))) }}">
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
                                                <a href="{{ route( 'provider.services.show', $schedule->service->code ) }}">
                                                    {{ $schedule->service->description ?? $schedule->service->code }}
                                                </a>
                                            </td>
                                            <td>
                                                <a href="{{ route( 'provider.customers.show', $schedule->service->customer->id ) }}">
                                                    {{ $schedule->service->customer->commonData->first_name ?? $schedule->service->customer->name ?? 'N/A' }}
                                                </a>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($schedule->start_date_time)->format('d/m/Y H:i') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($schedule->end_date_time)->format('d/m/Y H:i') }}</td>
                                            <td>{{ $schedule->location ?? 'Não definido' }}</td>
                                            <td>
                                                <span class="badge bg-secondary">{{ ucfirst(str_replace('_',' ', $schedule->status ?? 'scheduled')) }}</span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route( 'provider.schedules.show', $schedule->id ) }}"
                                                        class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
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
