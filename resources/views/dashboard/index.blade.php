@extends( 'layouts.app' )

@section( 'content' )
  <div class="container-fluid py-1">

    @php
      $user        = auth()->user();
      $pendingPlan = $user?->pendingPlan();
      $isAdmin     = $user?->hasRole( 'admin' );
    @endphp

    {{-- Alertas de plano (apenas para providers) --}}
    @unless( $isAdmin )
      @includeWhen( $user?->isTrialExpired(), 'partials.components.provider.plan-alert' )
      @includeWhen( $user?->isTrial() || ( $pendingPlan && $pendingPlan->status === 'pending' ), 'partials.components.provider.plan-modal' )
    @endunless

    <h1 class="mb-4">
      @if( $isAdmin )
        Painel Administrativo
      @else
        Painel do Prestador
      @endif
    </h1>

    {{-- Verificar se os dados do dashboard estão disponíveis --}}
    @if( isset( $metrics ) && isset( $charts ) && isset( $recentTransactions ) && isset( $quickActions ) )
      {{-- Transformar dados do MetricsService para o formato esperado pelos componentes --}}
      @php
        $financialSummary = [
          'monthly_revenue'       => $metrics[ 'receita_total' ][ 'valor' ] ?? 0,
          'pending_budgets'       => [
            'count' => 0, // TODO: implementar contagem de orçamentos pendentes
            'total' => 0  // TODO: implementar soma de orçamentos pendentes
          ],
          'overdue_payments'      => [
            'count' => 0, // TODO: implementar contagem de pagamentos atrasados
            'total' => 0  // TODO: implementar soma de pagamentos atrasados
          ],
          'next_month_projection' => 0 // TODO: implementar projeção do próximo mês
        ];

        $translations = [
          'actionIcons'            => [
            'created_budget' => 'bi-file-earmark-plus',
            'updated_budget' => 'bi-pencil-square',
            'deleted_budget' => 'bi-trash',
          ],
          'textColors'             => [
            'created_budget' => 'text-success',
            'updated_budget' => 'text-warning',
            'deleted_budget' => 'text-danger',
          ],
          'descriptionTranslation' => [
            'created_budget' => 'Orçamento Criado',
            'updated_budget' => 'Orçamento Atualizado',
            'deleted_budget' => 'Orçamento Removido',
          ],
        ];
      @endphp

      <div class="row g-4">
        <div class="col-12 col-lg-6">
          <x-financial-summary :summary="$financialSummary" />
        </div>
        <div class="col-12 col-lg-6">
          <x-activities :activities="$recentTransactions" :translations="$translations" :total="count( $recentTransactions )" />
        </div>
      </div>

      {{-- Linha 2: Ações Rápidas --}}
      @if( !$isAdmin )
        <div class="row g-4 mt-2">
          <div class="col-12">
            <x-quick-actions />
          </div>
        </div>
      @else
        <div class="row g-4 mt-2">
          <div class="col-12">
            <div class="card border-0 shadow-sm hover-card mb-4">
              <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                  <i class="bi bi-lightning-charge me-2"></i>Ações Administrativas
                </h5>
              </div>
              <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                  <a href="{{ route( 'admin.users.index' ) }}" class="btn btn-primary">
                    <i class="bi bi-people me-1"></i> Gerenciar Usuários
                  </a>
                  <a href="{{ route( 'admin.settings' ) }}" class="btn btn-info">
                    <i class="bi bi-gear me-1"></i> Configurações do Sistema
                  </a>
                  <a href="{{ route( 'admin.reports.index' ) }}" class="btn btn-outline-success">
                    <i class="bi bi-graph-up me-1"></i> Relatórios do Sistema
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      @endif

      {{-- Linha 3: Gráficos --}}
      <div class="row g-4 mt-2">
        <div class="col-12">
          {{-- Gráficos serão implementados posteriormente --}}
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">Análise de Performance</h5>
            </div>
            <div class="card-body">
              <p class="text-muted">Gráficos e análises serão implementados em breve.</p>
            </div>
          </div>
        </div>
      </div>
    @else
      {{-- Fallback para quando os dados não estão disponíveis --}}
      <div class="row g-4">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">Dashboard</h5>
            </div>
            <div class="card-body">
              <div class="alert alert-info">
                <h6>Informações do Sistema</h6>
                <p>Usuário: {{ $user->email }}</p>
                <p>Tipo: {{ $isAdmin ? 'Administrador' : 'Prestador de Serviços' }}</p>
                <p>Tenant ID: {{ $user->tenant_id }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endif

  </div>
@endsection
