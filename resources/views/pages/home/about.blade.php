@extends('layouts.public')

@section('title', 'Sobre - Easy Budget')

@section('content')
<!-- Page Header -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-3">Sobre o Easy Budget</h1>
                <p class="lead mb-0">
                    Conheça nossa história e missão
                </p>
            </div>
        </div>
    </div>
</section>

<!-- About Content -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center mb-5">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2 class="h3 fw-bold text-primary mb-3">Nossa Missão</h2>
                <p class="lead mb-4">
                    Democratizar o acesso a ferramentas profissionais de gestão comercial 
                    para empresas de todos os tamanhos.
                </p>
                <p class="text-muted mb-4">
                    Acreditamos que toda empresa, independentemente do seu porte, deve ter 
                    acesso a ferramentas que permitam gerenciar seus orçamentos, clientes 
                    e estoque de forma profissional e eficiente.
                </p>
                <p class="text-muted mb-0">
                    O Easy Budget nasceu da necessidade de pequenas e médias empresas 
                    terem um sistema completo sem a complexidade e custo das soluções 
                    corporativas tradicionais.
                </p>
            </div>
            <div class="col-lg-6">
                <img src="https://trae-api-us.mchost.guru/api/ide/v1/text_to_image?prompt=business%20team%20collaboration%2C%20modern%20office%2C%20professional%20meeting%2C%20diverse%20team%2C%20bright%20colors&image_size=landscape_4_3" 
                     alt="Nossa Equipe" class="img-fluid rounded shadow">
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="h3 fw-bold text-primary mb-4">Nossos Valores</h2>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4 text-center">
                <div class="value-card p-4">
                    <div class="value-icon mb-3">
                        <i class="bi bi-people-fill text-primary fs-1"></i>
                    </div>
                    <h4 class="h5 fw-bold mb-3">Foco no Cliente</h4>
                    <p class="text-muted">
                        Nossos clientes estão no centro de todas as nossas decisões. 
                        Escutamos, aprendemos e evoluímos constantemente.
                    </p>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="value-card p-4">
                    <div class="value-icon mb-3">
                        <i class="bi bi-lightning-fill text-primary fs-1"></i>
                    </div>
                    <h4 class="h5 fw-bold mb-3">Simplicidade</h4>
                    <p class="text-muted">
                        Acreditamos que tecnologia deve simplificar, não complicar. 
                        Buscamos sempre a solução mais simples e eficaz.
                    </p>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="value-card p-4">
                    <div class="value-icon mb-3">
                        <i class="bi bi-graph-up-arrow text-primary fs-1"></i>
                    </div>
                    <h4 class="h5 fw-bold mb-3">Crescimento</h4>
                    <p class="text-muted">
                        Crescemos junto com nossos clientes. 
                        Seu sucesso é o nosso sucesso.
                    </p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="bg-light p-4 rounded">
                    <h3 class="h4 fw-bold text-primary mb-3">Por que escolher o Easy Budget?</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Interface intuitiva e fácil de usar
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Implementação rápida e sem complicação
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Suporte técnico dedicado
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Preços acessíveis e transparentes
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Atualizações constantes e gratuitas
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Segurança e confiabilidade
                                </li>
                            </ul>
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
.value-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    height: 100%;
}

.value-card:hover {
    transform: translateY(-5px);
}

.value-icon {
    width: 80px;
    height: 80px;
    background: rgba(13, 110, 253, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}
</style>
@stop
