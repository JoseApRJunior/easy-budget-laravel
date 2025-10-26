@extends( 'layouts.app' )

@section( 'content' )
  <div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 mb-0">
        <i class="bi bi-file-earmark-text me-2"></i>Detalhes do Orçamento
      </h1>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="/">Início</a></li>
          <li class="breadcrumb-item active">{{ $budget->code }}</li>
        </ol>
      </nav>
    </div>

    <!-- Success/Error Messages -->
    @if( session( 'success' ) )
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session( 'success' ) }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if( session( 'error' ) )
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ session( 'error' ) }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="row g-4 mb-4">
      <!-- Main Details -->
      <div class="col-md-8">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4">
            <!-- Budget Info -->
            <div class="row g-4 mb-4">
              <div class="col-md-4">
                <small class="text-muted">Código</small>
                <h5 class="fw-semibold mb-0">{{ $budget->code }}</h5>
              </div>
              <div class="col-md-6">
                <small class="text-muted">Cliente</small>
                <h5 class="mb-0">{{ $budget->customer->first_name }} {{ $budget->customer->last_name }}</h5>
              </div>
              <div class="col-md-2">
                <small class="text-muted">Status</small>
                <span class="badge fs-6"
                  style="background-color: {{ $budget->budgetStatus->color }};">{{ $budget->budgetStatus->name }}</span>
              </div>
            </div>

            <!-- Description -->
            <div class="mb-4">
              <small class="text-muted">Descrição</small>
              <p class="lead mb-0">{{ $budget->description }}</p>
            </div>

            <!-- Additional Details -->
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-transparent border-0">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Detalhes Adicionais</h6>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <small class="text-muted">Criado em:</small>
                    {{ $budget->created_at ? $budget->created_at->format( 'd/m/Y H:i' ) : 'Não informado' }}
                  </div>
                  <div class="col-md-6">
                    <small class="text-muted">Atualizado em:</small>
                    {{ $budget->updated_at ? $budget->updated_at->format( 'd/m/Y H:i' ) : 'Não informado' }}
                  </div>
                  @if( $budget->payment_terms )
                    <div class="col-12">
                      <small class="text-muted">Condições de Pagamento:</small>
                      {{ $budget->payment_terms }}
                    </div>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Financial Summary -->
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-transparent">
            <h5 class="card-title mb-0"><i class="bi bi-currency-dollar me-2"></i>Resumo Financeiro</h5>
          </div>
          <div class="card-body">
            @php
              $cancelled_total = $budget->services->where( 'status.slug', 'CANCELLED' )->sum( 'total' );
              $total_discount  = $budget->discount + $budget->services->sum( 'discount' );
              $real_total      = $budget->total - $cancelled_total - $total_discount;
            @endphp
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between">
                Total Bruto:
                <span class="fw-semibold">R$ {{ number_format( $budget->total, 2, ',', '.' ) }}</span>
              </li>
              @if( $cancelled_total > 0 )
                <li class="list-group-item d-flex justify-content-between text-danger">
                  Cancelados:
                  <span>- R$ {{ number_format( $cancelled_total, 2, ',', '.' ) }}</span>
                </li>
              @endif
              <li class="list-group-item d-flex justify-content-between text-danger">
                Descontos:
                <span>- R$ {{ number_format( $total_discount, 2, ',', '.' ) }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between h5 mb-0">
                <strong>Total:</strong>
                <strong class="text-success">R$ {{ number_format( $real_total, 2, ',', '.' ) }}</strong>
              </li>
            </ul>
            <hr>
            <div class="d-flex justify-content-between mb-3">
              <span class="text-muted">Vencimento:</span>
              <span class="fw-semibold @if( $budget->due_date && $budget->due_date->isPast() ) text-danger @endif">
                {{ $budget->due_date ? $budget->due_date->format( 'd/m/Y' ) : 'Não informado' }}
              </span>
            </div>

            <!-- Action Buttons for SENT status -->
            @if( $budget->budgetStatus->slug === 'sent' )
              <div class="d-grid gap-2 mt-4">
                <form action="{{ route( 'budgets.public.choose-status.store' ) }}" method="POST">
                  @csrf
                  <input type="hidden" name="budget_code" value="{{ $budget->code }}">
                  <input type="hidden" name="token" value="{{ $token }}">

                  <div class="mb-3">
                    <label for="budget_status_id" class="form-label">Escolha uma ação:</label>
                    <select name="budget_status_id" class="form-select" required>
                      <option value="">Selecione uma opção...</option>
                      @foreach( $availableStatuses as $status )
                        <option value="{{ $status->value }}">
                          {{ $status->getName() }}
                        </option>
                      @endforeach
                    </select>
                  </div>

                  <button type="submit" class="btn btn-success btn-lg w-100">
                    <i class="bi bi-check-circle-fill me-2"></i>Confirmar
                  </button>
                </form>
              </div>
            @else
              <div class="alert alert-info text-center">
                Este orçamento já foi {{ strtolower( $budget->budgetStatus->name ) }}.
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Linked Services -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-transparent p-4">
        <h4 class="mb-0"><i class="bi bi-tools me-2"></i>Serviços Vinculados</h4>
      </div>
      <div class="card-body p-4">
        @forelse( $budget->services as $service )
          <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header p-3 d-flex justify-content-between align-items-center">
              <h5 class="mb-0"><i class="bi bi-tag me-2"></i>{{ $service->category->name }}</h5>
              <span class="badge"
                style="background-color: {{ $service->status->color }};">{{ $service->status->name }}</span>
            </div>
            <div class="card-body p-4">
              <div class="row g-4">
                <div class="col-md-8">
                  <p class="mb-1"><strong>Descrição:</strong> {{ $service->description }}</p>
                  <p class="mb-0">
                    <small class="text-muted">Vencimento:
                      {{ $service->due_date ? $service->due_date->format( 'd/m/Y' ) : 'Não informado' }}</small>
                  </p>
                </div>
                <div class="col-md-4">
                  <ul class="list-group list-group-flush text-end">
                    <li class="list-group-item">
                      Total: <span class="fw-bold text-success">R$
                        {{ number_format( $service->total, 2, ',', '.' ) }}</span>
                    </li>
                    @if( $service->discount > 0 )
                      <li class="list-group-item">
                        Desconto: <span class="fw-bold text-danger">- R$
                          {{ number_format( $service->discount, 2, ',', '.' ) }}</span>
                      </li>
                      <li class="list-group-item">
                        Subtotal: <span class="fw-bold">R$
                          {{ number_format( $service->total - $service->discount, 2, ',', '.' ) }}</span>
                      </li>
                    @endif
                  </ul>
                </div>
              </div>
            </div>
          </div>
        @empty
          <p class="text-center">Nenhum serviço vinculado.</p>
        @endforelse
      </div>
    </div>
  </div>
@endsection
