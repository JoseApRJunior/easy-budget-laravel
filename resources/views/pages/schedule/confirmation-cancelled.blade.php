<x-app-layout title="Agendamento Cancelado">
    <x-auth.card
        title="Cancelado"
        subtitle="O agendamento foi cancelado com sucesso."
        icon="calendar-x"
        maxWidth="600px">

        <div class="text-center mb-4">
            <h5 class="text-danger fw-normal">Agendamento Cancelado</h5>
            <p class="text-muted">O prestador de serviço será notificado sobre este cancelamento.</p>
        </div>

        <x-ui.card class="bg-light mb-4 shadow-sm border-0 rounded-3">
            <div class="p-2">
                <h6 class="text-uppercase text-muted fw-bold small mb-3 border-bottom pb-2">Detalhes do Agendamento</h6>

                <div class="d-flex align-items-start mb-3 opacity-75">
                    <div class="me-3 text-secondary">
                        <i class="bi bi-tools fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Serviço</small>
                        <span class="fw-bold fs-5 text-decoration-line-through">{{ $schedule->service->title ?? 'Serviço Personalizado' }}</span>
                    </div>
                </div>

                <div class="row g-3 opacity-75">
                    <div class="col-6">
                        <div class="d-flex align-items-start">
                            <div class="me-3 text-secondary">
                                <i class="bi bi-calendar-event fs-4"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Data</small>
                                <span class="fw-bold text-decoration-line-through">{{ $schedule->start_date_time->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-start">
                            <div class="me-3 text-secondary">
                                <i class="bi bi-clock fs-4"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Horário</small>
                                <span class="fw-bold text-decoration-line-through">{{ $schedule->start_date_time->format('H:i') }} - {{ $schedule->end_date_time->format('H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.alert type="warning" class="mb-4 shadow-sm border-0 rounded-3">
            <div class="d-flex align-items-center">
                <div class="fs-1 me-3 text-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div>
                    <strong class="d-block text-warning">Importante</strong>
                    <span class="small text-muted">Caso tenha sido um engano, entre em contato diretamente com o prestador para realizar um novo agendamento.</span>
                </div>
            </div>
        </x-ui.alert>

        <div class="d-grid gap-2">
            <x-ui.button href="{{ url('/') }}" variant="outline-secondary" label="Voltar para o Início" icon="house" class="py-3 rounded-pill fw-bold" />
        </div>

        <div class="text-center mt-4 pt-3 border-top">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>Obrigado por utilizar nosso sistema.
            </small>
        </div>
    </x-auth.card>
</x-app-layout>
