@extends( 'layouts.app' )

@section( 'content' )
    <div class=" container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="jumbotron bg-light p-5 rounded shadow-sm">
                    <div class="text-center mb-4">
                        <i class="bi bi-calculator display-1 text-primary mb-3"></i>
                        <h1 class="display-4 text-primary">Bem-vindo ao Easy Budget!</h1>
                        <p class="lead">Sistema completo para gerenciamento de orçamentos e planos.</p>
                    </div>

                    <hr class="my-4">

                    <div class="row text-center mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="text-primary mb-2">
                                <i class="bi bi-diagram-3 fs-2"></i>
                            </div>
                            <h5>Planos Flexíveis</h5>
                            <p class="text-muted">Crie e gerencie planos personalizados para seus clientes.</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-success mb-2">
                                <i class="bi bi-people fs-2"></i>
                            </div>
                            <h5>Gestão de Usuários</h5>
                            <p class="text-muted">Controle completo de usuários e permissões do sistema.</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-info mb-2">
                                <i class="bi bi-receipt fs-2"></i>
                            </div>
                            <h5>Orçamentos Detalhados</h5>
                            <p class="text-muted">Elabore orçamentos profissionais com facilidade.</p>
                        </div>
                    </div>

                    <div class="text-center">
                        <p class="mb-4">Faça login para acessar o sistema ou registre-se para começar.</p>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a class="btn btn-primary btn-lg px-4 me-md-2" href="{{ route( 'login' ) }}" role="button">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Fazer Login
                            </a>
                            <a class="btn btn-outline-primary btn-lg px-4" href="{{ route( 'register' ) }}" role="button">
                                <i class="bi bi-person-plus me-2"></i>Registrar
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Seção de recursos -->
                <div class="row mt-5">
                    <div class="col-md-12">
                        <h3 class="text-center mb-4">Recursos do Sistema</h3>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="text-primary mb-3">
                                    <i class="bi bi-graph-up fs-1"></i>
                                </div>
                                <h5 class="card-title">Relatórios e Análises</h5>
                                <p class="card-text">Acompanhe o desempenho dos seus orçamentos e tome decisões baseadas em
                                    dados.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="text-success mb-3">
                                    <i class="bi bi-shield-check fs-1"></i>
                                </div>
                                <h5 class="card-title">Segurança Total</h5>
                                <p class="card-text">Seus dados estão protegidos com os mais altos padrões de segurança.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'styles' )
    <style>
        .jumbotron {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
        }

        .card:hover {
            transform: translateY(-2px);
            transition: transform 0.3s ease;
        }

        .btn {
            border-radius: 25px;
        }

        .display-1 {
            font-size: 4rem;
        }

        @media (max-width: 768px) {
            .display-1 {
                font-size: 3rem;
            }

            .jumbotron {
                padding: 2rem 1rem;
            }
        }
    </style>
@endpush
