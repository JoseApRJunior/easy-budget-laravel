@extends('layouts.public')

@section('title', 'Recursos - Easy Budget')

@section('content')
<!-- Page Header -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-3">Conheça todos os recursos</h1>
                <p class="lead mb-0">
                    Descubra como o Easy Budget pode revolucionar a gestão do seu negócio
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Features Detail Section -->
<section class="py-5">
    <div class="container">
        @foreach($features as $category)
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="h3 fw-bold text-primary mb-4">{{ $category['category'] }}</h2>
                <div class="row g-3">
                    @foreach($category['items'] as $item)
                    <div class="col-md-6">
                        <div class="feature-item d-flex align-items-start">
                            <div class="feature-icon me-3">
                                <i class="bi bi-check-circle-fill text-success"></i>
                            </div>
                            <div>
                                <p class="mb-0">{{ $item }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @if(!$loop->last)
        <hr class="my-5">
        @endif
        @endforeach
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-light">
    <div class="container text-center">
        <h2 class="h3 fw-bold mb-3">Pronto para começar?</h2>
        <p class="lead text-muted mb-4">
            Experimente todas essas funcionalidades e muito mais
        </p>
        <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-5">
            Criar Conta Gratuita
        </a>
    </div>
</section>
@stop

@section('styles')
<style>
.feature-item {
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border-left: 4px solid #0d6efd;
    transition: transform 0.2s ease;
}

.feature-item:hover {
    transform: translateX(5px);
}

.feature-icon {
    font-size: 1.2rem;
    margin-top: 2px;
}
</style>
@stop