@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            @php
                $statusColor = $budget->status->getColor();
                $statusIcon  = $budget->status->getIcon();
                $statusLabel = $budget->status->label();
            @endphp
            <h1 class="h3 mb-0 d-flex align-items-center gap-3">
                <span class="d-flex align-items-center">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    Orçamento {{ $budget->code }}
                </span>
                <span class="badge" style="background-color: {{ $statusColor }}">
                    <i class="bi {{ $statusIcon }} me-1"></i>{{ $statusLabel }}
                </span>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.budgets.index' ) }}">Orçamentos</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $budget->code }}</li>
                </ol>
            </nav>
        </div>

        <!-- Budget Details Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- Basic Info -->
                    <div class="col-md-6">
                        <h5 class="fw-bold mb-3">Informações Básicas</h5>
                        <div class="mb-3">
                            <strong>Código:</strong> {{ $budget->code }}
                        </div>
                        <div class="mb-3">
                            <strong>Status:</strong>
                            <span class="badge" style="background-color: {{ $statusColor }}">{{ $statusLabel }}</span>
                        </div>
                        <div class="mb-3">
                            <strong>Data de Criação:</strong> {{ $budget->created_at->format('d/m/Y H:i') }}
                        </div>
                        @if($budget->due_date)
                        <div class="mb-3">
                            <strong>Data de Vencimento:</strong> {{ $budget->due_date->format('d/m/Y') }}
                        </div>
                        @endif
                    </div>

                    <!-- Customer Info -->
                    <div class="col-md-6">
                        <h5 class="fw-bold mb-3">Cliente</h5>
                        @if($budget->customer && $budget->customer->commonData)
                        <div class="mb-3">
                            <strong>Nome:</strong>
                            @if($budget->customer->commonData->company_name)
                                {{ $budget->customer->commonData->company_name }}
                            @else
                                {{ $budget->customer->commonData->first_name }} {{ $budget->customer->commonData->last_name }}
                            @endif
                        </div>
                        @if($budget->customer->commonData->cnpj)
                        <div class="mb-3">
                            <strong>CNPJ:</strong> {{ $budget->customer->commonData->cnpj }}
                        </div>
                        @elseif($budget->customer->commonData->cpf)
                        <div class="mb-3">
                            <strong>CPF:</strong> {{ $budget->customer->commonData->cpf }}
                        </div>
                        @endif
                        @endif

                        @if($budget->customer && $budget->customer->contact)
                        @if($budget->customer->contact->email_personal)
                        <div class="mb-3">
                            <strong>Email:</strong> {{ $budget->customer->contact->email_personal }}
                        </div>
                        @endif
                        @if($budget->customer->contact->phone_personal)
                        <div class="mb-3">
                            <strong>Telefone:</strong> {{ $budget->customer->contact->phone_personal }}
                        </div>
                        @endif
                        @endif
                    </div>

                    <!-- Description -->
                    @if($budget->description)
                    <div class="col-12">
                        <h5 class="fw-bold mb-3">Descrição</h5>
                        <p class="text-muted">{{ $budget->description }}</p>
                    </div>
                    @endif

                    <!-- Payment Terms -->
                    @if($budget->payment_terms)
                    <div class="col-12">
                        <h5 class="fw-bold mb-3">Condições de Pagamento</h5>
                        <p class="text-muted">{{ $budget->payment_terms }}</p>
                    </div>
                    @endif

                    <!-- Financial Summary -->
                    <div class="col-12">
                        <h5 class="fw-bold mb-3">Resumo Financeiro</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Desconto</h6>
                                        <h4 class="text-warning">R$ {{ number_format($budget->discount, 2, ',', '.') }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Total</h6>
                                        <h4 class="text-success">R$ {{ number_format($budget->total, 2, ',', '.') }}</h4>
                                    </div>
                                </div>
                            </div>
                            @php
                                $itemsGross = $budget->items?->sum('total_price') ?? 0;
                                $itemsNet   = $budget->items?->sum('net_total') ?? 0;
                            @endphp
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Itens (Bruto)</h6>
                                        <h4 class="text-dark">R$ {{ number_format($itemsGross, 2, ',', '.') }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Itens (Líquido)</h6>
                                        <h4 class="text-primary">R$ {{ number_format($itemsNet, 2, ',', '.') }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                    <a href="{{ route( 'provider.budgets.index' ) }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-arrow-left me-2"></i>Voltar
                    </a>
                    <div class="d-flex gap-2">
                        <a href="{{ route('provider.budgets.services.create', $budget->code) }}" class="btn btn-success px-4">
                            <i class="bi bi-tools me-2"></i>Criar Serviço
                        </a>
                        <a href="{{ route( 'provider.budgets.edit', $budget->code ) }}" class="btn btn-outline-primary px-4">
                            <i class="bi bi-pencil me-2"></i>Editar
                        </a>
                        <a href="{{ route('provider.budgets.print', $budget->code) }}" class="btn btn-outline-info px-4" target="_blank">
                            <i class="bi bi-printer me-2"></i>Imprimir
                        </a>
                        <a href="{{ route('provider.budgets.print', ['budget' => $budget->code, 'pdf' => true]) }}" class="btn btn-outline-danger px-4" target="_blank">
                            <i class="bi bi-file-earmark-pdf me-2"></i>Visualizar PDF
                        </a>
                        <a href="{{ route('provider.budgets.print', ['budget' => $budget->code, 'pdf' => true, 'download' => true]) }}" class="btn btn-danger px-4">
                            <i class="bi bi-download me-2"></i>Baixar PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Itens do Orçamento -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-transparent border-0">
                <h5 class="mb-0">
                    <i class="bi bi-list-check me-2"></i>Itens do Orçamento
                </h5>
            </div>
            <div class="card-body p-0">
                @if($budget->items && $budget->items->count())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4">Título</th>
                                    <th>Descrição</th>
                                    <th class="text-end">Qtde</th>
                                    <th>Unidade</th>
                                    <th class="text-end">Preço Unit.</th>
                                    <th class="text-end">Desconto %</th>
                                    <th class="text-end">Impostos %</th>
                                    <th class="text-end">Subtotal</th>
                                    <th class="text-end px-4">Total Líquido</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($budget->items as $item)
                                    <tr>
                                        <td class="px-4">{{ $item->title }}</td>
                                        <td>{{ $item->description }}</td>
                                        <td class="text-end">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                                        <td>{{ $item->unit }}</td>
                                        <td class="text-end">R$ {{ $item->formatted_unit_price }}</td>
                                        <td class="text-end">{{ number_format($item->discount_percentage, 2, ',', '.') }}%</td>
                                        <td class="text-end">{{ number_format($item->tax_percentage, 2, ',', '.') }}%</td>
                                        <td class="text-end">R$ {{ $item->formatted_total }}</td>
                                        <td class="text-end px-4">R$ {{ $item->formatted_net_total }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox"></i> Nenhum item cadastrado neste orçamento
                    </div>
                @endif
            </div>
        </div>

        <!-- Serviços Vinculados -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-tools me-2"></i>Serviços Vinculados
                </h5>
                <a href="{{ route('provider.budgets.services.create', $budget->code) }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-plus-circle me-1"></i> Novo Serviço
                </a>
            </div>
            <div class="card-body p-0">
                @if($budget->services && $budget->services->count())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4">Código</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Status</th>
                                    <th>Vencimento</th>
                                    <th class="text-end">Desconto</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end px-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($budget->services as $service)
                                    <tr>
                                        <td class="px-4"><span class="text-code">{{ $service->code }}</span></td>
                                        <td>{{ $service->description }}</td>
                                        <td>{{ $service->category?->name }}</td>
                                        <td>
                                            <span class="badge" style="background-color: {{ $service->color }}">{{ $service->name }}</span>
                                        </td>
                                        <td>{{ $service->due_date ? \Carbon\Carbon::parse($service->due_date)->format('d/m/Y') : '-' }}</td>
                                        <td class="text-end">R$ {{ number_format($service->discount, 2, ',', '.') }}</td>
                                        <td class="text-end">R$ {{ number_format($service->total, 2, ',', '.') }}</td>
                                        <td class="text-end px-4">
                                            <div class="btn-group">
                                                <a href="{{ route('provider.services.show', $service->code) }}" class="btn btn-sm btn-outline-secondary" title="Ver Serviço">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('provider.services.edit', $service->code) }}" class="btn btn-sm btn-outline-primary" title="Editar Serviço">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox"></i> Nenhum serviço vinculado a este orçamento
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
