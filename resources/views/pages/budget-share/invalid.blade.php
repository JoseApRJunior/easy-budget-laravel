@extends('layouts.app')

@section('title', 'Link Inválido ou Expirado')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center align-items-center min-vh-75">
        <div class="col-md-8 col-lg-6">
            <x-ui.card class="border-0 shadow-lg overflow-hidden">
                <x-slot:header class="bg-white border-bottom-0 pt-4">
                    <div class="text-center">
                        <div class="mb-3">
                            <div class="avatar-circle bg-danger-soft text-danger mx-auto" style="width: 80px; height: 80px; font-size: 2.5rem; display: flex; align-items: center; justify-content: center; background-color: rgba(220, 53, 69, 0.1); border-radius: 50%;">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold text-dark">Acesso Indisponível</h3>
                    </div>
                </x-slot:header>

                <div class="text-center px-4 pb-4">
                    <h5 class="text-secondary mb-4">O link acessado é inválido ou já expirou.</h5>

                    <div class="text-start bg-light rounded-3 p-4 mb-4">
                        <p class="small fw-bold text-uppercase text-muted mb-3">Possíveis Motivos:</p>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex align-items-center mb-2">
                                <i class="bi bi-clock-history text-warning me-3 fs-5"></i>
                                <span class="text-dark">O prazo de validade do link foi atingido.</span>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="bi bi-person-x text-danger me-3 fs-5"></i>
                                <span class="text-dark">O acesso foi revogado pelo profissional.</span>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="bi bi-trash3 text-muted me-3 fs-5"></i>
                                <span class="text-dark">O orçamento não consta mais no sistema.</span>
                            </li>
                        </ul>
                    </div>

                    <x-ui.alert type="info" :noContainer="true" class="mb-4 text-start">
                        <strong>Como resolver?</strong><br>
                        Solicite ao profissional que gere um novo link de compartilhamento para você.
                    </x-ui.alert>

                    <div class="d-grid gap-2">
                        <x-ui.button variant="primary" icon="arrow-left" label="Voltar à Página Anterior" onclick="goBack()" />
                        <x-ui.button variant="outline-secondary" icon="house" label="Ir para o Início" onclick="goHome()" />
                    </div>
                </div>

                <x-slot:footer class="bg-light border-top-0 py-3">
                    <div class="d-flex justify-content-between align-items-center px-2">
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i>
                            <span id="accessTime"></span>
                        </small>
                        <small class="text-muted">
                            <i class="bi bi-shield-check text-success me-1"></i>
                            Conexão Segura
                        </small>
                    </div>
                </x-slot:footer>
            </x-ui.card>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('accessTime').textContent = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

    function goBack() {
        if (document.referrer && document.referrer !== window.location.href) {
            window.history.back();
        } else {
            window.location.href = '/';
        }
    }

    function goHome() {
        window.location.href = '/';
    }
</script>
@endsection

@section('styles')
<style>
    body {
        background-color: #f8fafc;
    }
    .min-vh-75 {
        min-height: 75vh;
    }
    .bg-danger-soft {
        background-color: rgba(220, 53, 69, 0.08);
    }
    .card {
        transition: transform 0.2s ease;
    }
    .card:hover {
        transform: translateY(-2px);
    }
</style>
@endsection
