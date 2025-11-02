@extends( 'layouts.app' )

@section( 'content' )
  <div class="container-fluid py-4">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center py-5">
            <div class="mb-4">
              <i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
            </div>
            <h2 class="card-title text-muted mb-3">Erro interno do servidor</h2>
            <p class="card-text text-muted mb-4">
              Ocorreu um erro inesperado. Nossa equipe foi notificada e estamos trabalhando para resolver o problema.
            </p>

            @if( isset( $error ) && app()->environment( 'local' ) )
              <div class="alert alert-danger">
                <h6>Detalhes do erro (apenas em desenvolvimento):</h6>
                <code>{{ $error->getMessage() }}</code>
              </div>
            @endif

            <div class="d-flex justify-content-center gap-3">
              <a href="{{ route( 'home' ) }}" class="btn btn-primary">
                <i class="bi bi-house me-2"></i>Voltar ao Início
              </a>
              <button onclick="window.location.reload()" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-2"></i>Tentar Novamente
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
