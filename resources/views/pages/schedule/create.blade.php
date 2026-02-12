@extends('layouts.app')

@section('title', 'Criar Agendamento')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Criar Agendamento"
            icon="calendar-plus"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Agendamentos' => route('provider.schedules.index'),
                'Criar' => '#'
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

                    <form method="POST" action="{{ route('provider.schedules.store', $service) }}" id="scheduleForm">
                        @csrf

                        <input type="hidden" name="service_id" value="{{ $service->id }}">
                        <input type="hidden" name="customer_id" value="{{ $service->budget->customer_id }}">

                        <div class="row">
                            <div class="col-md-6">
                                <x-ui.form.input 
                                    type="datetime-local" 
                                    name="start_date_time" 
                                    id="start_date_time" 
                                    label="Data e Hora de Início *" 
                                    value="{{ old('start_date_time') }}"
                                    required
                                />
                            </div>

                            <div class="col-md-6">
                                <x-ui.form.input 
                                    type="datetime-local" 
                                    name="end_date_time" 
                                    id="end_date_time" 
                                    label="Data e Hora de Término *" 
                                    value="{{ old('end_date_time') }}"
                                    required
                                />
                            </div>
                        </div>

                        @php
                            $customerAddress = $service->customer?->address;
                        @endphp
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">CEP</label>
                                <input type="text" id="cep" name="cep" class="form-control" data-cep-lookup data-mask="00000-000" placeholder="00000-000" value="{{ $customerAddress?->cep }}">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-bold small text-muted text-uppercase">Endereço</label>
                                <input type="text" id="address" name="address" class="form-control" placeholder="Rua, Av, etc." value="{{ $customerAddress?->address }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small text-muted text-uppercase">Número</label>
                                <input type="text" id="address_number" name="address_number" class="form-control" placeholder="Nº" value="{{ $customerAddress?->address_number }}">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-muted text-uppercase">Bairro</label>
                                <input type="text" id="neighborhood" name="neighborhood" class="form-control" placeholder="Bairro" value="{{ $customerAddress?->neighborhood }}">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-muted text-uppercase">Cidade</label>
                                <input type="text" id="city" name="city" class="form-control" placeholder="Cidade" value="{{ $customerAddress?->city }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small text-muted text-uppercase">UF</label>
                                <input type="text" id="state" name="state" class="form-control" maxlength="2" placeholder="UF" value="{{ $customerAddress?->state }}">
                            </div>
                        </div>

                        <input type="hidden" name="location" id="location_hidden">

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
                                        <p class="fw-bold text-dark">{{ $service->title }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted text-uppercase fw-bold">Cliente</label>
                                        <p class="fw-bold text-dark">{{ $service->customer->name }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted text-uppercase fw-bold">Status Atual</label>
                                        <div>
                                            <span class="badge bg-{{ $service->status->getBadgeClass() }}">
                                                {{ $service->status->label() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div class="d-flex align-items-center">
                                <x-ui.button type="link" :href="route('provider.services.show', $service->code)" variant="outline-secondary" icon="arrow-left" label="Voltar ao Serviço" class="me-2" feature="schedules" />
                                <small class="text-muted d-none d-md-block">
                                    Serviço: <strong>{{ $service->code }}</strong>
                                </small>
                            </div>
                            <x-ui.button type="submit" variant="primary" icon="calendar-plus" label="Criar Agendamento" id="submitBtn" feature="schedules" />
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
            
            startDateTimeInput.min = localISOTime;

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

                // Auto-set end time to 1 hour after start time if not set
                if (this.value && !endDateTimeInput.value) {
                    const startDate = new Date(this.value);
                    const endDate = new Date(startDate.getTime() + 60 * 60 * 1000); // Add 1 hour
                    // Format back to YYYY-MM-DDTHH:MM
                    const offset = endDate.getTimezoneOffset() * 60000;
                    const localEndISOTime = (new Date(endDate - offset)).toISOString().slice(0, 16);
                    endDateTimeInput.value = localEndISOTime;
                }

                checkConflicts();
            });

            endDateTimeInput.addEventListener('change', checkConflicts);

            // Máscaras e Concatenação de Endereço
            if (typeof VanillaMask !== 'undefined') {
                const cepInput = document.getElementById('cep');
                if (cepInput) {
                    new VanillaMask('cep', 'cep');
                }
            }

            const scheduleForm = document.getElementById('scheduleForm');
            if (scheduleForm) {
                scheduleForm.addEventListener('submit', function(e) {
                    const cep = document.getElementById('cep')?.value || '';
                    const address = document.getElementById('address')?.value || '';
                    const number = document.getElementById('address_number')?.value || '';
                    const neighborhood = document.getElementById('neighborhood')?.value || '';
                    const city = document.getElementById('city')?.value || '';
                    const state = document.getElementById('state')?.value || '';

                    let fullLocation = '';
                    if (address) fullLocation += address;
                    if (number) fullLocation += `, ${number}`;
                    if (neighborhood) fullLocation += ` - ${neighborhood}`;
                    if (city) fullLocation += ` - ${city}`;
                    if (state) fullLocation += `/${state}`;
                    if (cep) fullLocation += ` (CEP: ${cep})`;

                    const hiddenLocation = document.getElementById('location_hidden');
                    if (hiddenLocation) {
                        hiddenLocation.value = fullLocation.trim();
                    }
                });
            }
        });
    </script>
@endpush
