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

        {{-- Seção de Itens do Orçamento --}}
        <div class="row g-4 mt-4">
          <div class="col-12">
            <div class="card shadow-sm">
              <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0">
                  <i class="bi bi-list-ul me-2"></i> Itens do Orçamento
                </h3>
              </div>
              <div class="card-body p-4">
                <div x-data="budgetItems()" id="budget-items-section">
                  <div class="items-container">
                    <template x-for="(item, index) in items" :key="index">
                      <div class="item-row border p-3 mb-3 bg-light rounded">
                        <div class="row align-items-end">
                          <div class="col-lg-6 col-md-5 mb-2">
                            <label class="form-label">Descrição</label>
                            <input type="text" class="form-control" x-model="item.description" placeholder="Descreva o item/serviço" required>
                          </div>

                          <div class="col-lg-2 col-md-3 mb-2">
                            <label class="form-label">Quantidade</label>
                            <input type="number" class="form-control" x-model.number="item.quantity" min="1" value="1" required>
                          </div>

                          <div class="col-lg-3 col-md-3 mb-2">
                            <label class="form-label">Preço Unitário (R$)</label>
                            <input type="number" class="form-control" x-model.number="item.unit_price" min="0" step="0.01" placeholder="0.00" required>
                          </div>

                          <div class="col-lg-1 col-md-1">
                            <button type="button" class="btn btn-outline-danger w-100" @click="removeItem(index)" :disabled="items.length <= 1">
                              <i class="bi bi-trash"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                    </template>
                  </div>

                  <button type="button" class="btn btn-outline-primary mb-3" @click="addItem()">
                    <i class="bi bi-plus-circle me-2"></i>Adicionar Item
                  </button>

                  {{-- Total --}}
                  <div class="text-end mt-4 border-top pt-3">
                    <h5 class="text-primary">
                      <strong>Total Geral: <span x-text="formatCurrency(total)"></span></strong>
                    </h5>
                  </div>

                  {{-- Campos ocultos para itens (gerados dinamicamente) --}}
                  <template x-for="(item, index) in items" :key="index">
                    <input type="hidden" :name="'items[' + index + '][description]'" :value="item.description">
                    <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.quantity">
                    <input type="hidden" :name="'items[' + index + '][unit_price]'" :value="item.unit_price">
                    <input type="hidden" :name="'items[' + index + '][total]'" :value="(item.quantity * item.unit_price)">
                  </template>
                  <input type="hidden" name="total_value" :value="total">
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Botões de ação (únicos) --}}
        </div>

        <div class="d-flex justify-content-between mt-4 pt-4 border-top">
          <a href="{{ route( 'budgets.index' ) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Voltar
          </a>

          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Criar Orçamento
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


<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
  document.addEventListener('alpine:init', () => {
    Alpine.data('budgetItems', () => ({
      items: @json(old('items', [{ description: '', quantity: 1, unit_price: 0 }])),

      addItem() {
        this.items.push({
          description: '',
          quantity: 1,
          unit_price: 0
        });
      },

      removeItem(index) {
        if (this.items.length > 1) {
          this.items.splice(index, 1);
        }
      },

      get total() {
        return this.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
      },

      formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
          style: 'currency',
          currency: 'BRL'
        }).format(value);
      }
    }));
  });
</script>