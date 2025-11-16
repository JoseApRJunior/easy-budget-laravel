@extends('layouts.public')

@section('title', 'Contato - Easy Budget')

@section('content')
<!-- Page Header -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-3">Entre em Contato</h1>
                <p class="lead mb-0">
                    Estamos aqui para ajudar você a crescer
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="row">
                    <!-- Contact Form -->
                    <div class="col-lg-8 mb-4 mb-lg-0">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h3 class="h4 fw-bold mb-4">Envie sua mensagem</h3>
                                
                                @if(session('success'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        {{ session('success') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @endif
                                
                                <form action="{{ route('home.contact.submit') }}" method="POST">
                                    @csrf
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="name" class="form-label">Nome *</label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">E-mail *</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">Telefone</label>
                                            <input type="tel" class="form-control" id="phone" name="phone">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="subject" class="form-label">Assunto *</label>
                                            <select class="form-select" id="subject" name="subject" required>
                                                <option value="">Selecione...</option>
                                                <option value="duvida">Dúvida sobre o sistema</option>
                                                <option value="preco">Informações sobre preços</option>
                                                <option value="demo">Agendar demonstração</option>
                                                <option value="suporte">Suporte técnico</option>
                                                <option value="outro">Outro assunto</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <label for="message" class="form-label">Mensagem *</label>
                                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="bi bi-send me-2"></i>Enviar Mensagem
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Info -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body p-4">
                                <h4 class="h5 fw-bold mb-3">Informações de Contato</h4>
                                
                                <div class="contact-info mb-3">
                                    <div class="d-flex align-items-start mb-3">
                                        <i class="bi bi-envelope text-primary me-3 mt-1"></i>
                                        <div>
                                            <strong>E-mail</strong><br>
                                            <a href="mailto:contato@easybudget.com" class="text-decoration-none">
                                                contato@easybudget.com
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-start mb-3">
                                        <i class="bi bi-telephone text-primary me-3 mt-1"></i>
                                        <div>
                                            <strong>Telefone</strong><br>
                                            <a href="tel:+5511999999999" class="text-decoration-none">
                                                (11) 99999-9999
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-clock text-primary me-3 mt-1"></i>
                                        <div>
                                            <strong>Horário de Atendimento</strong><br>
                                            Segunda a Sexta: 9h às 18h<br>
                                            Sábado: 9h às 13h
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h4 class="h5 fw-bold mb-3">Redes Sociais</h4>
                                <div class="social-links">
                                    <a href="#" class="btn btn-outline-primary btn-sm me-2 mb-2">
                                        <i class="bi bi-facebook"></i>
                                    </a>
                                    <a href="#" class="btn btn-outline-primary btn-sm me-2 mb-2">
                                        <i class="bi bi-twitter"></i>
                                    </a>
                                    <a href="#" class="btn btn-outline-primary btn-sm me-2 mb-2">
                                        <i class="bi bi-linkedin"></i>
                                    </a>
                                    <a href="#" class="btn btn-outline-primary btn-sm mb-2">
                                        <i class="bi bi-instagram"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center mb-5">
                <h2 class="h3 fw-bold text-primary mb-3">Perguntas Frequentes</h2>
                <p class="text-muted">
                    Confira as respostas para as dúvidas mais comuns
                </p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Como funciona o período de teste gratuito?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Oferecemos um período de teste gratuito de 14 dias em todos os planos. 
                                Durante esse período, você tem acesso completo a todas as funcionalidades 
                                do plano escolhido. Não é necessário cartão de crédito para iniciar o teste.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Posso mudar de plano depois de contratar?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Sim! Você pode mudar de plano a qualquer momento. A mudança é feita 
                                de forma proporcional, e você só paga a diferença. O novo plano entra 
                                em vigor imediatamente.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Há algum custo de implantação?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Não! A implantação é gratuita. Oferecemos suporte completo para 
                                configurar o sistema de acordo com as necessidades do seu negócio. 
                                Também fornecemos treinamento gratuito para você e sua equipe.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Meus dados estão seguros?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Sim! Utilizamos os mais altos padrões de segurança. Todos os dados 
                                são criptografados e armazenados em servidores seguros com backup 
                                automático. Estamos em conformidade com a LGPD (Lei Geral de Proteção de Dados).
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@stop

@section('styles')
<style>
.contact-info .d-flex {
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.contact-info .d-flex:last-child {
    border-bottom: none;
}

.accordion-button:not(.collapsed) {
    background-color: #e7f1ff;
    color: #0c63e4;
}

.social-links a {
    transition: all 0.3s ease;
}

.social-links a:hover {
    transform: translateY(-2px);
}
</style>
@stop