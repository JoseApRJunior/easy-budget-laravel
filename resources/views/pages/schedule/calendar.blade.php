@extends('layouts.app')

@section('title', 'Calendário de Agendamentos')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Calendário de Agendamentos"
            icon="calendar3"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Agendamentos' => route('provider.schedules.index'),
                'Calendário' => '#'
            ]">
            <x-slot:actions>
                <div class="d-flex gap-2">
                    <x-ui.button type="link" :href="route('provider.schedules.index')" variant="secondary" icon="list-ul" label="Ver Lista" />
                    {{-- Note: Create usually requires a service context, so maybe this button should be conditional or link to service selection --}}
                </div>
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <div class="col-12">
                <x-ui.card>
                    <x-slot:header>
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <h5 class="mb-0 text-primary fw-bold">
                                <i class="bi bi-calendar me-2"></i>Visualização em Calendário
                            </h5>
                            <x-ui.button type="link" :href="route('provider.schedules.index')" variant="outline-primary" size="sm" icon="list-ul" label="Lista" />
                        </div>
                    </x-slot:header>
                    
                    <div id='calendar'></div>
                </x-ui.card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection

@push('styles')
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <style>
        .fc-event {
            cursor: pointer;
        }
        .fc-toolbar-title {
            font-size: 1.25rem !important;
            text-transform: capitalize;
        }
        .fc-button-primary {
            background-color: var(--bs-primary) !important;
            border-color: var(--bs-primary) !important;
        }
        .fc-button-active {
            background-color: var(--bs-primary-dark) !important;
            border-color: var(--bs-primary-dark) !important;
        }
    </style>
@endpush

@push('scripts')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.min.js'></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                themeSystem: 'bootstrap5',
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
                        // Using a simple alert or toast here would be better, but sticking to basic alert for now to ensure functionality
                        console.error('Erro ao carregar agendamentos!');
                    }
                },
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    if (info.event.url) {
                        window.location.href = info.event.url;
                    }
                },
                eventDidMount: function(info) {
                    if (info.event.extendedProps.location) {
                        info.el.title = 'Local: ' + info.event.extendedProps.location;
                        // Add tooltip functionality if bootstrap tooltips are enabled globally
                        // new bootstrap.Tooltip(info.el); 
                    }
                }
            });

            calendar.render();
        });
    </script>
@endpush
