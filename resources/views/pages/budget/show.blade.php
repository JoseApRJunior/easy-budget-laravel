@extends('layouts.app')

@section('title', 'Detalhes do Orçamento')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-file-earmark-text me-2"></i>Detalhes do Orçamento
                </h1>
                <p class="text-muted mb-0">Visualize as informações completas do orçamento</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('provider.budgets.index') }}">Orçamentos</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $budget->code }}</li>
                </ol>
            </nav>
        </div>

        <div class="row g-4">
            <!-- Informações Básicas -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 py-1">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="bi bi-info-circle me-2"></i>
                            <span>Informações Básicas</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="info-item mb-3">
                            <label class="text-muted small d-block mb-1">Código</label>
                            <p class="mb-0 fw-bold">{{ $budget->code }}</p>
                        </div>

                        <div class="info-item mb-3">
                            <label class="text-muted small d-block mb-1">Status</label>
                            <p class="mb-0">
                                @php
                                    $statusColor = $budget->status->getColor();
                                    $statusIcon = $budget->status->getIcon();
                                    $statusLabel = $budget->status->label();
                                @endphp
                                <span class="badge" style="background-color: {{ $statusColor }}">
                                    <i class="bi {{ $statusIcon }} me-1"></i>{{ $statusLabel }}
                                </span>
                            </p>
                        </div>

                        <div class="info-item mb-3">
                            <label class="text-muted small d-block mb-1">Data de Criação</label>
                            <p class="mb-0 d-flex align-items-center">
                                <i class="bi bi-calendar-plus me-2"></i>
                                <span>{{ $budget->created_at->format('d/m/Y H:i') }}</span>
                            </p>
                        </div>

                        @if ($budget->due_date)
                            <div class="info-item mb-3">
                                <label class="text-muted small d-block mb-1">Data de Vencimento</label>
                                <p class="mb-0 d-flex align-items-center">
                                    <i class="bi bi-calendar-event me-2"></i>
                                    <span>{{ $budget->due_date->format('d/m/Y') }}</span>
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Informações do Cliente -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 py-1">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="bi bi-person me-2"></i>
                            <span>Cliente</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        @if ($budget->customer && $budget->customer->commonData)
                            <div class="info-item mb-3">
                                <label class="text-muted small d-block mb-1">Nome</label>
                                <p class="mb-0 fw-bold">
                                    @if ($budget->customer->commonData->company_name)
                                        {{ $budget->customer->commonData->company_name }}
                                    @else
                                        {{ $budget->customer->commonData->first_name }}
                                        {{ $budget->customer->commonData->last_name }}
                                    @endif
                                </p>
                            </div>

                            @if ($budget->customer->commonData->cnpj)
                                <div class="info-item mb-3">
                                    <label class="text-muted small d-block mb-1">CNPJ</label>
                                    <p class="mb-0">
                                        {{ \App\Helpers\DocumentHelper::formatCnpj($budget->customer->commonData->cnpj) }}
                                    </p>
                                </div>
                            @elseif($budget->customer->commonData->cpf)
                                <div class="info-item mb-3">
                                    <label class="text-muted small d-block mb-1">CPF</label>
                                    <p class="mb-0">
                                        {{ \App\Helpers\DocumentHelper::formatCpf($budget->customer->commonData->cpf) }}
                                    </p>
                                </div>
                            @endif

                            @if ($budget->customer->contact)
                                @if ($budget->customer->contact->email_personal)
                                    <div class="info-item mb-3">
                                        <label class="text-muted small d-block mb-1">Email</label>
                                        <p class="mb-0 d-flex align-items-center">
                                            <i class="bi bi-envelope me-2"></i>
                                            <a href="mailto:{{ $budget->customer->contact->email_personal }}"
                                                class="text-decoration-none">
                                                {{ $budget->customer->contact->email_personal }}
                                            </a>
                                        </p>
                                    </div>
                                @endif

                                @if ($budget->customer->contact->phone_personal)
                                    <div class="info-item mb-3">
                                        <label class="text-muted small d-block mb-1">Telefone</label>
                                        <p class="mb-0 d-flex align-items-center">
                                            <i class="bi bi-phone me-2"></i>
                                            <a href="tel:{{ preg_replace('/\D/', '', $budget->customer->contact->phone_personal) }}"
                                                class="text-decoration-none">
                                                {{ $budget->customer->contact->phone_personal }}
                                            </a>
                                        </p>
                                    </div>
                                @endif
                            @endif
                        @else
                            <p class="text-muted">Cliente não informado</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Resumo Financeiro -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 py-1">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="bi bi-cash-stack me-2"></i>
                            <span>Resumo Financeiro</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            $servicesSubtotal = $budget->services?->sum('total') ?? 0;
                        @endphp

                        <div class="info-item mb-3">
                            <label class="text-muted small d-block mb-1">Subtotal (Serviços)</label>
                            <h5 class="mb-0 text-primary">R$ {{ number_format($servicesSubtotal, 2, ',', '.') }}</h5>
                        </div>

                        <div class="info-item mb-3">
                            <label class="text-muted small d-block mb-1">Desconto</label>
                            <h5 class="mb-0 text-warning">R$ {{ number_format($budget->discount, 2, ',', '.') }}</h5>
                        </div>

                        <div class="info-item mb-3">
                            <label class="text-muted small d-block mb-1">Total</label>
                            <h4 class="mb-0 text-success">R$ {{ number_format($budget->total, 2, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Descrição -->
            @if ($budget->description)
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 py-1">
                            <h5 class="card-title mb-0 d-flex align-items-center">
                                <i class="bi bi-file-text me-2"></i>
                                <span>Descrição</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">{{ $budget->description }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Condições de Pagamento -->
            @if ($budget->payment_terms)
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 py-1">
                            <h5 class="card-title mb-0 d-flex align-items-center">
                                <i class="bi bi-credit-card me-2"></i>
                                <span>Condições de Pagamento</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">{{ $budget->payment_terms }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Serviços Vinculados -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <div class="row align-items-center">
                            <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                                <h5 class="mb-0 d-flex align-items-center flex-wrap">
                                    <span class="me-2">
                                        <i class="bi bi-tools me-1"></i>
                                        <span class="d-none d-sm-inline">Serviços Vinculados</span>
                                        <span class="d-sm-none">Serviços</span>
                                    </span>
                                    <span class="text-muted" style="font-size: 0.875rem;">
                                        ({{ $budget->services?->count() ?? 0 }})
                                    </span>
                                </h5>
                            </div>
                            <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                                <div class="d-flex justify-content-start justify-content-lg-end">
                                    <a href="{{ route('provider.budgets.services.create', $budget->code) }}"
                                        class="btn btn-success btn-sm">
                                        <i class="bi bi-plus"></i>
                                        <span class="ms-1">Novo</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if ($budget->services && $budget->services->count())
                            <!-- Desktop View -->
                            <div class="desktop-view">
                                <div class="table-responsive">
                                    <table class="modern-table table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Descrição</th>
                                                <th>Categoria</th>
                                                <th>Status</th>
                                                <th>Vencimento</th>
                                                <th class="text-end">Desconto</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($budget->services as $service)
                                                <tr>
                                                    <td>{{ $service->code }}</td>
                                                    <td>{{ $service->description }}</td>
                                                    <td>{{ $service->category?->name ?? '-' }}</td>
                                                    <td>
                                                        <span class="badge"
                                                            style="background-color: {{ $service->color }}">
                                                            {{ $service->name }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $service->due_date ? \Carbon\Carbon::parse($service->due_date)->format('d/m/Y') : '-' }}
                                                    </td>
                                                    <td class="text-end">R$
                                                        {{ number_format($service->discount, 2, ',', '.') }}</td>
                                                    <td class="text-end">R$
                                                        {{ number_format($service->total, 2, ',', '.') }}</td>
                                                    <td class="text-center">
                                                        <div class="action-btn-group">
                                                            <a href="{{ route('provider.services.show', $service->code) }}"
                                                                class="action-btn action-btn-view" title="Visualizar">
                                                                <i class="bi bi-eye-fill"></i>
                                                            </a>
                                                            <a href="{{ route('provider.services.edit', $service->code) }}"
                                                                class="action-btn action-btn-edit" title="Editar">
                                                                <i class="bi bi-pencil-fill"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Mobile View -->
                            <div class="mobile-view">
                                <div class="list-group">
                                    @foreach ($budget->services as $service)
                                        <a href="{{ route('provider.services.show', $service->code) }}"
                                            class="list-group-item list-group-item-action py-3">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-tools text-muted me-2 mt-1"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold mb-2">{{ $service->code }}</div>
                                                    <div class="small text-muted mb-2">{{ $service->description }}</div>
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        <span class="badge"
                                                            style="background-color: {{ $service->color }}">
                                                            {{ $service->name }}
                                                        </span>
                                                        <span class="badge bg-success">R$
                                                            {{ number_format($service->total, 2, ',', '.') }}</span>
                                                    </div>
                                                </div>
                                                <i class="bi bi-chevron-right text-muted ms-2"></i>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                <br>
                                Nenhum serviço vinculado a este orçamento
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="d-flex gap-2">
                <a href="{{ url()->previous(route('provider.budgets.index')) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Voltar
                </a>
            </div>
            <small class="text-muted d-none d-md-block">
                Última atualização: {{ $budget->updated_at?->format('d/m/Y H:i') }}
            </small>
            <div class="d-flex gap-2">
                <a href="{{ route('provider.budgets.edit', $budget->code) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-fill me-2"></i>Editar
                </a>
                
                <!-- Desktop: Botões diretos -->
                <a href="{{ route('provider.budgets.print', $budget->code) }}" target="_blank"
                    class="btn btn-outline-secondary d-none d-md-inline-flex">
                    <i class="bi bi-printer me-2"></i>Imprimir
                </a>
                <a href="{{ route('provider.budgets.print', ['budget' => $budget->code, 'pdf' => true, 'download' => true]) }}"
                    class="btn btn-outline-secondary d-none d-md-inline-flex">
                    <i class="bi bi-download me-2"></i>PDF
                </a>
                
                <!-- Mobile: Dropdown -->
                <div class="btn-group d-md-none">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('provider.budgets.print', $budget->code) }}"
                                target="_blank">
                                <i class="bi bi-printer me-2"></i>Imprimir
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item"
                                href="{{ route('provider.budgets.print', ['budget' => $budget->code, 'pdf' => true, 'download' => true]) }}">
                                <i class="bi bi-download me-2"></i>Baixar PDF
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
