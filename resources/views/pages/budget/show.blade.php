@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-file-earmark-text me-2"></i>
                Orçamento {{ $budget->code }}
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
                            <span class="badge bg-secondary">{{ $budget->status->value }}</span>
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
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Desconto</h6>
                                        <h4 class="text-warning">R$ {{ number_format($budget->discount, 2, ',', '.') }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Total</h6>
                                        <h4 class="text-success">R$ {{ number_format($budget->total, 2, ',', '.') }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Total Final</h6>
                                        <h4 class="text-primary">R$ {{ number_format($budget->total - $budget->discount, 2, ',', '.') }}</h4>
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
                        <button class="btn btn-outline-info px-4">
                            <i class="bi bi-printer me-2"></i>Imprimir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
