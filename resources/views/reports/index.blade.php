@extends( 'layouts.app' )

@section( 'content' )
<div class="container-fluid py-4">
  {{-- Cabeçalho --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">
      <i class="bi bi-graph-up me-2"></i>Relatórios
    </h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route( 'dashboard' ) }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Relatórios</li>
      </ol>
    </nav>
  </div>

  {{-- Mensagens de erro/sucesso --}}
  @if( session( 'success' ) )
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session( 'success' ) }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  @endif

  @if( session( 'error' ) )
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session( 'error' ) }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  @endif

  {{-- Cards de Ação --}}
  <div class="row g-4 mb-5">
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-0 hover-shadow">
        <div class="card-body d-flex flex-column">
          <div class="d-flex align-items-center mb-3">
            <div class="bg-primary bg-opacity-10 me-3">
              <i class="bi bi-people"></i>
            </div>
            <h5 class="card-title mb-0">Relatório de Clientes</h5>
          </div>
          <p class="card-text text-muted flex-grow-1">
            Visualize todos os clientes cadastrados no sistema.
          </p>
          <a href="{{ route( 'reports.customers' ) }}" class="btn btn-primary">
            Acessar Relatório
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-0 hover-shadow">
        <div class="card-body d-flex flex-column">
          <div class="d-flex align-items-center mb-3">
            <div class="bg-warning bg-opacity-10 me-3">
              <i class="bi bi-box"></i>
            </div>
            <h5 class="card-title mb-0">Relatório de Produtos</h5>
          </div>
          <p class="card-text text-muted flex-grow-1">
            Visualize todos os produtos no sistema.
          </p>
          <a href="{{ route( 'reports.products' ) }}" class="btn btn-warning text-white">
            Acessar Relatório
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-0 hover-shadow">
        <div class="card-body d-flex flex-column">
          <div class="d-flex align-items-center mb-3">
            <div class="bg-success bg-opacity-10 me-3">
              <i class="bi bi-file-earmark-text"></i>
            </div>
            <h5 class="card-title mb-0">Relatório de Orçamentos</h5>
          </div>
          <p class="card-text text-muted flex-grow-1">
            Visualize todos os orçamentos gerados no sistema.
          </p>
          <a href="{{ route( 'reports.budgets' ) }}" class="btn btn-success">
            Acessar Relatório
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-0 hover-shadow">
        <div class="card-body d-flex flex-column">
          <div class="d-flex align-items-center mb-3">
            <div class="bg-info bg-opacity-10 me-3">
              <i class="bi bi-tools"></i>
            </div>
            <h5 class="card-title mb-0">Relatório de Serviços</h5>
          </div>
          <p class="card-text text-muted flex-grow-1">
            Visualize todos os serviços prestados no sistema.
          </p>
          <a href="{{ route( 'reports.services' ) }}" class="btn btn-info text-white">
            Acessar Relatório
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- Tabela de Relatórios --}}
  <div class="card shadow-sm border-0">
    <div class="card-header py-3">
      <h2 class="h5 mb-0">
        <i class="bi bi-list me-2"></i>Relatórios Recentes
      </h2>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th scope="col" class="px-4" style="width: 20%">Tipo</th>
              <th scope="col" style="width: 30%">Descrição</th>
              <th scope="col" style="width: 15%">Data</th>
              <th scope="col" style="width: 15%">Tamanho</th>
              <th scope="col" style="width: 10%">Status</th>
              <th scope="col" style="width: 10%">Ações</th>
            </tr>
          </thead>
          <tbody>
            {{-- Exemplo de dados - será populado com dados reais --}}
            <tr>
              <td colspan="6" class="text-center py-4">
                <div class="text-muted">
                  <i class="bi bi-inbox display-6 d-block mb-3"></i>
                  <p class="mb-0">Nenhum relatório gerado recentemente</p>
                  <small>Selecione um tipo de relatório acima para gerar</small>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@section( 'scripts' )
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Funcionalidades JavaScript para relatórios
  console.log('Reports index page loaded');
});
</script>
@endsection