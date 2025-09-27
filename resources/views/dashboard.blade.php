@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid">
        <!-- Header do Dashboard -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-1">
                            <i class="bi bi-speedometer2 me-2 text-primary"></i>
                            Dashboard
                        </h1>
                        <p class="text-muted mb-0">Bem-vindo de volta, {{ Auth::user()->name ?? 'Usuário' }}!</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route( 'plans.create' ) }}" class="btn btn-success">
                            <i class="bi bi-plus-circle me-1"></i>Novo Plano
                        </a>
                        <a href="{{ route( 'budgets.create' ) }}" class="btn btn-info">
                            <i class="bi bi-plus-circle me-1"></i>Novo Orçamento
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards de Estatísticas Rápidas -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3"
                            style="width: 60px; height: 60px;">
                            <i class="bi bi-diagram-3 fs-4"></i>
                        </div>
                        <h3 class="text-primary mb-1">{{ $stats[ 'plans_count' ] ?? '0' }}</h3>
                        <p class="card-text text-muted mb-0">Planos Ativos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-3"
                            style="width: 60px; height: 60px;">
                            <i class="bi bi-people fs-4"></i>
                        </div>
                        <h3 class="text-success mb-1">{{ $stats[ 'users_count' ] ?? '0' }}</h3>
                        <p class="card-text text-muted mb-0">Usuários</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center mb-3"
                            style="width: 60px; height: 60px;">
                            <i class="bi bi-receipt fs-4"></i>
                        </div>
                        <h3 class="text-info mb-1">{{ $stats[ 'budgets_count' ] ?? '0' }}</h3>
                        <p class="card-text text-muted mb-0">Orçamentos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="rounded-circle bg-warning text-white d-inline-flex align-items-center justify-content-center mb-3"
                            style="width: 60px; height: 60px;">
                            <i class="bi bi-graph-up fs-4"></i>
                        </div>
                        <h3 class="text-warning mb-1">{{ $stats[ 'revenue' ] ?? 'R$ 0,00' }}</h3>
                        <p class="card-text text-muted mb-0">Receita do Mês</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards de Navegação Principal -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-3">
                    <i class="bi bi-compass me-2 text-primary"></i>
                    Navegação Rápida
                </h3>
            </div>
        </div>

        <div class="row">
            <!-- Planos -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-4"
                            style="width: 80px; height: 80px;">
                            <i class="bi bi-diagram-3 fs-2"></i>
                        </div>
                        <h5 class="card-title text-primary mb-3">Planos</h5>
                        <p class="card-text text-muted mb-4">Gerencie os planos disponíveis no sistema. Crie planos
                            personalizados para diferentes tipos de clientes.</p>
                        <div class="d-grid gap-2">
                            <a href="{{ route( 'plans.index' ) }}" class="btn btn-primary">
                                <i class="bi bi-list-ul me-2"></i>Ver Todos os Planos
                            </a>
                            <a href="{{ route( 'plans.create' ) }}" class="btn btn-outline-primary">
                                <i class="bi bi-plus-circle me-2"></i>Criar Novo Plano
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usuários -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-4"
                            style="width: 80px; height: 80px;">
                            <i class="bi bi-people fs-2"></i>
                        </div>
                        <h5 class="card-title text-success mb-3">Usuários</h5>
                        <p class="card-text text-muted mb-4">Gerencie usuários e permissões do sistema. Controle o acesso e
                            mantenha a segurança.</p>
                        <div class="d-grid gap-2">
                            <a href="{{ route( 'users.index' ) }}" class="btn btn-success">
                                <i class="bi bi-list-ul me-2"></i>Ver Todos os Usuários
                            </a>
                            <a href="{{ route( 'users.create' ) }}" class="btn btn-outline-success">
                                <i class="bi bi-person-plus me-2"></i>Adicionar Usuário
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orçamentos -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center mb-4"
                            style="width: 80px; height: 80px;">
                            <i class="bi bi-receipt fs-2"></i>
                        </div>
                        <h5 class="card-title text-info mb-3">Orçamentos</h5>
                        <p class="card-text text-muted mb-4">Crie e gerencie orçamentos dos clientes. Acompanhe o status e
                            histórico completo.</p>
                        <div class="d-grid gap-2">
                            <a href="{{ route( 'budgets.index' ) }}" class="btn btn-info">
                                <i class="bi bi-list-ul me-2"></i>Ver Todos os Orçamentos
                            </a>
                            <a href="{{ route( 'budgets.create' ) }}" class="btn btn-outline-info">
                                <i class="bi bi-plus-circle me-2"></i>Criar Novo Orçamento
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Atividades Recentes -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2 text-primary"></i>
                            Atividades Recentes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-graph-up fs-1 mb-3"></i>
                            <p>Em breve: Histórico de atividades e relatórios detalhados</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'styles' )
    <style>
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .rounded-circle {
            transition: transform 0.3s ease;
        }

        .card-hover:hover .rounded-circle {
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .d-flex.gap-2 {
                flex-direction: column;
            }

            .card-body.p-4 {
                padding: 2rem 1rem !important;
            }
        }
    </style>
@endpush
