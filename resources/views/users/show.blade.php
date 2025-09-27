@extends( 'layouts.app' )

@section( 'title', $user->name . ' - Easy Budget' )

@section( 'content' )
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center">
                    <a href="{{ route( 'users.index' ) }}" class="btn btn-outline-secondary me-3">
                        <i class="bi bi-arrow-left me-1"></i>
                        Voltar
                    </a>
                    <div>
                        <h1 class="h3 mb-1">
                            <i class="bi bi-person-circle text-primary me-2"></i>
                            {{ $user->name }}
                        </h1>
                        <p class="text-muted mb-0">{{ $user->email }}</p>
                    </div>
                </div>
                <div class="btn-group" role="group">
                    <a href="{{ route( 'users.edit', $user ) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-2"></i>
                        Editar
                    </a>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route( 'budgets.create', [ 'user_id' => $user->id ] ) }}">
                                    <i class="bi bi-receipt-plus me-2"></i>
                                    Criar Orçamento
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route( 'users.login-as', $user ) ?? '#' }}">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Fazer Login Como
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                @if( $user->status === 'active' )
                                    <form method="POST" action="{{ route( 'users.destroy', $user ) }}" class="d-inline">
                                        @csrf
                                        @method( 'DELETE' )
                                        <button type="submit" class="dropdown-item text-danger"
                                            onclick="return confirm('Tem certeza que deseja desativar este usuário?')">
                                            <i class="bi bi-pause me-2"></i>
                                            Desativar
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route( 'users.activate', $user ) }}" class="d-inline">
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
                                Informações do Usuário
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-person text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Nome Completo</small>
                                            <div class="fw-bold">{{ $user->name }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-envelope text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Email</small>
                                            <div class="fw-bold">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-diagram-3 text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Plano</small>
                                            <div>
                                                @if( $user->plan )
                                                    <span class="badge bg-info">{{ $user->plan->name }}</span>
                                                    <br>
                                                    <small class="text-muted">R$
                                                        {{ number_format( $user->plan->price, 2, ',', '.' ) }}</small>
                                                @else
                                                    <span class="text-muted">Sem plano</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-building text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Tenant</small>
                                            <div class="fw-bold">{{ $user->tenant_id ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-toggle-on text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Status</small>
                                            <div>
                                                @if( $user->status === 'active' )
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
                                            <small class="text-muted">Último Login</small>
                                            <div class="fw-bold">
                                                {{ $user->last_login_at ? $user->last_login_at->format( 'd/m/Y H:i' ) : 'Nunca' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if( $user->notes )
                                <div class="mt-4">
                                    <h6 class="text-muted mb-2">Observações</h6>
                                    <div class="bg-light p-3 rounded">
                                        {!! nl2br( e( $user->notes ) ) !!}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Orçamentos Recentes -->
                    @if( $user->budgets()->count() > 0 )
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-receipt text-primary me-2"></i>
                                    Orçamentos Recentes
                                </h5>
                                <a href="{{ route( 'budgets.index', [ 'user_id' => $user->id ] ) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    Ver Todos
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    @foreach( $user->budgets()->latest()->limit( 5 )->get() as $budget )
                                        <div class="list-group-item d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <div class="fw-bold">{{ $budget->code ?? 'ORC-' . $budget->id }}</div>
                                                    <small class="text-muted">{{ $budget->created_at->format( 'd/m/Y' ) }}</small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold text-success">R$
                                                    {{ number_format( $budget->amount, 2, ',', '.' ) }}</div>
                                                <small class="text-muted">{{ $budget->status }}</small>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Estatísticas -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-graph-up text-primary me-2"></i>
                                Estatísticas
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h3 class="text-primary mb-1">{{ $user->budgets()->count() }}</h3>
                                        <small class="text-muted">Orçamentos</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h3 class="text-success mb-1">
                                        R$ {{ number_format( $user->budgets()->sum( 'amount' ), 2, ',', '.' ) }}
                                    </h3>
                                    <small class="text-muted">Total</small>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                <a href="{{ route( 'budgets.create', [ 'user_id' => $user->id ] ) }}"
                                    class="btn btn-outline-success">
                                    <i class="bi bi-receipt-plus me-2"></i>
                                    Novo Orçamento
                                </a>
                                <a href="mailto:{{ $user->email }}" class="btn btn-outline-info">
                                    <i class="bi bi-envelope me-2"></i>
                                    Enviar Email
                                </a>
                                @if( $user->plan )
                                    <a href="{{ route( 'plans.show', $user->plan ) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-diagram-3 me-2"></i>
                                        Ver Plano
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Timeline de Atividades -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-clock-history text-primary me-2"></i>
                                Atividade Recente
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <small class="text-muted">Usuário criado</small>
                                        <div class="fw-bold">{{ $user->created_at->format( 'd/m/Y H:i' ) }}</div>
                                    </div>
                                </div>
                                @if( $user->last_login_at )
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-success"></div>
                                        <div class="timeline-content">
                                            <small class="text-muted">Último login</small>
                                            <div class="fw-bold">{{ $user->last_login_at->format( 'd/m/Y H:i' ) }}</div>
                                        </div>
                                    </div>
                                @endif
                                @if( $user->updated_at != $user->created_at )
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-warning"></div>
                                        <div class="timeline-content">
                                            <small class="text-muted">Última atualização</small>
                                            <div class="fw-bold">{{ $user->updated_at->format( 'd/m/Y H:i' ) }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section( 'styles' )
    <style>
        .avatar-sm {
            width: 2.5rem;
            height: 2.5rem;
        }

        .avatar-title {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.875rem;
        }

        .timeline {
            position: relative;
            padding-left: 1.5rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0.75rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1rem;
        }

        .timeline-marker {
            position: absolute;
            left: -1.5rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .timeline-content {
            padding-bottom: 0.5rem;
        }
    </style>
@endsection
