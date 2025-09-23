@extends( 'layouts.app' )

@section( 'content' )
<div class="container-fluid py-4">
  {{-- Cabeçalho --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
      <i class="bi bi-file-earmark-text me-2"></i>Visualizar Relatório
    </h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route( 'dashboard' ) }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route( 'reports.index' ) }}">Relatórios</a></li>
        <li class="breadcrumb-item active">{{ $report->type ?? 'Relatório' }}</li>
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

  {{-- Informações do Relatório --}}
  <div class="row g-4 mb-4">
    <div class="col-md-8">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <div class="row g-4 mb-4">
            <div class="col-md-6">
              <div class="d-flex flex-column">
                <label class="text-muted small mb-1">Tipo de Relatório</label>
                <h5 class="mb-0 fw-semibold">{{ $report->type ?? 'N/A' }}</h5>
              </div>
            </div>
            <div class="col-md-6">
              <div class="d-flex flex-column">
                <label class="text-muted small mb-1">Data de Geração</label>
                <h5 class="mb-0">
                  {{ $report->created_at ? $report->created_at->format( 'd/m/Y H:i' ) : 'N/A' }}</h5>
              </div>
            </div>
          </div>

          <div class="mb-4">
            <label class="text-muted small mb-1">Descrição</label>
            <p class="mb-0 lead">{{ $report->description ?? 'Sem descrição' }}</p>
          </div>

          {{-- Conteúdo do Relatório --}}
          <div class="mb-4">
            <label class="text-muted small mb-1">Conteúdo do Relatório</label>
            <div class="border rounded p-3 bg-light">
              @if( $report->type === 'budgets' )
              {{-- Relatório de Orçamentos --}}
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Código</th>
                      <th>Cliente</th>
                      <th>Status</th>
                      <th>Valor Total</th>
                      <th>Data</th>
                    </tr>
                  </thead>
                  <tbody>
                    {{-- Dados serão populados dinamicamente --}}
                    <tr>
                      <td colspan="5" class="text-center py-3">
                        <div class="text-muted">
                          <i class="bi bi-graph-up me-2"></i>
                          Relatório de orçamentos será carregado aqui
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              @elseif( $report->type === 'customers' )
              {{-- Relatório de Clientes --}}
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Nome</th>
                      <th>Email</th>
                      <th>Telefone</th>
                      <th>Data Cadastro</th>
                    </tr>
                  </thead>
                  <tbody>
                    {{-- Dados serão populados dinamicamente --}}
                    <tr>
                      <td colspan="4" class="text-center py-3">
                        <div class="text-muted">
                          <i class="bi bi-people me-2"></i>
                          Relatório de clientes será carregado aqui
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              @elseif( $report->type === 'products' )
              {{-- Relatório de Produtos --}}
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Nome</th>
                      <th>Categoria</th>
                      <th>Preço</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {{-- Dados serão populados dinamicamente --}}
                    <tr>
                      <td colspan="4" class="text-center py-3">
                        <div class="text-muted">
                          <i class="bi bi-box me-2"></i>
                          Relatório de produtos será carregado aqui
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              @elseif( $report->type === 'services' )
              {{-- Relatório de Serviços --}}
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Descrição</th>
                      <th>Categoria</th>
                      <th>Status</th>
                      <th>Valor</th>
                    </tr>
                  </thead>
                  <tbody>
                    {{-- Dados serão populados dinamicamente --}}
                    <tr>
                      <td colspan="4" class="text-center py-3">
                        <div class="text-muted">
                          <i class="bi bi-tools me-2"></i>
                          Relatório de serviços será carregado aqui
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              @else
              {{-- Relatório Genérico --}}
              <div class="text-center py-4">
                <i class="bi bi-file-earmark-text text-primary mb-3" style="font-size: 3rem;"></i>
                <h5>Relatório Gerado</h5>
                <p class="text-muted">O relatório foi gerado com sucesso e está disponível para
                  visualização.</p>
              </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Informações do Relatório --}}
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent">
          <h5 class="card-title mb-0">
            <i class="bi bi-info-circle me-2"></i>Informações
          </h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="text-muted small">ID do Relatório</label>
            <p class="mb-0 fw-semibold">{{ $report->id ?? 'N/A' }}</p>
          </div>

          <div class="mb-3">
            <label class="text-muted small">Tipo</label>
            <p class="mb-0">
              <span class="badge bg-primary">{{ $report->type ?? 'N/A' }}</span>
            </p>
          </div>

          <div class="mb-3">
            <label class="text-muted small">Status</label>
            <p class="mb-0">
              <span class="badge bg-success">Concluído</span>
            </p>
          </div>

          <div class="mb-3">
            <label class="text-muted small">Data de Criação</label>
            <p class="mb-0">{{ $report->created_at ? $report->created_at->format( 'd/m/Y H:i' ) : 'N/A' }}</p>
          </div>

          <div class="mb-3">
            <label class="text-muted small">Tamanho do Arquivo</label>
            <p class="mb-0">
              {{ $report->file_size ? number_format( $report->file_size / 1024, 2 ) . ' KB' : 'N/A' }}</p>
          </div>

          <div class="mb-3">
            <label class="text-muted small">Formato</label>
            <p class="mb-0">{{ strtoupper( $report->format ?? 'pdf' ) }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Botões de ação --}}
  <div class="d-flex justify-content-between mt-4">
    <a href="{{ route( 'reports.index' ) }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-2"></i>Voltar
    </a>
    <div class="d-flex gap-2">
      <a href="{{ route( 'reports.download', $report->id ?? '' ) }}" class="btn btn-success">
        <i class="bi bi-download me-2"></i>Download
      </a>
      <a href="{{ route( 'reports.export', $report->id ?? '' ) }}" class="btn btn-primary">
        <i class="bi bi-file-earmark-arrow-down me-2"></i>Exportar
      </a>
      <button type="button" class="btn btn-outline-danger" onclick="window.print()">
        <i class="bi bi-printer me-2"></i>Imprimir
      </button>
    </div>
  </div>
</div>
@endsection

@section( 'scripts' )
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Funcionalidades JavaScript para visualização de relatórios
  console.log('Report show page loaded');
});
</script>
@endsection