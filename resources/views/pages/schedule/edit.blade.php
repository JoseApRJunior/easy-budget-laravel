@extends('layouts.app')

@section('title', 'Editar Agendamento')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Editar Agendamento #{{ $schedule->id }}"
            icon="pencil"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Agendamentos' => route('provider.schedules.index'),
                'Editar' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button type="link" :href="route('provider.schedules.index')" variant="secondary" icon="arrow-left" label="Voltar" feature="schedules" />
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <div class="col-12">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-info-circle me-2"></i>Informações do Agendamento
                        </h5>
                    </x-slot:header>

                    <form method="POST" action="{{ route('provider.schedules.update', $schedule) }}" id="scheduleForm">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <x-ui.form.input
                                    type="datetime-local"
                                    name="start_date_time"
                                    id="start_date_time"
                                    label="Data e Hora de Início *"
                                    value="{{ old('start_date_time', $schedule->start_date_time->format('Y-m-d\TH:i')) }}"
                                    required
                                />
                            </div>

                            <div class="col-md-6">
                                <x-ui.form.input
                                    type="datetime-local"
                                    name="end_date_time"
                                    id="end_date_time"
                                    label="Data e Hora de Término *"
                                    value="{{ old('end_date_time', $schedule->end_date_time->format('Y-m-d\TH:i')) }}"
                                    required
                                />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <x-ui.form.input
                                    type="text"
                                    name="location"
                                    id="location"
                                    label="Local do Agendamento"
                                    value="{{ old('location', $schedule->location) }}"
                                    placeholder="Ex: Escritório do cliente, Sala de reunião, etc."
                                />
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="alert alert-warning d-flex align-items-center" id="conflictAlert" style="display: none;">
                                    <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                                    <div>
                                        <strong>Atenção!</strong> <span id="conflictMessage"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <h6 class="text-uppercase text-muted fw-bold mb-3 border-bottom pb-2">Informações do Serviço</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="small text-muted text-uppercase fw-bold">Título</label>
                                        <p class="fw-bold text-dark">{{ $schedule->service->title }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted text-uppercase fw-bold">Cliente</label>
                                        <p class="fw-bold text-dark">{{ $schedule->service->customer->name }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted text-uppercase fw-bold">Status Atual</label>
                                        <div>
                                            <span class="badge bg-{{ $schedule->service->status->getBadgeClass() }}">
                                                {{ $schedule->service->status->label() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div class="d-flex align-items-center">
                                <x-ui.button type="link" :href="route('provider.schedules.show', $schedule->id)" variant="outline-secondary" icon="arrow-left" label="Voltar" class="me-2" feature="schedules" />
                                <small class="text-muted d-none d-md-block">
                                    Serviço: <strong>{{ $schedule->service->code }}</strong>
                                </small>
                            </div>
                            <x-ui.button type="submit" variant="primary" icon="calendar-check" label="Atualizar Agendamento" id="submitBtn" feature="schedules" />
                        </div>
                    </form>
                </x-ui.card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDateTimeInput = document.getElementById('start_date_time');
            const endDateTimeInput = document.getElementById('end_date_time');
            const conflictAlert = document.getElementById('conflictAlert');
            const conflictMessage = document.getElementById('conflictMessage');
            const submitBtn = document.getElementById('submitBtn');

            // Set minimum date to now
            const now = new Date();
            // Format to YYYY-MM-DDTHH:MM (local time)
            // Note: toISOString() uses UTC. We need local time.
            const offset = now.getTimezoneOffset() * 60000;
            const localISOTime = (new Date(now - offset)).toISOString().slice(0, 16);

            // Allow editing past dates if the original date was in the past?
            // Usually we don't want to restrict editing to future only if fixing records,
            // but the original code had this restriction. I'll keep it consistent with create.
            // startDateTimeInput.min = localISOTime;

            // Check conflicts function
            function checkConflicts() {
                const startDateTime = startDateTimeInput.value;
                const endDateTime = endDateTimeInput.value;

                if (startDateTime && endDateTime) {
                    fetch('{{ route('provider.schedules.check-conflicts') }}?' + new URLSearchParams({
                            service_id: {{ $schedule->service->id }},
                            start_date_time: startDateTime,
                            end_date_time: endDateTime,
                            exclude_id: {{ $schedule->id }}
                        }))
                        .then(response => response.json())
                        .then(data => {
                            if (data.has_conflict) {
                                conflictAlert.style.display = 'flex';
                                conflictMessage.textContent =
                                    'Existe um conflito de horário com outro agendamento para este serviço.';
                                submitBtn.disabled = true;
                            } else {
                                conflictAlert.style.display = 'none';
                                submitBtn.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao verificar conflitos:', error);
                        });
                }
            }

            // Add event listeners
            startDateTimeInput.addEventListener('change', function() {
                // Set minimum end date to start date
                endDateTimeInput.min = this.value;
                checkConflicts();
            });

            endDateTimeInput.addEventListener('change', checkConflicts);
        });
    </script>
@endpush
