@extends( 'layouts.app' )

@section( 'content' )
  <div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 mb-0 text-gray-800">
        <i class="bi bi-receipt-cutoff me-2"></i>Fatura #{{ $invoice->code }}
      </h1>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="{{ route( 'provider.invoices.index' ) }}">Faturas</a></li>
          <li class="breadcrumb-item active">#{{ $invoice->code }}</li>
        </ol>
      </nav>
    </div>

    <!-- Ações -->
    <div class="d-flex gap-2 mb-4">
      <a href="{{ route( 'provider.invoices.index' ) }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Voltar à Lista
      </a>
      <a href="{{ route( 'provider.invoices.print', $invoice->code ) }}" class="btn btn-outline-secondary" target="_blank">
        <i class="bi bi-printer me-1"></i>Imprimir
      </a>
      @if( $invoice->status === 'pending' )
        <a href="{{ route( 'provider.invoices.edit', $invoice->code ) }}" class="btn btn-warning">
          <i class="bi bi-pencil me-1"></i>Editar
        </a>
      @endif
      <div class="dropdown">
        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown"
          aria-expanded="false">
          <i class="bi bi-three-dots me-1"></i>Mais Ações
        </button>
        <ul class="dropdown-menu">
          <li>
            <a class="dropdown-item" href="#" onclick="changeStatus('paid')">
              <i class="bi bi-check-circle text-success me-2"></i>Marcar como Paga
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="#" onclick="changeStatus('cancelled')">
              <i class="bi bi-x-circle text-danger me-2"></i>Cancelar Fatura
            </a>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>
          <li>
            <a class="dropdown-item" href="#" onclick="deleteInvoice()">
              <i class="bi bi-trash text-danger me-2"></i>Excluir Fatura
            </a>
          </li>
        </ul>
      </div>
    </div>

    <div class="row g-4">
      <!-- Informações Principais -->
      <div class="col-md-8">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4">
            <!-- Cabeçalho da Fatura -->
            <div class="d-flex justify-content-between align-items-start mb-4">
              <div>
                <h2 class="h4 mb-1">Fatura #{{ $invoice->code }}</h2>
                <p class="text-muted mb-0">
                  Gerada em {{ $invoice->created_at->format( 'd/m/Y H:i' ) }}
                </p>
              </div>
              @php
                $status     = $invoice->status;
                $badgeClass = match ( $status ) {
                  'pending'   => 'bg-warning',
                  'paid'      => 'bg-success',
                  'overdue'   => 'bg-danger',
                  'cancelled' => 'bg-secondary',
                  default     => 'bg-light text-dark'
                };
              @endphp
              <span class="badge {{ $badgeClass }} fs-6">
                {{ $status->name ?? ucfirst( $status ) }}
              </span>
            </div>

            <!-- Dados do Cliente e Empresa -->
            <div class="row mb-4">
              <div class="col-md-6">
                <h5 class="text-muted">Cliente</h5>
                <div class="bg-light p-3 rounded">
                  <strong>{{ $invoice->customer->name ?? 'N/A' }}</strong><br>
                  @if( $invoice->customer->email )
                    <i class="bi bi-envelope me-1"></i>{{ $invoice->customer->email }}<br>
                  @endif
                  @if( $invoice->customer->phone )
                    <i class="bi bi-telephone me-1"></i>{{ $invoice->customer->phone }}
                  @endif
                </div>
              </div>
              <div class="col-md-6">
                <h5 class="text-muted">Serviço</h5>
                <div class="bg-light p-3 rounded">
                  <strong>{{ $invoice->service->code ?? 'N/A' }}</strong><br>
                  <small class="text-muted">
                    {{ Str::limit( $invoice->service->description ?? '', 50 ) }}
                  </small>
                </div>
              </div>
            </div>

            <!-- Datas Importantes -->
            <div class="row mb-4">
              <div class="col-md-4">
                <h6 class="text-muted">Data de Emissão</h6>
                <p class="mb-0">{{ $invoice->issue_date?->format( 'd/m/Y' ) ?? 'N/A' }}</p>
              </div>
              <div class="col-md-4">
                <h6 class="text-muted">Data de Vencimento</h6>
                <p class="mb-0">
                  {{ $invoice->due_date?->format( 'd/m/Y' ) ?? 'N/A' }}
                  @if( $invoice->due_date )
                    @if( $invoice->due_date < now() )
                      <span class="badge bg-danger ms-2">Vencida</span>
                    @elseif( $invoice->due_date->diffInDays( now() ) <= 7 )
                      <span class="badge bg-warning ms-2">
                        Vence em {{ $invoice->due_date->diffInDays( now() ) }} dias
                      </span>
                    @endif
                  @endif
                </p>
              </div>
              <div class="col-md-4">
                <h6 class="text-muted">Valor Total</h6>
                <p class="mb-0 fs-5 text-success fw-bold">
                  R$ {{ number_format( $invoice->total_amount, 2, ',', '.' ) }}
                </p>
              </div>
            </div>

            <!-- Itens da Fatura -->
            @if( $invoice->invoiceItems->count() > 0 )
              <h5 class="mb-3">Itens da Fatura</h5>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Produto</th>
                      <th class="text-center">Qtd</th>
                      <th class="text-end">Valor Unit.</th>
                      <th class="text-end">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach( $invoice->invoiceItems as $item )
                      <tr>
                        <td>
                          <strong>{{ $item->product->name ?? 'N/A' }}</strong>
                          @if( $item->product->description )
                            <br><small class="text-muted">{{ $item->product->description }}</small>
                          @endif
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">R$ {{ number_format( $item->unit_value, 2, ',', '.' ) }}</td>
                        <td class="text-end fw-bold">R$ {{ number_format( $item->total, 2, ',', '.' ) }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                  <tfoot>
                    <tr class="table-light">
                      <th colspan="3" class="text-end">Subtotal:</th>
                      <th class="text-end">R$ {{ number_format( $invoice->invoiceItems->sum( 'total' ), 2, ',', '.' ) }}</th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            @else
              <div class="text-center py-4">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">Nenhum item encontrado nesta fatura</p>
              </div>
            @endif
          </div>
        </div>
      </div>

      <!-- Sidebar com Informações Extras -->
      <div class="col-md-4">
        <!-- Resumo Financeiro -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-primary text-white">
            <h6 class="mb-0">
              <i class="bi bi-calculator me-2"></i>Resumo Financeiro
            </h6>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
              <span>Subtotal:</span>
              <span>R$ {{ number_format( $invoice->invoiceItems->sum( 'total' ), 2, ',', '.' ) }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Desconto:</span>
              <span>R$ 0,00</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between fw-bold">
              <span>Total:</span>
              <span class="text-success">R$ {{ number_format( $invoice->total_amount, 2, ',', '.' ) }}</span>
            </div>
          </div>
        </div>

        <!-- Status Detalhado -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-info text-white">
            <h6 class="mb-0">
              <i class="bi bi-info-circle me-2"></i>Status Detalhado
            </h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <span class="badge {{ $badgeClass }} fs-6 w-100 py-2">
                {{ $status->name ?? ucfirst( $status ) }}
              </span>
            </div>

            @if( $invoice->due_date )
              @if( $invoice->status === 'pending' )
                @if( $invoice->due_date < now() )
                  <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Fatura vencida há {{ $invoice->due_date->diffInDays( now() ) }} dias
                  </div>
                @else
                  <div class="alert alert-warning">
                    <i class="bi bi-clock me-2"></i>
                    Vence em {{ $invoice->due_date->diffInDays( now() ) }} dias
                  </div>
                @endif
              @elseif( $invoice->status === 'paid' )
                <div class="alert alert-success">
                  <i class="bi bi-check-circle me-2"></i>
                  Fatura paga com sucesso
                </div>
              @endif
            @endif
          </div>
        </div>

        <!-- Histórico de Ações -->
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-secondary text-white">
            <h6 class="mb-0">
              <i class="bi bi-clock-history me-2"></i>Histórico
            </h6>
          </div>
          <div class="card-body">
            <div class="timeline">
              <div class="timeline-item">
                <div class="timeline-marker bg-primary"></div>
                <div class="timeline-content">
                  <h6 class="timeline-title">Fatura Criada</h6>
                  <p class="timeline-description text-muted">
                    {{ $invoice->created_at->format( 'd/m/Y H:i' ) }}
                  </p>
                </div>
              </div>
              @if( $invoice->updated_at->ne( $invoice->created_at ) )
                <div class="timeline-item">
                  <div class="timeline-marker bg-info"></div>
                  <div class="timeline-content">
                    <h6 class="timeline-title">Última Atualização</h6>
                    <p class="timeline-description text-muted">
                      {{ $invoice->updated_at->format( 'd/m/Y H:i' ) }}
                    </p>
                  </div>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para mudança de status -->
  <div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Alterar Status da Fatura</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Tem certeza que deseja alterar o status desta fatura?</p>
          <p><strong>Fatura:</strong> #{{ $invoice->code }}</p>
          <p><strong>Status atual:</strong> <span
              class="badge {{ $badgeClass }}">{{ $status->name ?? ucfirst( $status ) }}</span></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="confirmStatusChange">Confirmar</button>
        </div>
      </div>
    </div>
  </div>

  <style>
    .timeline {
      position: relative;
      padding-left: 20px;
    }

    .timeline-item {
      position: relative;
      margin-bottom: 20px;
    }

    .timeline-marker {
      position: absolute;
      left: -20px;
      top: 0;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background-color: #007bff;
    }

    .timeline-content {
      margin-left: 10px;
    }

    .timeline-title {
      font-size: 0.9rem;
      margin-bottom: 2px;
    }

    .timeline-description {
      font-size: 0.8rem;
      margin-bottom: 0;
    }
  </style>

  <script>
    let newStatus = '';

    // Função para alterar status
    function changeStatus( status ) {
      newStatus = status;
      const statusModal = new bootstrap.Modal( document.getElementById( 'statusModal' ) );
      statusModal.show();
    }

    // Confirmação de mudança de status
    document.getElementById( 'confirmStatusChange' ).addEventListener( 'click', function () {
      if ( !newStatus ) return;

      const form = document.createElement( 'form' );
      form.method = 'POST';
      form.action = '{{ route( "provider.invoices.change_status", $invoice->code ) }}';

      const csrfToken = document.createElement( 'input' );
      csrfToken.type = 'hidden';
      csrfToken.name = '_token';
      csrfToken.value = '{{ csrf_token() }}';

      const methodField = document.createElement( 'input' );
      methodField.type = 'hidden';
      methodField.name = '_method';
      methodField.value = 'PUT';

      const statusField = document.createElement( 'input' );
      statusField.type = 'hidden';
      statusField.name = 'status';
      statusField.value = newStatus;

      form.appendChild( csrfToken );
      form.appendChild( methodField );
      form.appendChild( statusField );

      document.body.appendChild( form );
      form.submit();
    } );

    // Função para excluir fatura
    function deleteInvoice() {
      if ( confirm( 'Tem certeza que deseja excluir esta fatura? Esta ação não pode ser desfeita.' ) ) {
        const form = document.createElement( 'form' );
        form.method = 'POST';
        form.action = '{{ route( "provider.invoices.destroy", $invoice->code ) }}';

        const csrfToken = document.createElement( 'input' );
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        const methodField = document.createElement( 'input' );
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';

        form.appendChild( csrfToken );
        form.appendChild( methodField );

        document.body.appendChild( form );
        form.submit();
      }
    }
  </script>
@endsection
