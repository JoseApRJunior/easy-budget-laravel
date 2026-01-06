@extends( 'layouts.app' )

@section( 'title', 'Status do Pagamento' )

@section('content')
    <div class="container-fluid py-4">
        <x-page-header
            title="Status do Pagamento"
            icon="credit-card"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Planos' => route('provider.plans.index'),
                'Status do Pagamento' => '#'
            ]">
            <p class="text-muted mb-0">Confira o status da sua transação</p>
        </x-page-header>

        <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">Status do Pagamento</h4>
          </div>
          <div class="card-body text-center">
            @if( $status === 'approved' )
              <div class="alert alert-success">
                <h5><i class="fas fa-check-circle"></i> Pagamento Aprovado!</h5>
                <p>Seu plano foi ativado com sucesso.</p>
              </div>
            @elseif( $status === 'pending' )
              <div class="alert alert-warning">
                <h5><i class="fas fa-clock"></i> Pagamento Pendente</h5>
                <p>Estamos aguardando a confirmação do pagamento.</p>
              </div>
            @elseif( $status === 'rejected' )
              <div class="alert alert-danger">
                <h5><i class="fas fa-times-circle"></i> Pagamento Rejeitado</h5>
                <p>Houve um problema com seu pagamento. Tente novamente.</p>
              </div>
            @else
              <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> Status Desconhecido</h5>
                <p>Não foi possível determinar o status do pagamento.</p>
              </div>
            @endif

            @if( isset( $plan_slug ) )
              <div class="mt-4">
                <a href="{{ route( 'provider.plans.show', $plan_slug ) }}" class="btn btn-primary">
                  Ver Detalhes do Plano
                </a>
                <a href="{{ route( 'provider.plans.index' ) }}" class="btn btn-secondary">
                  Voltar para Planos
                </a>
              </div>
            @else
              <div class="mt-4">
                <a href="{{ route( 'provider.plans.index' ) }}" class="btn btn-primary">
                  Ir para Planos
                </a>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
