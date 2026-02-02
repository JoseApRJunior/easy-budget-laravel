@extends('layouts.app')

@section('title', 'Agendamento Confirmado')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-success text-white text-center py-4">
                    <div class="mb-3">
                        <i class="bi bi-check-circle-fill fs-1"></i>
                    </div>
                    <h3 class="mb-0">Agendamento Confirmado!</h3>
                </div>
                
                <div class="card-body py-5">
                    <div class="text-center mb-4">
                        <h4 class="text-success mb-3">Tudo certo para o seu serviço!</h4>
                        <p class="text-muted">Seu agendamento foi confirmado com sucesso em nosso sistema.</p>
                    </div>

                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h5 class="card-title border-bottom pb-2 mb-3">Detalhes do Agendamento</h5>
                            
                            <div class="row mb-2">
                                <div class="col-4 text-muted">Serviço:</div>
                                <div class="col-8 fw-bold">{{ $schedule->service->title }}</div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-4 text-muted">Data:</div>
                                <div class="col-8 fw-bold">{{ $schedule->start_date_time->format('d/m/Y') }}</div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-4 text-muted">Horário:</div>
                                <div class="col-8 fw-bold">
                                    {{ $schedule->start_date_time->format('H:i') }} às {{ $schedule->end_date_time->format('H:i') }}
                                </div>
                            </div>
                            
                            @if($schedule->location)
                            <div class="row mb-2">
                                <div class="col-4 text-muted">Local:</div>
                                <div class="col-8 fw-bold">{{ $schedule->location }}</div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="alert alert-primary mb-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-event me-3 fs-3"></i>
                            <div>
                                <strong>Adicionar ao Calendário</strong>
                                <p class="mb-0 small">
                                    Não se esqueça de reservar este horário em sua agenda pessoal!
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="{{ url('/') }}" class="btn btn-outline-primary">
                            <i class="bi bi-house me-2"></i>Voltar para a Página Inicial
                        </a>
                    </div>
                </div>
                
                <div class="card-footer bg-light text-center">
                    <small class="text-muted">
                        Um e-mail de confirmação foi enviado com estes detalhes.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
