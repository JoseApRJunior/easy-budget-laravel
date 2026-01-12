@extends('layouts.app')

@section('title', 'Criar Agendamento')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Criar Agendamento"
            icon="calendar-plus"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Agendamentos' => route('provider.schedules.index'),
                'Criar' => '#'
            ]">
            <x-ui.button type="link" :href="route('provider.schedules.index')" variant="secondary" icon="arrow-left" label="Voltar" />
        </x-layout.page-header>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informações do Agendamento</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('provider.schedules.store', $service) }}" id="scheduleForm">
                            @csrf

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="start_date_time">Data e Hora de Início *</label>
                                        <input type="datetime-local" name="start_date_time" id="start_date_time"
                                            class="form-control @error('start_date_time') is-invalid @enderror"
                                            value="{{ old('start_date_time') }}" required>
                                        @error('start_date_time')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="end_date_time">Data e Hora de Término *</label>
                                        <input type="datetime-local" name="end_date_time" id="end_date_time"
                                            class="form-control @error('end_date_time') is-invalid @enderror"
                                            value="{{ old('end_date_time') }}" required>
                                        @error('end_date_time')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="location">Local do Agendamento</label>
                                        <input type="text" name="location" id="location"
                                            class="form-control @error('location') is-invalid @enderror"
                                            value="{{ old('location') }}"
                                            placeholder="Ex: Escritório do cliente, Sala de reunião, etc.">
                                        @error('location')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-info" id="conflictAlert" style="display: none;">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span id="conflictMessage"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <h5>Informações do Serviço</h5>
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Título:</th>
                                            <td>{{ $service->title }}</td>
                                        </tr>
                                        <tr>
                                            <th>Cliente:</th>
                                            <td>{{ $service->customer->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Status Atual:</th>
                                            <td>
                                                <span class="badge badge-{{ $service->status->getBadgeClass() }}">
                                                    {{ $service->status->label() }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <a href="{{ route('provider.services.show', $service->code) }}"
                                    class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Voltar
                                </a>
                                <small class="text-muted d-none d-md-block">
                                    Serviço: {{ $service->code }}
                                </small>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="bi bi-calendar-plus me-2"></i>Criar Agendamento
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
            const minDateTime = now.toISOString().slice(0, 16);
            startDateTimeInput.min = minDateTime;

            // Check conflicts function
            function checkConflicts() {
                const startDateTime = startDateTimeInput.value;
                const endDateTime = endDateTimeInput.value;

                if (startDateTime && endDateTime) {
                    fetch('{{ route('provider.schedules.check-conflicts') }}?' + new URLSearchParams({
                            service_id: {{ $service->id }},
                            start_date_time: startDateTime,
                            end_date_time: endDateTime
                        }))
                        .then(response => response.json())
                        .then(data => {
                            if (data.has_conflict) {
                                conflictAlert.style.display = 'block';
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

                // Auto-set end time to 1 hour after start time if not set
                if (this.value && !endDateTimeInput.value) {
                    const startDate = new Date(this.value);
                    const endDate = new Date(startDate.getTime() + 60 * 60 * 1000); // Add 1 hour
                    endDateTimeInput.value = endDate.toISOString().slice(0, 16);
                }

                checkConflicts();
            });

            endDateTimeInput.addEventListener('change', checkConflicts);
        });
    </script>
@endpush
