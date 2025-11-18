@extends('layouts.public')

@section('title', 'Link Inválido ou Expirado')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-danger text-white text-center">
                    <div class="mb-3">
                        <i class="bi bi-shield-x fs-1"></i>
                    </div>
                    <h3 class="mb-0">Acesso Negado</h3>
                </div>
                
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-exclamation-triangle text-warning fs-1"></i>
                    </div>
                    
                    <h4 class="text-danger mb-3">Link Inválido ou Expirado</h4>
                    
                    <p class="text-muted mb-4">
                        O link que você está tentando acessar não é mais válido. Isso pode ter ocorrido por:
                    </p>
                    
                    <div class="row text-start mb-4">
                        <div class="col-12">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-clock text-warning me-2"></i>
                                    <strong>Expiração:</strong> O prazo de validade do link expirou
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-x-circle text-danger me-2"></i>
                                    <strong>Revogação:</strong> O compartilhamento foi revogado pelo proprietário
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-trash text-secondary me-2"></i>
                                    <strong>Exclusão:</strong> O orçamento foi excluído do sistema
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-shield-slash text-info me-2"></i>
                                    <strong>Segurança:</strong> O link foi desativado por questões de segurança
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mb-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle me-2 fs-4"></i>
                            <div>
                                <strong>Como resolver?</strong>
                                <p class="mb-0 mt-1">
                                    Entre em contato com quem compartilhou este orçamento para obter um novo link válido.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" onclick="goBack()">
                            <i class="bi bi-arrow-left me-2"></i>Voltar à Página Anterior
                        </button>
                        <button class="btn btn-outline-secondary" onclick="goHome()">
                            <i class="bi bi-house me-2"></i>Página Inicial
                        </button>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-muted">
                        <small>
                            <i class="bi bi-shield-check me-1"></i>
                            Este sistema utiliza segurança avançada para proteger seus dados
                        </small>
                    </div>
                </div>
                
                <div class="card-footer bg-light text-center">
                    <div class="row align-items-center">
                        <div class="col-md-6 text-start">
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>
                                Tentativa de acesso: <span id="accessTime"></span>
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                <i class="bi bi-shield me-1"></i>
                                Sistema de Orçamentos
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Informações de Contato -->
    <div class="row justify-content-center mt-4">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 bg-transparent">
                <div class="card-body text-center">
                    <h5 class="text-muted mb-3">Precisa de ajuda?</h5>
                    <p class="text-muted mb-4">
                        Se você acredita que este link deveria estar funcionando, entre em contato com nossa equipe de suporte.
                    </p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <i class="bi bi-envelope text-primary me-2 fs-4"></i>
                                <div class="text-start">
                                    <small class="text-muted d-block">Email</small>
                                    <strong>suporte@sistema.com</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <i class="bi bi-telephone text-success me-2 fs-4"></i>
                                <div class="text-start">
                                    <small class="text-muted d-block">Telefone</small>
                                    <strong>(00) 0000-0000</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i>
                            Horário de atendimento: Segunda a Sexta, 9h às 18h
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Atualizar hora de acesso
document.getElementById('accessTime').textContent = new Date().toLocaleString('pt-BR');

function goBack() {
    if (document.referrer) {
        window.history.back();
    } else {
        window.location.href = '/';
    }
}

function goHome() {
    window.location.href = '/';
}

// Adicionar animação ao carregar
document.addEventListener('DOMContentLoaded', function() {
    const card = document.querySelector('.card');
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.5s ease';
    
    setTimeout(() => {
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
    }, 100);
});

// Adicionar efeito de hover nos botões
document.querySelectorAll('.btn').forEach(btn => {
    btn.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
        this.style.transition = 'transform 0.2s ease';
    });
    
    btn.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});
</script>
@endsection

@section('styles')
<style>
body {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
}

.card {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
    padding: 2rem 1.5rem;
}

.btn {
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
}

.alert {
    border-radius: 10px;
    border: none;
}

.list-unstyled li {
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.list-unstyled li:last-child {
    border-bottom: none;
}

.text-primary {
    color: #667eea !important;
}

.text-success {
    color: #28a745 !important;
}

.text-warning {
    color: #ffc107 !important;
}

.text-danger {
    color: #dc3545 !important;
}

.text-info {
    color: #17a2b8 !important;
}

.text-secondary {
    color: #6c757d !important;
}

@media (max-width: 768px) {
    body {
        padding: 1rem;
    }
    
    .card-header {
        padding: 1.5rem 1rem;
    }
    
    .card-body {
        padding: 1.5rem 1rem;
    }
    
    .btn {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .fs-1 {
        font-size: 2.5rem !important;
    }
    
    h3 {
        font-size: 1.5rem;
    }
    
    h4 {
        font-size: 1.25rem;
    }
}

@media (max-width: 576px) {
    .card-header {
        padding: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }
    
    .fs-1 {
        font-size: 2rem !important;
    }
    
    h3 {
        font-size: 1.25rem;
    }
    
    h4 {
        font-size: 1.1rem;
    }
}

/* Animação de pulsação para ícones */
@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

.bi-shield-x {
    animation: pulse 2s infinite;
}

/* Efeito de brilho no hover */
.card-header:hover {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}
</style>
@endsection