@extends( 'layouts.admin' )

@section( 'title', 'Calendário de Agendamentos' )

@section( 'content' )
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Calendário de Agendamentos</h3>
                            <div class="btn-group">
                                <a href="{{ route( 'provider.schedules.index' ) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-list"></i> Lista
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id='calendar'></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.min.js'></script>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />

    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            var calendarEl = document.getElementById( 'calendar' );
            var calendar = new FullCalendar.Calendar( calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Hoje',
                    month: 'Mês',
                    week: 'Semana',
                    day: 'Dia'
                },
                events: {
                    url: '{{ route( "provider.schedules.calendar.data" ) }}',
                    method: 'GET',
                    failure: function () {
                        alert( 'Erro ao carregar agendamentos!' );
                    }
                },
                eventClick: function ( info ) {
                    info.jsEvent.preventDefault();
                    if ( info.event.url ) {
                        window.open( info.event.url, '_blank' );
                    }
                },
                eventDidMount: function ( info ) {
                    if ( info.event.extendedProps.location ) {
                        info.el.title = 'Local: ' + info.event.extendedProps.location;
                    }
                }
            } );

            calendar.render();
        } );
    </script>
@endpush
