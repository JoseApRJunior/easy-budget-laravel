@extends('layouts.app')

@section('title', 'Erro na Confirmação de Agendamento')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-danger text-white text-center py-4">
                    <div class="mb-3">
                        <i class="bi bi-x-circle fs-1"></i>
                    </div>
                    <h3 class="mb-0">Ops! Algo deu errado</h3>
                </div>
                
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-exclamation-triangle text-warning fs-1"></i>
                    </div>
                    
                    <h4 class="text-danger mb-3">Erro na Confirmação</h4>
                    
                    <p class="text-muted mb-4">
                        {{ $error ?? 'O link de confirmação é inválido ou já expirou.' }}
                    </p>
                    
                    @if(isset($status) && $status->value === 'forbidden')
                        <div class="alert alert-warning mb-4 text-start">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-shield-lock me-3 fs-3 text-warning"></i>
                                <div>
                                    <strong>Ação Não Permitida</strong>
                                    <p class="mb-0 mt-1 small">
                                        Este serviço já foi processado (Concluído ou Cancelado) e não permite mais alterações via link público.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info mb-4 text-start">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle me-3 fs-3"></i>
                                <div>
                                    <strong>Por que isso aconteceu?</strong>
                                    <ul class="mb-0 mt-1 small">
                                        <li>O link pode ter expirado (validade de 7 dias)</li>
                                        <li>O agendamento pode ter sido cancelado ou alterado</li>
                                        <li>O token de confirmação não é mais válido</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="d-grid gap-2">
                        <a href="{{ url('/') }}" class="btn btn-primary">
                            <i class="bi bi-house me-2"></i>Voltar para a Página Inicial
                        </a>
                    </div>
                </div>
                
                <div class="card-footer bg-light text-center">
                    <small class="text-muted">
                        Se o problema persistir, entre em contato com o prestador de serviço.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
