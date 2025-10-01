@extends( 'layouts.app' )

@section( 'title', 'Detalhes do Orçamento' )

@section( 'content' )
    <div class="budget-show">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item">
                            <a href="{{ route( 'budgets.index' ) }}">Orçamentos</a>
                        </li>
                        <li class="breadcrumb-item active">{{ $budget->code }}</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0">{{ $budget->code }}</h1>
                <p class="text-muted mb-0">{{ $budget->description ?? 'Sem descrição' }}</p>
            </div>
            <div class="d-flex gap-2">
                @if( $budget->canBeEdited() )
                    <a href="{{ route( 'budgets.edit', $budget ) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-2"></i>Editar
                    </a>
                @endif
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical me-2"></i>Ações
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{ route( 'budgets.generate-pdf', $budget ) }}" target="_blank">
                            <i class="bi bi-file-earmark-pdf me-2"></i>Gerar PDF
                        </a>
                        @if( $budget->canBeSent() )
                            <a class="dropdown-item" href="#" onclick="sendBudget()">
                                <i class="bi bi-send me-2"></i>Enviar para Cliente
                            </a>
                        @endif
                        <a class="dropdown-item" href="{{ route( 'budgets.duplicate', $budget ) }}">
                            <i class="bi bi-copy me-2"></i>Duplicar
                        </a>
                        @if( $budget->versions->count() > 1 )
                            <a class="dropdown-item" href="{{ route( 'budgets.versions', $budget ) }}">
                                <i class="bi bi-clock-history me-2"></i>Ver Versões
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Informações do Orçamento -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>Informações do Orçamento
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar avatar-sm me-3">
                                        <div class="avatar-initial bg-primary rounded-circle">
                                            {{ substr( $budget->customer->name ?? 'N', 0, 1 ) }}
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $budget->customer->name ?? 'Cliente não identificado' }}</h6>
                                        <small class="text-muted">Cliente</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-end">
                                    @php
                                        $statusColors = [
                                            'rascunho'  => 'warning',
                                            'enviado'   => 'info',
                                            'aprovado'  => 'success',
                                            'rejeitado' => 'danger',
                                            'expirado'  => 'secondary'
                                        ];
                                        $statusColor  = $statusColors[ $budget->budgetStatus->slug ?? 'rascunho' ] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $statusColor }} fs-6">
                                        <i class="bi bi-circle-fill me-1"></i>
                                        {{ $budget->budgetStatus->name ?? 'Indefinido' }}
                                    </span>
                                    @if( $budget->isExpired() )
                                        <br><small class="text-danger">Expirado</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if( $budget->valid_until )
                            <div class="alert alert-info">
                                <i class="bi bi-calendar-event me-2"></i>
                                <strong>Válido até:</strong> {{ $budget->valid_until->format( 'd/m/Y' ) }}
                                @if( $budget->isExpired() )
                                    <span class="text-danger">(Expirado)</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Itens do Orçamento -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-list-ul me-2"></i>Itens do Orçamento
                        </h5>
                        <span class="badge bg-primary">{{ $budget->items->count() }} itens</span>
                    </div>
                    <div class="card-body">
                        @forelse( $budget->items as $item )
                            <div class="budget-item-card border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">{{ $item->title }}</h6>
                                        @if( $item->description )
                                            <p class="text-muted mb-2">{{ $item->description }}</p>
                                        @endif
                                        @if( $item->category )
                                            <span class="badge bg-light text-dark me-2">
                                                {{ $item->category->name }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold">{{ $item->formattedNetTotal }}</div>
                                        <small class="text-muted">
                                            {{ $item->quantity }} × {{ $item->formattedUnitPrice }}
                                        </small>
                                    </div>
                                </div>

                                @if( $item->discount_percentage > 0 || $item->tax_percentage > 0 )
                                    <div class="row g-2 mt-2 pt-2 border-top">
                                        @if( $item->discount_percentage > 0 )
                                            <div class="col-md-6">
                                                <small class="text-success">
                                                    <i class="bi bi-dash-circle me-1"></i>
                                                    Desconto: {{ $item->discount_percentage }}%
                                                </small>
                                            </div>
                                        @endif
                                        @if( $item->tax_percentage > 0 )
                                            <div class="col-md-6 text-end">
                                                <small class="text-info">
                                                    <i class="bi bi-plus-circle me-1"></i>
                                                    Imposto: {{ $item->tax_percentage }}%
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="bi bi-list-ul fs-1 text-muted mb-3"></i>
                                <p class="text-muted mb-0">Nenhum item adicionado ao orçamento.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Resumo e Ações -->
            <div class="col-lg-4">
                <!-- Resumo Financeiro -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-calculator me-2"></i>Resumo Financeiro
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span class="fw-bold">{{ number_format( $totals[ 'subtotal' ], 2, ',', '.' ) }}</span>
                        </div>
                        @if( $totals[ 'discount_total' ] > 0 )
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Descontos:</span>
                                <span>-{{ number_format( $totals[ 'discount_total' ], 2, ',', '.' ) }}</span>
                            </div>
                        @endif
                        @if( $totals[ 'taxes_total' ] > 0 )
                            <div class="d-flex justify-content-between mb-2 text-info">
                                <span>Impostos:</span>
                                <span>{{ number_format( $totals[ 'taxes_total' ], 2, ',', '.' ) }}</span>
                            </div>
                        @endif
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="h5 mb-0">Total:</span>
                            <span
                                class="h4 mb-0 text-primary">{{ number_format( $totals[ 'grand_total' ], 2, ',', '.' ) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Histórico de Ações -->
                @if( $budget->actionHistory->count() > 0 )
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-clock-history me-2"></i>Histórico
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="timeline">
                                @foreach( $budget->actionHistory->take( 5 ) as $action )
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-primary"></div>
                                        <div class="timeline-content">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <p class="mb-1">{{ $action->formattedInfo[ 'action_label' ] }}</p>
                                                    <small class="text-muted">{{ $action->formattedInfo[ 'date' ] }}</small>
                                                </div>
                                                @if( $action->new_status )
                                                    <span class="badge bg-light text-dark">{{ $action->new_status }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push( 'styles' )
        <style>
            .timeline {
                position: relative;
                padding: 1rem 0;
            }

            .timeline-item {
                position: relative;
                padding-left: 2rem;
                margin-bottom: 1rem;
            }

            .timeline-item:not(:last-child)::before {
                content: '';
                position: absolute;
                left: 0.75rem;
                top: 2rem;
                bottom: -1rem;
                width: 2px;
                background: #e9ecef;
            }

            .timeline-marker {
                position: absolute;
                left: 0;
                top: 0.25rem;
                width: 1rem;
                height: 1rem;
                border-radius: 50%;
                border: 3px solid #fff;
            }

            .budget-item-card:hover {
                background-color: #f8f9fa;
            }
        </style>
    @endpush

    @push( 'scripts' )
        <script>
            function sendBudget() {
                if ( confirm( 'Deseja enviar este orçamento para o cliente?' ) ) {
                    const form = document.createElement( 'form' );
                    form.method = 'POST';
                    form.action = '{{ route( "budgets.send", $budget ) }}';

                    const csrfInput = document.createElement( 'input' );
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';

                    form.appendChild( csrfInput );
                    document.body.appendChild( form );
                    form.submit();
                }
            }
        </script>
    @endpush
@endsection
