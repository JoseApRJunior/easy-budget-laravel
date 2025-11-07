@extends( 'layouts.app' )

@section( 'title', 'Orçamento ' . $budget->code )

@section( 'content' )
  <div class="container py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0">Orçamento {{ $budget->code }}</h1>
        <p class="text-muted mb-0">
          Status:
          <span
            class="badge bg-{{ $budget->status->value === 'approved' ? 'success' : ( $budget->status->value === 'rejected' ? 'danger' : 'warning' ) }}">
            {{ $budget->status->label() ?? ucfirst( $budget->status->value ) }}
          </span>
        </p>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route( 'budgets.print', $budget->code ) }}" class="btn btn-outline-primary" target="_blank"
          title="Download PDF">
          <i class="bi bi-file-pdf me-2"></i>PDF
        </a>
        @if( $budget->status->canEdit() )
          <a href="{{ route( 'budgets.edit', $budget->code ) }}" class="btn btn-primary" title="Editar orçamento">
            <i class="bi bi-pencil me-2"></i>Editar
          </a>
        @endif
      </div>
    </div>

    <div class="row">
      <!-- Customer Information -->
      <div class="col-lg-6 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0">
              <i class="bi bi-person me-2"></i>Cliente
            </h5>
          </div>
          <div class="card-body">
            <h6 class="mb-2">{{ $budget->customer->name }}</h6>
            <p class="text-muted mb-1">
              <i class="bi bi-envelope me-1"></i>{{ $budget->customer->email }}
            </p>
            <p class="text-muted mb-0">
              <i class="bi bi-telephone me-1"></i>{{ $budget->customer->phone ?? 'Não informado' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Budget Details -->
      <div class="col-lg-6 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0">
              <i class="bi bi-info-circle me-2"></i>Detalhes
            </h5>
          </div>
          <div class="card-body">
            <p class="mb-2">
              <strong>Descrição:</strong>
              {{ $budget->description ?? 'Sem descrição' }}
            </p>
            <p class="mb-2">
              <strong>Data de Vencimento:</strong>
              {{ $budget->due_date ? $budget->due_date->format( 'd/m/Y' ) : 'Não definida' }}
            </p>
            <p class="mb-0">
              <strong>Criado em:</strong>
              {{ $budget->created_at->format( 'd/m/Y H:i' ) }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Budget Items Table -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="bi bi-list-check me-2"></i>Itens do Orçamento
        </h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Descrição</th>
                <th class="text-center">Qtd</th>
                <th class="text-end">Valor Unit.</th>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              @forelse( $budget->items as $item )
                <tr>
                  <td>{{ $item->description }}</td>
                  <td class="text-center">{{ $item->quantity }}</td>
                  <td class="text-end">R$ {{ number_format( $item->unit_price, 2, ',', '.' ) }}</td>
                  <td class="text-end">R$ {{ number_format( $item->total_price, 2, ',', '.' ) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">
                    <i class="bi bi-inbox me-2"></i>Nenhum item adicionado
                  </td>
                </tr>
              @endforelse
            </tbody>
            @if( $budget->items->count() > 0 )
              <tfoot class="table-light">
                <tr>
                  <th colspan="3" class="text-end fw-bold">Total Geral:</th>
                  <th class="text-end fw-bold">R$ {{ number_format( $budget->total_amount, 2, ',', '.' ) }}</th>
                </tr>
              </tfoot>
            @endif
          </table>
        </div>
      </div>
    </div>

    <!-- Status Actions (only for pending budgets) -->
    @if( $budget->status->value === 'pending' )
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="bi bi-gear me-2"></i>Ações
          </h5>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route( 'budgets.change-status', $budget->code ) }}" class="d-inline me-2">
            @csrf
            <input type="hidden" name="status" value="approved">
            <button type="submit" class="btn btn-success"
              onclick="return confirm('Tem certeza que deseja aprovar este orçamento?')">
              <i class="bi bi-check-lg me-2"></i>Aprovar
            </button>
          </form>

          <form method="POST" action="{{ route( 'budgets.change-status', $budget->code ) }}" class="d-inline">
            @csrf
            <input type="hidden" name="status" value="rejected">
            <button type="submit" class="btn btn-danger"
              onclick="return confirm('Tem certeza que deseja rejeitar este orçamento?')">
              <i class="bi bi-x-lg me-2"></i>Rejeitar
            </button>
          </form>
        </div>
      </div>
    @endif
  </div>
@endsection
