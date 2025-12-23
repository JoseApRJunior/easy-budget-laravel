@extends('layouts.public')

@section('title', 'Preços - Easy Budget')

@section('content')
<!-- Page Header -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-3">Planos e Preços</h1>
                <p class="lead mb-0">
                    Escolha o plano ideal para o seu negócio
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            @foreach($plans as $plan)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="pricing-card h-100 p-4 bg-white rounded shadow-sm position-relative @if($plan['popular']) border-primary border-2 @endif">
                    @if($plan['popular'])
                    <div class="popular-badge position-absolute top-0 start-50 translate-middle">
                        <span class="badge bg-primary px-3 py-2">Mais Popular</span>
                    </div>
                    @endif
                    
                    <div class="text-center mb-4 @if($plan['popular']) mt-4 @endif">
                        <h3 class="h4 fw-bold mb-2">{{ $plan['name'] }}</h3>
                        <div class="price mb-3">
                            <span class="display-6 fw-bold text-primary">{{ $plan['price'] }}</span>
                            <small class="text-muted">/ {{ $plan['period'] }}</small>
                        </div>
                    </div>
                    
                    <ul class="list-unstyled mb-4">
                        @foreach($plan['features'] as $feature)
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                    
                    <div class="text-center">
                        <a href="{{ route('register') }}" class="btn @if($plan['popular']) btn-primary @else btn-outline-primary @endif w-100">
                            Escolher Plano
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        <!-- Additional Info -->
        <div class="row mt-5">
            <div class="col-lg-8 mx-auto text-center">
                <div class="bg-light p-4 rounded">
                    <h4 class="h5 fw-bold mb-3">Dúvidas sobre os planos?</h4>
                    <p class="text-muted mb-3">
                        Todos os planos incluem suporte técnico, atualizações gratuitas e segurança de dados.
                        Você pode mudar de plano a qualquer momento.
                    </p>
                    <a href="{{ route('home.contact') }}" class="btn btn-outline-primary">
                        Entrar em Contato
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@stop

@section('styles')
<style>
.pricing-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.pricing-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15) !important;
}

.popular-badge {
    z-index: 1;
}

.price {
    position: relative;
}

.list-unstyled li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.list-unstyled li:last-child {
    border-bottom: none;
}
</style>
@stop