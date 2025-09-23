@extends( 'layouts.app' )

@section( 'content' )
<div class="container-fluid py-4">
  {{-- Cabeçalho da página --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
      <i class="bi bi-file-earmark-plus me-2"></i>Novo Orçamento
    </h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route( 'dashboard' ) }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route( 'budgets.index' ) }}">Orçamentos</a></li>
        <li class="breadcrumb-item active">Novo</li>
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

  {{-- Formulário --}}
  <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <form id="create-budget-form" action="{{ route( 'budgets.store' ) }}" method="POST">
        @csrf

        {{-- Seção de Cliente --}}
        <div class="row g-4">
          <div class="col-md-4">
            <div class="form-group">
              <label for="search-input" class="form-label fw-semibold">
                <i class="bi bi-search me-2"></i>Buscar Cliente
              </label>
              <div class="input-group">
                <input type="search" id="search-input" name="search-input" class="form-control"
                  placeholder="Nome, CPF ou CNPJ" autocomplete="off">
                <span class="input-group-text bg-light">
                  <i class="bi bi-search text-muted"></i>
                </span>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group">
              <label for="customer" class="form-label fw-semibold">
                <i class="bi bi-person me-2"></i>Cliente Selecionado
              </label>
              <input type="text" id="customer" name="customer" class="form-control bg-light" disabled required>
              @error( 'customer_id' )
              <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror

              {{-- Lista de sugestões --}}
              <div class="position-relative">
                <ul id="suggestions-list" class="list-group position-absolute w-100 mt-1 shadow-sm rounded-3 d-none">
                </ul>
              </div>
            </div>
          </div>

          {{-- Campos ocultos --}}
          <input type="hidden" id="customer_id" name="customer_id">
          <input type="hidden" id="customer_name" name="customer_name">
          <input type="hidden" id="phone" name="phone">
          <input type="hidden" id="email" name="email">

          {{-- Data de Vencimento --}}
          <div class="col-md-4">
            <div class="form-group">
              <label for="due-date" class="form-label fw-semibold">
                <i class="bi bi-calendar me-2"></i>Previsão de Vencimento
              </label>
              <input type="date" id="due-date" name="due_date"
                class="form-control @error( 'due_date' ) is-invalid @enderror"
                value="{{ old( 'due_date', date( 'Y-m-d', strtotime( '+1 month' ) ) ) }}" min="{{ date( 'Y-m-d' ) }}">
              @error( 'due_date' )
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          {{-- Descrição --}}
          <div class="col-12">
            <div class="form-group">
              <label for="description" class="form-label fw-semibold">
                <i class="bi bi-card-text me-2"></i>Descrição
              </label>
              <textarea id="description" name="description"
                class="form-control @error( 'description' ) is-invalid @enderror" rows="4" maxlength="255"
                placeholder="Ex: Pintura residencial completa">{{ old( 'description' ) }}</textarea>
              @error( 'description' )
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <div class="d-flex justify-content-end">
                <small id="char-count" class="text-muted mt-2">
                  <span id="chars-remaining">255</span> caracteres restantes
                </small>
              </div>
            </div>
          </div>

          {{-- Condições de Pagamento --}}
          <div class="col-12">
            <div class="form-group">
              <label for="payment_terms" class="form-label fw-semibold">
                <i class="bi bi-credit-card me-2"></i>Condições de Pagamento
              </label>
              <textarea id="payment_terms" name="payment_terms"
                class="form-control @error( 'payment_terms' ) is-invalid @enderror" rows="2" maxlength="255"
                placeholder="Ex: Pagamento em 2x no cartão ou à vista com 5% de desconto">{{ old( 'payment_terms' ) }}</textarea>
              @error( 'payment_terms' )
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        {{-- Botões de ação --}}
        <div class="d-flex justify-content-between mt-4 pt-4 border-top">
          <button type="button" onclick="history.back()" class="btn btn-outline-secondary px-4">
            <i class="bi bi-arrow-left me-2"></i>Voltar
          </button>
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-2"></i>Criar Orçamento
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section( 'scripts' )
@vite( [ 'resources/js/budget_create.js' ] )
@endsection