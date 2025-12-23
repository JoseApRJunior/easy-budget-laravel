@extends('layouts.app')

@section('title', 'Calendário de Agendamentos')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-calendar-check me-2"></i>
                    Calendário de Agendamentos
                </h1>
                <p class="text-muted">Visualização em calendário dos agendamentos do sistema</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('provider.schedules.index') }}">Agendamentos</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Calendário</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                                <h5 class="mb-0 d-flex align-items-center flex-wrap">
                                    <span class="me-2">
                                        <i class="bi bi-calendar me-1"></i>
                                        <span class="d-none d-sm-inline">Visualização em Calendário</span>
                                        <span class="d-sm-none">Calendário</span>
                                    </span>
                                </h5>
                            </div>
                            <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                                <div class="d-flex justify-content-start justify-content-lg-end gap-2">
                                    <a href="{{ route('provider.schedules.index') }}"
                                        class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-list-ul"></i>
                                        <span class="ms-1">Lista</span>
                                    </a>
                                                                 </div>
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

@push('scripts')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.min.js'></script>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
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
                    url: '{{ route('provider.schedules.calendar.data') }}',
                    method: 'GET',
                    failure: function() {
                        alert('Erro ao carregar agendamentos!');
                    }
                },
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    if (info.event.url) {
                        window.open(info.event.url, '_blank');
                    }
                },
                eventDidMount: function(info) {
                    if (info.event.extendedProps.location) {
                        info.el.title = 'Local: ' + info.event.extendedProps.location;
                    }
                }
            });

            calendar.render();
        });
    </script>
@endpush
