@extends('layouts.app')

@section('title', 'Confirmar Agendamento')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <div class="mb-3">
                        <i class="bi bi-calendar-check fs-1"></i>
                    </div>
                    <h3 class="mb-0">Confirmar Agendamento</h3>
                </div>
                
                <div class="card-body py-5">
                    <div class="text-center mb-4">
                        <h4 class="mb-3">Olá! Por favor, confirme o seu agendamento.</h4>
                        <p class="text-muted">Verifique os detalhes abaixo e clique no botão para confirmar.</p>
                    </div>

                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h5 class="card-title border-bottom pb-2 mb-3">Detalhes do Agendamento</h5>
                            
                            <div class="row mb-2">
                                <div class="col-4 text-muted">Serviço:</div>
                                <div class="col-8 fw-bold">{{ $schedule->service->title ?? 'Serviço' }}</div>
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

                    <form action="{{ route('services.public.schedules.confirm.action', $token) }}" method="POST">
                        @csrf
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-check2-circle me-2"></i>Confirmar Agendamento
                            </button>
                            <a href="{{ url('/') }}" class="btn btn-link text-muted">
                                Cancelar e voltar
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="card-footer bg-light text-center">
                    <small class="text-muted">
                        Ao confirmar, o prestador será notificado e o horário será reservado para você.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
