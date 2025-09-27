@extends( 'layouts.app' )

@section( 'title', $plan->name . ' - Easy Budget' )

@section( 'content' )
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center">
                    <a href="{{ route( 'plans.index' ) }}" class="btn btn-outline-secondary me-3">
                        <i class="bi bi-arrow-left me-1"></i>
                        Voltar
                    </a>
                    <div>
                        <h1 class="h3 mb-1">
                            <i class="bi bi-diagram-3 text-primary me-2"></i>
                            {{ $plan->name }}
                        </h1>
                        @if( $plan->slug )
                            <p class="text-muted mb-0">Slug: <code>{{ $plan->slug }}</code></p>
                        @endif
                    </div>
                </div>
                <div class="btn-group" role="group">
                    <a href="{{ route( 'plans.edit', $plan ) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-2"></i>
                        Editar
                    </a>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route( 'plans.duplicate', $plan ) ?? '#' }}">
                                    <i class="bi bi-copy me-2"></i>
                                    Duplicar Plano
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                @if( $plan->status === 'active' )
                                    <form method="POST" action="{{ route( 'plans.destroy', $plan ) }}" class="d-inline">
                                        @csrf
                                        @method( 'DELETE' )
                                        <button type="submit" class="dropdown-item text-danger"
                                            onclick="return confirm('Tem certeza que deseja desativar este plano?')">
                                            <i class="bi bi-pause me-2"></i>
                                            Desativar
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route( 'plans.activate', $plan ) }}" class="d-inline">
                                        @csrf
                                        @method( 'PATCH' )
                                        <button type="submit" class="dropdown-item text-success">
                                            <i class="bi bi-play me-2"></i>
                                            Ativar
                                        </button>
                                    </form>
                                @endif
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Informações Principais -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-info-circle text-primary me-2"></i>
                                Informações do Plano
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-tag text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Nome</small>
                                            <div class="fw-bold">{{ $plan->name }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-cash text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Preço</small>
                                            <div class="fw-bold text-success">R$
                                                {{ number_format( $plan->price, 2, ',', '.' ) }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-bar-chart text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Máx. Orçamentos</small>
                                            <div class="fw-bold">{{ $plan->max_budgets ?? 'Ilimitado' }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-people text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Máx. Clientes</small>
                                            <div class="fw-bold">{{ $plan->max_clients ?? 'Ilimitado' }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-toggle-on text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Status</small>
                                            <div>
                                                @if( $plan->status === 'active' )
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        Ativo
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-x-circle me-1"></i>
                                                        Inativo
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-calendar text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Criado em</small>
                                            <div class="fw-bold">{{ $plan->created_at->format( 'd/m/Y H:i' ) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if( $plan->description )
                                <div class="mt-4">
                                    <h6 class="text-muted mb-2">Descrição</h6>
                                    <div class="bg-light p-3 rounded">
                                        {!! nl2br( e( $plan->description ) ) !!}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Estatísticas -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-graph-up text-primary me-2"></i>
                                Estatísticas de Uso
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="border-end">
                                        <h2 class="text-primary mb-1">
                                            {{ $plan->users()->where( 'status', 'active' )->count() }}</h2>
                                        <small class="text-muted">Usuários Ativos</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border-end">
                                        <h2 class="text-success mb-1">{{ $plan->budgets()->count() }}</h2>
                                        <small class="text-muted">Orçamentos Criados</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <h2 class="text-info mb-1">{{ $plan->users()->count() }}</h2>
                                    <small class="text-muted">Total de Usuários</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Ações Rápidas -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-lightning text-primary me-2"></i>
                                Ações Rápidas
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route( 'users.create', [ 'plan_id' => $plan->id ] ) }}"
                                    class="btn btn-outline-primary">
                                    <i class="bi bi-person-plus me-2"></i>
                                    Criar Usuário
                                </a>
                                <a href="{{ route( 'budgets.create', [ 'plan_id' => $plan->id ] ) }}"
                                    class="btn btn-outline-success">
                                    <i class="bi bi-receipt-plus me-2"></i>
                                    Criar Orçamento
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Últimos Usuários -->
                    @if( $plan->users()->count() > 0 )
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-people text-primary me-2"></i>
                                    Usuários Recentes
                                </h6>
                                <a href="{{ route( 'users.index', [ 'plan_id' => $plan->id ] ) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    Ver Todos
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    @foreach( $plan->users()->latest()->limit( 5 )->get() as $user )
                                        <div class="list-group-item d-flex align-items-center">
                                            <div class="avatar-sm me-3">
                                                <div class="avatar-title bg-primary text-white rounded-circle">
                                                    {{ strtoupper( substr( $user->name, 0, 1 ) ) }}
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0">{{ $user->name }}</h6>
                                                <small class="text-muted">{{ $user->email }}</small>
                                            </div>
                                            @if( $user->status === 'active' )
                                                <span class="badge bg-success">Ativo</span>
                                            @else
                                                <span class="badge bg-danger">Inativo</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section( 'page-scripts' )
    <script>
        // Gráfico simples de estatísticas (se necessário)
        document.addEventListener( 'DOMContentLoaded', function () {
            // Adicionar interatividade se necessário
            console.log( 'Plano {{ $plan->id }} carregado' );
        } );
    </script>
@endsection
