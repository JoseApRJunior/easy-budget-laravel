@extends( 'layouts.app' )

@section( 'title', 'Dashboard de Compartilhamentos' )

@section( 'content' )
  <div class="container-fluid py-4">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0">
          <i class="bi bi-share-fill me-2"></i>Dashboard de Compartilhamentos
        </h1>
        <p class="text-muted mb-0">
          Visão geral dos compartilhamentos de orçamentos com métricas de acesso e performance.
        </p>
      </div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item">
            <a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a>
          </li>
          <li class="breadcrumb-item">
            <a href="{{ route( 'provider.budgets.index' ) }}">Orçamentos</a>
          </li>
          <li class="breadcrumb-item">
            <a href="{{ route( 'provider.budgets.shares.index' ) }}">Compartilhamentos</a>
          </li>
          <li class="breadcrumb-item active" aria-current="page">
            Dashboard
          </li>
        </ol>
      </nav>
    </div>

    @php
      $totalShares = $stats[ 'total_shares' ] ?? 0;
      $activeShares = $stats[ 'active_shares' ] ?? 0;
      $expiredShares = $stats[ 'expired_shares' ] ?? 0;
      $totalAccesses = $stats[ 'access_count' ] ?? 0;
      $recentShares = $stats[ 'recent_shares' ] ?? collect();
      $mostSharedBudgets = $stats[ 'most_shared_budgets' ] ?? collect();

      $activeRate = $totalShares > 0
        ? number_format( ( $activeShares / $totalShares ) * 100, 1, ',', '.' )
        : 0;
    @endphp

    <!-- Cards de Métricas -->
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
              <div class="avatar-circle bg-primary bg-gradient me-3">
                <i class="bi bi-share text-white"></i>
              </div>
              <div>
                <h6 class="text-muted mb-1">Total de Compartilhamentos</h6>
                <h3 class="mb-0">{{ $totalShares }}</h3>
              </div>
            </div>
            <p class="text-muted small mb-0">
              Quantidade total de compartilhamentos criados para seus orçamentos.
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
              <div class="avatar-circle bg-success bg-gradient me-3">
                <i class="bi bi-check-circle-fill text-white"></i>
              </div>
              <div>
                <h6 class="text-muted mb-1">Compartilhamentos Ativos</h6>
                <h3 class="mb-0">{{ $activeShares }}</h3>
              </div>
            </div>
            <p class="text-muted small mb-0">
              Links ativos e disponíveis para acesso aos orçamentos.
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
              <div class="avatar-circle bg-warning bg-gradient me-3">
                <i class="bi bi-clock-fill text-white"></i>
              </div>
              <div>
                <h6 class="text-muted mb-1">Taxa de Atividade</h6>
                <h3 class="mb-0">{{ $activeRate }}%</h3>
              </div>
            </div>
            <p class="text-muted small mb-0">
              Percentual de compartilhamentos ativos em relação ao total.
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
              <div class="avatar-circle bg-info bg-gradient me-3">
                <i class="bi bi-eye text-white"></i>
              </div>
              <div>
                <h6 class="text-muted mb-1">Total de Acessos</h6>
                <h3 class="mb-0">{{ $totalAccesses }}</h3>
              </div>
            </div>
            <p class="text-muted small mb-0">
              Número total de vezes que os orçamentos foram acessados.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="row g-4">
      <!-- Compartilhamentos Recentes -->
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
              <i class="bi bi-clock-history me-2"></i>Compartilhamentos Recentes
            </h5>
            <a href="{{ route( 'provider.budgets.shares.index' ) }}" class="btn btn-sm btn-outline-primary">
              Ver todos
            </a>
          </div>
          <div class="card-body">
            @if( $recentShares instanceof \Illuminate\Support\Collection && $recentShares->isNotEmpty() )
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Orçamento</th>
                      <th>Destinatário</th>
                      <th>Status</th>
                      <th>Acessos</th>
                      <th>Expiração</th>
                      <th class="text-end">Ações</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach( $recentShares as $share )
                      @php
                        $budget = $share->budget;
                        $customer = $budget->customer->commonData ?? null;
                        $customerName = $customer?->company_name
                          ?? trim( ( $customer->first_name ?? '' ) . ' ' . ( $customer->last_name ?? '' ) )
                          ?: 'Cliente não informado';

                        $isExpired = !$share->is_active ||
                            ($share->expires_at && $share->expires_at <= now());

                        $statusBadge = $isExpired
                          ? '<span class="badge bg-danger-subtle text-danger">Expirado</span>'
                          : '<span class="badge bg-success-subtle text-success">Ativo</span>';
                      @endphp
                      <tr>
                        <td>
                          <div class="d-flex align-items-center">
                            <i class="bi bi-file-earmark-text text-primary me-2"></i>
                            <div>
                              <div class="fw-medium">{{ $budget->code }}</div>
                              <small class="text-muted">{{ Str::limit($customerName, 25) }}</small>
                            </div>
                          </div>
                        </td>
                        <td>
                          <div class="d-flex align-items-center">
                            <i class="bi bi-person text-muted me-2"></i>
                            <div>
                              <div class="fw-medium">{{ $share->recipient_name }}</div>
                              <small class="text-muted">{{ $share->recipient_email }}</small>
                            </div>
                          </div>
                        </td>
                        <td>{!! $statusBadge !!}</td>
                        <td>
                          <span class="badge bg-light text-dark">
                            <i class="bi bi-eye me-1"></i>{{ $share->access_count }}
                          </span>
                        </td>
                        <td>
                          @if( $share->expires_at )
                            <small class="text-muted">{{ $share->expires_at->format( 'd/m/Y' ) }}</small>
                          @else
                            <small class="text-muted">Sem expiração</small>
                          @endif
                        </td>
                        <td class="text-end">
                          <a href="{{ route( 'provider.budgets.shares.show', $share->id ) }}"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye"></i>
                          </a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="text-muted mb-0">
                Nenhum compartilhamento recente encontrado. Crie novos compartilhamentos para visualizar aqui.
              </p>
            @endif
          </div>
        </div>
      </div>

      <!-- Orçamentos Mais Compartilhados -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-header bg-transparent border-0">
            <h6 class="mb-0">
              <i class="bi bi-trophy me-2"></i>Orçamentos Mais Compartilhados
            </h6>
          </div>
          <div class="card-body">
            @if( $mostSharedBudgets instanceof \Illuminate\Support\Collection && $mostSharedBudgets->isNotEmpty() )
              <div class="list-group list-group-flush">
                @foreach( $mostSharedBudgets as $budget )
                  @php
                    $customer = $budget->customer->commonData ?? null;
                    $customerName = $customer?->company_name
                      ?? trim( ( $customer->first_name ?? '' ) . ' ' . ( $customer->last_name ?? '' ) )
                      ?: 'Cliente não informado';
                  @endphp
                  <div class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                    <div>
                      <div class="fw-medium">{{ $budget->code }}</div>
                      <small class="text-muted">{{ Str::limit($customerName, 20) }}</small>
                    </div>
                    <span class="badge bg-primary rounded-pill">
                      {{ $budget->shares_count }}
                    </span>
                  </div>
                @endforeach
              </div>
            @else
              <p class="text-muted mb-0">
                Nenhum orçamento foi compartilhado ainda.
              </p>
            @endif
          </div>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-0">
            <h6 class="mb-0">
              <i class="bi bi-lightbulb me-2"></i>Dicas de Uso
            </h6>
          </div>
          <div class="card-body">
            <ul class="list-unstyled mb-0 small text-muted">
              <li class="mb-2">
                <i class="bi bi-share-fill text-primary me-2"></i>
                Compartilhe orçamentos aprovados para aumentar a conversão.
              </li>
              <li class="mb-2">
                <i class="bi bi-clock text-warning me-2"></i>
                Defina prazos de expiração para criar urgência no cliente.
              </li>
              <li class="mb-2">
                <i class="bi bi-eye text-info me-2"></i>
                Monitore os acessos para entender o interesse do cliente.
              </li>
              <li class="mb-0">
                <i class="bi bi-envelope text-success me-2"></i>
                Use notificações automáticas para lembrar os clientes.
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Atalhos Rápidos -->
    <div class="row g-4 mt-1">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-0">
            <h6 class="mb-0">
              <i class="bi bi-link-45deg me-2"></i>Atalhos Rápidos
            </h6>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <a href="{{ route( 'provider.budgets.shares.create' ) }}" class="btn btn-success w-100">
                  <i class="bi bi-plus-circle me-2"></i>Novo Compartilhamento
                </a>
              </div>
              <div class="col-md-3">
                <a href="{{ route( 'provider.budgets.shares.index' ) }}" class="btn btn-outline-primary w-100">
                  <i class="bi bi-list me-2"></i>Gerenciar Compartilhamentos
                </a>
              </div>
              <div class="col-md-3">
                <a href="{{ route( 'provider.budgets.index' ) }}" class="btn btn-outline-secondary w-100">
                  <i class="bi bi-file-earmark-text me-2"></i>Ver Orçamentos
                </a>
              </div>
              <div class="col-md-3">
                <a href="{{ route( 'provider.reports.budgets' ) }}" class="btn btn-outline-info w-100">
                  <i class="bi bi-graph-up me-2"></i>Relatórios
                </a>
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
    .avatar-circle {
      width: 46px;
      height: 46px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .text-code {
      font-family: 'Courier New', monospace;
      background-color: #f8f9fa;
      padding: 2px 6px;
      border-radius: 3px;
      font-size: 0.85em;
    }
  </style>
@endpush
