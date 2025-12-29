@extends('layouts.app')

@section('content')
    <div class="container-fluid py-1">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-search text-info" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="card-title text-muted mb-3">404 | Página não encontrada</h2>
                        <p class="card-text text-muted mb-4">
                            A página que você está procurando não existe ou foi movida.
                        </p>

                        <div class="d-flex justify-content-center gap-3">
                            <a href="{{ route('home') }}" class="btn btn-primary">
                                <i class="bi bi-house me-2"></i>Voltar ao Início
                            </a>
                            <a href="{{ route('support') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-question-circle me-2"></i>Suporte
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
