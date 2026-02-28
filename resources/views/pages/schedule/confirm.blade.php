<x-app-layout title="Confirmar Agendamento">
    <x-auth.card
        title="Confirmação"
        subtitle="Confirme seu agendamento abaixo"
        icon="calendar-check"
        maxWidth="600px">

        <div class="text-center mb-4">
            <h5 class="text-secondary fw-normal">Olá! Estamos quase lá.</h5>
            <p class="text-muted">Revise os detalhes do serviço agendado para você.</p>
        </div>

        <x-ui.card class="bg-light mb-4">
            <div class="p-2">
                <h6 class="text-uppercase text-muted fw-bold small mb-3 border-bottom pb-2">Resumo do Agendamento</h6>

                <div class="d-flex align-items-start mb-3">
                    <div class="me-3 text-primary">
                        <i class="bi bi-tools fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Serviço</small>
                        <span class="fw-bold fs-5">{{ $schedule->service->title ?? 'Serviço Personalizado' }}</span>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="d-flex align-items-start">
                            <div class="me-3 text-primary">
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
                            <div class="me-3 text-primary">
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
                    <div class="me-3 text-primary">
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

        <div class="d-grid gap-3">
            <form id="confirm-form" action="{{ route('services.public.schedules.confirm.action', $token) }}" method="POST">
                @csrf
                <x-ui.button type="button" variant="primary" size="lg" icon="check-lg" label="Confirmar Agendamento" class="rounded-pill py-3 w-100" onclick="confirmAction()" />
            </form>

            <form id="cancel-form" action="{{ route('services.public.schedules.confirm.cancel.action', $token) }}" method="POST">
                @csrf
                <x-ui.button type="button" variant="outline-danger" label="Cancelar Agendamento e Fechar" icon="x-lg" class="rounded-pill py-3 w-100" onclick="cancelAction()" />
            </form>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            function confirmAction() {
                Swal.fire({
                    title: 'Confirmar Agendamento?',
                    text: "Deseja realmente confirmar este agendamento?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sim, Confirmar',
                    cancelButtonText: 'Voltar',
                    reverseButtons: true,
                    width: '400px',
                    customClass: {
                        confirmButton: 'rounded-pill px-4',
                        cancelButton: 'rounded-pill px-4'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('confirm-form').submit();
                    }
                });
            }

            function cancelAction() {
                Swal.fire({
                    title: 'Cancelar Agendamento?',
                    text: "Tem certeza que deseja cancelar este agendamento?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sim, Cancelar',
                    cancelButtonText: 'Voltar',
                    reverseButtons: true,
                    width: '400px',
                    customClass: {
                        confirmButton: 'rounded-pill px-4',
                        cancelButton: 'rounded-pill px-4'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('cancel-form').submit();
                    }
                });
            }
        </script>

        <div class="text-center mt-4 pt-3 border-top">
            <small class="text-muted">
                <i class="bi bi-lock-fill me-1"></i>Ambiente Seguro. Seus dados estão protegidos.
            </small>
        </div>
    </x-auth.card>
</x-app-layout>
