@extends( 'layouts.app' )

@section( 'content' )
<div class="container-fluid py-4">
  {{-- Cabeçalho --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">
      <i class="bi bi-file-earmark-text me-2"></i>Orçamentos
    </h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route( 'dashboard' ) }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Orçamentos</li>
      </ol>
    </nav>
  </div>

  {{-- Cards de Ação --}}
  <div class="row g-4 mb-5">
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-0 hover-shadow">
        <div class="card-body d-flex flex-column">
          <div class="d-flex align-items-center mb-3">
            <div class="bg-primary bg-opacity-10 me-3">
              <i class="bi bi-graph-up"></i>
            </div>
            <h5 class="card-title mb-0">Relatório de Orçamentos</h5>
          </div>
          <p class="card-text text-muted flex-grow-1">
            Visualize todos os orçamentos gerados no sistema.
          </p>
          <a href="{{ route( 'budgets.reports' ) }}" class="btn btn-primary">
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
            <h5 class="card-title mb-0">Novo Orçamento</h5>
          </div>
          <p class="card-text text-muted flex-grow-1">
            Crie um novo orçamento para um cliente.
          </p>
          <a href="{{ route( 'budgets.create' ) }}" class="btn btn-success">
            Criar Orçamento
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- Filtros --}}
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
      <form id="filter-form" class="row g-3">
        @csrf

        {{-- Nº Orçamento --}}
        <div class="col-md-2">
          <div class="form-floating">
            <input type="text" class="form-control" id="code" name="code" placeholder="Nº Orçamento"
              value="{{ request( 'code' ) }}">
            <label for="code">
              <i class="bi bi-hash me-1"></i>Nº Orçamento
            </label>
          </div>
        </div>

        {{-- Data Inicial --}}
        <div class="col-md-2">
          <div class="form-floating">
            <input type="date" class="form-control" id="start_date" name="start_date" placeholder="Data Inicial"
              value="{{ request( 'start_date' ) }}">
            <label for="start_date">
              <i class="bi bi-calendar-event me-1"></i>Data Inicial
            </label>
          </div>
        </div>

        {{-- Data Final --}}
        <div class="col-md-2">
          <div class="form-floating">
            <input type="date" class="form-control" id="end_date" name="end_date" placeholder="Data Final"
              value="{{ request( 'end_date' ) }}">
            <label for="end_date">
              <i class="bi bi-calendar-event me-1"></i>Data Final
            </label>
          </div>
        </div>

        {{-- Cliente --}}
        <div class="col-md-2">
          <div class="form-floating position-relative">
            <input type="text" class="form-control" id="customer_name" name="customer_name"
              placeholder="Digite nome, CPF ou CNPJ" value="{{ request( 'customer_name' ) }}" autocomplete="off"
              data-bs-toggle="tooltip" data-bs-placement="top" title="Busque por nome, CPF ou CNPJ do cliente">
            <label for="customer_name">
              <i class="bi bi-person me-1"></i>Cliente
            </label>
          </div>
          <div class="form-text text-muted small mt-1">
            <i class="bi bi-info-circle me-1"></i>
            Pesquise por nome, CPF ou CNPJ
          </div>
        </div>

        {{-- Valor Mínimo --}}
        <div class="col-md-2">
          <div class="form-floating">
            <input type="text" class="form-control money-input" id="total" name="total" placeholder="Valor Mínimo"
              value="{{ request( 'total' ) }}" autocomplete="off">
            <label for="total">
              <i class="bi bi-currency-dollar me-1"></i>Valor Mínimo
            </label>
          </div>
        </div>

        {{-- Status --}}
        <div class="col-md-2">
          <div class="form-floating">
            <select class="form-select" id="status" name="status">
              <option value="">Todos os Status</option>
              <option value="pending" {{ request( 'status' ) == 'pending' ? 'selected' : '' }}>Pendente
              </option>
              <option value="approved" {{ request( 'status' ) == 'approved' ? 'selected' : '' }}>Aprovado
              </option>
              <option value="rejected" {{ request( 'status' ) == 'rejected' ? 'selected' : '' }}>Rejeitado
              </option>
              <option value="completed" {{ request( 'status' ) == 'completed' ? 'selected' : '' }}>Concluído
              </option>
            </select>
            <label for="status">
              <i class="bi bi-flag me-1"></i>Status
            </label>
          </div>
        </div>

        {{-- Botões --}}
        <div class="col-12 d-flex justify-content-end gap-2 mt-4">
          <button type="button" id="clear-filters" class="btn btn-light btn-lg px-4">
            <i class="bi bi-x-circle me-2"></i>Limpar
          </button>
          <button type="submit" class="btn btn-primary btn-lg px-4">
            <i class="bi bi-search me-2"></i>Filtrar
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Mensagem Inicial --}}
  <div id="initial-message" class="card border-0 shadow-sm text-center py-5">
    <div class="card-body">
      <i class="bi bi-funnel-fill text-primary mb-3" style="font-size: 3rem;"></i>
      <h5 class="text-gray-800 mb-3">Utilize os filtros acima para buscar orçamentos</h5>
      <p class="text-muted mb-0">
        Configure os critérios desejados e clique em "Filtrar" para visualizar os resultados
      </p>
    </div>
  </div>

  {{-- Loading Spinner --}}
  <div id="loading-spinner" class="d-none">
    <div class="card border-0 shadow-sm">
      <div class="card-body text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Carregando...</span>
        </div>
        <p class="text-muted mt-3 mb-0">Processando sua solicitação...</p>
      </div>
    </div>
  </div>

  {{-- Resultados --}}
  <div id="results-container" class="d-none">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-transparent border-0">
        <div class="d-flex justify-content-between align-items-center">
          <h2 class="h5 mb-0">
            <i class="bi bi-list me-2"></i>Orçamentos
          </h2>
          <span id="results-count" class="text-muted">
            Mostrando <span id="results-total">0</span> resultados
          </span>
        </div>
      </div>

      {{-- Tabela de Resultados --}}
      <div class="card-body p-0">
        <div class="table-responsive">
          <table id="results-table" class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th style="width: 10%; text-align: left;">Nº Orçamento</th>
                <th style="width: 20%; text-align: left;">Cliente</th>
                <th style="width: 30%; text-align: left;">Descrição</th>
                <th style="width: 10%; text-align: left;">Data Criação</th>
                <th style="width: 10%; text-align: left;">Data Vencimento</th>
                <th style="width: 10%; text-align: right;">Valor Total</th>
                <th style="width: 10%; text-align: right;">Status</th>
                <th scope="col" style="width: 10%">Ações</th>
              </tr>
            </thead>
            <tbody>
              {{-- Será preenchido via JavaScript --}}
            </tbody>
          </table>
        </div>
      </div>

      {{-- Paginação --}}
      <div class="card-footer bg-transparent border-0">
        <div id="pagination-container" class="d-flex justify-content-center">
          {{-- Paginação será implementada via JavaScript --}}
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Modal de confirmação de exclusão --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar Exclusão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Tem certeza que deseja excluir este orçamento?</p>
        <p class="text-danger"><strong>Atenção:</strong> Esta ação não pode ser desfeita.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Excluir</a>
      </div>
    </div>
  </div>
</div>
@endsection

@section( 'scripts' )
<script>
function confirmDelete(budgetId) {
  const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
  const confirmBtn = document.getElementById('confirmDeleteBtn');
  confirmBtn.href = `/budgets/${budgetId}`;
  modal.show();
}
</script>
@vite( [ 'resources/js/budget.js' ] )
@endsection