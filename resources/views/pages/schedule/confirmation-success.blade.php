<x-app-layout title="Agendamento Confirmado">
    <x-auth.card
        title="Confirmado!"
        subtitle="Seu agendamento foi realizado com sucesso."
        icon="check-lg"
        maxWidth="600px">

        <div class="text-center mb-4">
            <h5 class="text-success fw-normal">Tudo pronto!</h5>
            <p class="text-muted">Já notificamos o prestador sobre a sua confirmação.</p>
        </div>

        <x-ui.card class="bg-light mb-4">
            <div class="p-2">
                <h6 class="text-uppercase text-muted fw-bold small mb-3 border-bottom pb-2">Detalhes Confirmados</h6>

                <div class="d-flex align-items-start mb-3">
                    <div class="me-3 text-success">
                        <i class="bi bi-tools fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Serviço</small>
                        <span class="fw-bold fs-5">{{ $schedule->service->title }}</span>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="d-flex align-items-start">
                            <div class="me-3 text-success">
                                <i class="bi bi-calendar-event fs-4"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Data</small>
                                <span class="fw-bold">{{ $schedule->start_date_time->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-start">
                            <div class="me-3 text-success">
                                <i class="bi bi-clock fs-4"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Horário</small>
                                <span class="fw-bold">{{ $schedule->start_date_time->format('H:i') }} - {{ $schedule->end_date_time->format('H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($schedule->location)
                <div class="d-flex align-items-start mt-3">
                    <div class="me-3 text-success">
                        <i class="bi bi-geo-alt fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Local</small>
                        <span class="fw-bold">{{ $schedule->location }}</span>
                    </div>
                </div>
                @endif
            </div>
        </x-ui.card>

        <x-ui.alert type="info" class="mb-4 shadow-sm border-0 rounded-3">
            <div class="d-flex align-items-center">
                <div class="fs-1 me-3 text-primary">
                    <i class="bi bi-calendar2-plus"></i>
                </div>
                <div>
                    <strong class="d-block text-primary">Dica Útil</strong>
                    <span class="small text-muted">Anote este compromisso na sua agenda pessoal para não esquecer!</span>
                </div>
            </div>
        </x-ui.alert>

        <div class="d-grid gap-2">
            <x-ui.button href="{{ url('/') }}" variant="outline-primary" label="Voltar para o Início" icon="house" class="py-3 rounded-pill fw-bold" />
        </div>

        <div class="text-center mt-4 pt-3 border-top">
            <small class="text-muted">
                <i class="bi bi-envelope me-1"></i>Enviamos uma cópia para o seu e-mail.
            </small>
        </div>
    </x-auth.card>
</x-app-layout>
