@extends('layouts.app')

@section('title', 'Detalhes do Orçamento')

@section('content')
<div class="container-fluid py-1">
    <x-page-header
        title="Detalhes do Orçamento"
        icon="file-earmark-text"
        :breadcrumb-items="[
            'Orçamentos' => route('provider.budgets.index'),
            $budget->code => '#'
        ]">
        <p class="text-muted mb-0">Visualize as informações completas do orçamento</p>
    </x-page-header>

    <div class="row g-4">
        <!-- Informações Básicas -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 py-3">
                    <h5 class="card-title mb-0 d-flex align-items-center text-dark fw-bold">
                        <div class="avatar avatar-xs bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                            <i class="bi bi-info-circle text-primary" style="font-size: 0.8rem;"></i>
                        </div>
                        <span>Informações Básicas</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-item mb-3">
                        <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Código</small>
                        <span class="text-dark fw-bold">{{ $budget->code }}</span>
                    </div>

                    <div class="info-item mb-3">
                        <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Status</small>
                        <div>
                            @php
                            $statusColor = $budget->status->getColor();
                            $statusIcon = $budget->status->getIcon();
                            $statusLabel = $budget->status->label();
                            @endphp
                            <span class="badge rounded-pill" style="background-color: {{ $statusColor }}20; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}40;">
                                <i class="bi {{ $statusIcon }} me-1"></i>{{ $statusLabel }}
                            </span>
                        </div>
                    </div>

                    <div class="info-item mb-3">
                        <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Data de Criação</small>
                        <span class="text-dark fw-bold">{{ $budget->created_at->format('d/m/Y H:i') }}</span>
                    </div>

                    @if ($budget->due_date)
                    <div class="info-item">
                        <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Data de Vencimento</small>
                        <span class="text-dark fw-bold">{{ $budget->due_date->format('d/m/Y') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Informações do Cliente -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 py-3">
                    <h5 class="card-title mb-0 d-flex align-items-center text-dark fw-bold">
                        <div class="avatar avatar-xs bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                            <i class="bi bi-person text-info" style="font-size: 0.8rem;"></i>
                        </div>
                        <span>Cliente</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if ($budget->customer && $budget->customer->commonData)
                    <div class="info-item mb-3">
                        <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Nome/Razão Social</small>
                        <span class="text-dark fw-bold">
                            @if ($budget->customer->commonData->company_name)
                            {{ $budget->customer->commonData->company_name }}
                            @else
                            {{ $budget->customer->commonData->first_name }}
                            {{ $budget->customer->commonData->last_name }}
                            @endif
                        </span>
                    </div>

                    @if ($budget->customer->commonData->cnpj)
                    <div class="info-item mb-3">
                        <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">CNPJ</small>
                        <span class="text-dark fw-bold">{{ \App\Helpers\DocumentHelper::formatCnpj($budget->customer->commonData->cnpj) }}</span>
                    </div>
                    @elseif($budget->customer->commonData->cpf)
                    <div class="info-item mb-3">
                        <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">CPF</small>
                        <span class="text-dark fw-bold">{{ \App\Helpers\DocumentHelper::formatCpf($budget->customer->commonData->cpf) }}</span>
                    </div>
                    @endif

                    @if ($budget->customer->contact)
                    @if ($budget->customer->contact->email_personal)
                    <div class="info-item mb-3">
                        <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">E-mail</small>
                        <span class="text-dark fw-bold text-break">{{ $budget->customer->contact->email_personal }}</span>
                    </div>
                    @endif

                    @if ($budget->customer->contact->phone_personal)
                    <div class="info-item">
                        <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Telefone</small>
                        <span class="text-dark fw-bold">{{ \App\Helpers\MaskHelper::formatPhone($budget->customer->contact->phone_personal) }}</span>
                    </div>
                    @endif
                    @endif
                    @else
                    <div class="text-center py-4">
                        <i class="bi bi-person-exclamation text-muted fs-2"></i>
                        <p class="text-muted mb-0 mt-2">Dados do cliente não encontrados.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Resumo Financeiro -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 py-3">
                    <h5 class="card-title mb-0 d-flex align-items-center text-dark fw-bold">
                        <div class="avatar avatar-xs bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                            <i class="bi bi-cash-stack text-success" style="font-size: 0.8rem;"></i>
                        </div>
                        <span>Resumo Financeiro</span>
                    </h5>
                </div>
                <div class="card-body">
                    @php
                    $servicesSubtotal = $budget->services?->sum('total') ?? 0;
                    @endphp

                    <div class="info-item mb-3">
                        <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Subtotal (Serviços)</small>
                        <h5 class="mb-0 text-dark fw-bold">R$ {{ number_format($servicesSubtotal, 2, ',', '.') }}</h5>
                    </div>

                    <div class="info-item mb-3">
                        <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Desconto</small>
                        <h5 class="mb-0 text-warning fw-bold">R$ {{ number_format($budget->discount, 2, ',', '.') }}</h5>
                    </div>

                    <div class="info-item">
                        <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Geral</small>
                        <h4 class="mb-0 text-success fw-bold">R$ {{ number_format($budget->total, 2, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Descrição -->
        @if ($budget->description)
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 py-3">
                    <h5 class="card-title mb-0 d-flex align-items-center text-dark fw-bold">
                        <div class="avatar avatar-xs bg-secondary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                            <i class="bi bi-file-text text-secondary" style="font-size: 0.8rem;"></i>
                        </div>
                        <span>Descrição</span>
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-dark">{{ $budget->description }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Condições de Pagamento -->
        @if ($budget->payment_terms)
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 py-3">
                    <h5 class="card-title mb-0 d-flex align-items-center text-dark fw-bold">
                        <div class="avatar avatar-xs bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                            <i class="bi bi-credit-card text-warning" style="font-size: 0.8rem;"></i>
                        </div>
                        <span>Condições de Pagamento</span>
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-dark">{{ $budget->payment_terms }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Serviços Vinculados -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 py-3">
                    <div class="row align-items-center">
                        <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                            <h5 class="mb-0 d-flex align-items-center flex-wrap text-dark fw-bold">
                                <div class="avatar avatar-xs bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                                    <i class="bi bi-tools text-primary" style="font-size: 0.8rem;"></i>
                                </div>
                                <span class="me-2">
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
                                <x-button type="link" :href="route('provider.budgets.services.create', $budget->code)"
                                    variant="success" size="sm" icon="plus" label="Novo" />
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
                                        <th class="text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: 0.5px;">Código</th>
                                        <th class="text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: 0.5px;">Descrição</th>
                                        <th class="text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: 0.5px;">Categoria</th>
                                        <th class="text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: 0.5px;">Status</th>
                                        <th class="text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: 0.5px;">Vencimento</th>
                                        <th class="text-end text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: 0.5px;">Desconto</th>
                                        <th class="text-end text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total</th>
                                        <th class="text-center text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: 0.5px;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($budget->services as $service)
                                    <tr>
                                        <td class="fw-bold text-dark">{{ $service->code }}</td>
                                        <td>{{ $service->description }}</td>
                                        <td>{{ $service->category?->name ?? '-' }}</td>
                                        <td>
                                            <span class="badge rounded-pill"
                                                style="background-color: {{ $service->color }}20; color: {{ $service->color }}; border: 1px solid {{ $service->color }}40; font-size: 0.7rem;">
                                                {{ $service->name }}
                                            </span>
                                        </td>
                                        <td>{{ $service->due_date ? \Carbon\Carbon::parse($service->due_date)->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="text-end text-warning fw-semibold">
                                            R$ {{ number_format($service->discount, 2, ',', '.') }}
                                        </td>
                                        <td class="text-end text-primary fw-bold">
                                            R$ {{ number_format($service->total, 2, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            <div class="action-btn-group">
                                                <x-button type="link" :href="route('provider.services.show', $service->code)"
                                                    variant="info" size="sm" icon="eye" title="Visualizar" class="action-btn action-btn-view" />
                                                <x-button type="link" :href="route('provider.services.edit', $service->code)"
                                                    variant="outline-primary" size="sm" icon="pencil-fill" title="Editar" class="action-btn action-btn-edit" />
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
                        <div class="list-group list-group-flush">
                            @foreach ($budget->services as $service)
                            <a href="{{ route('provider.services.show', $service->code) }}"
                                class="list-group-item list-group-item-action py-3 border-bottom">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="avatar avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                        <i class="bi bi-tools text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-dark">{{ $service->code }}</span>
                                            <span class="text-muted small">{{ $service->due_date ? \Carbon\Carbon::parse($service->due_date)->format('d/m/Y') : '-' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Descrição</small>
                                    <div class="text-dark fw-semibold">{{ $service->description }}</div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Valor Total</small>
                                        <span class="fw-bold text-primary">R$ {{ number_format($service->total, 2, ',', '.') }}</span>
                                    </div>
                                    <div class="col-6 text-end">
                                        <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Status</small>
                                        <span class="badge rounded-pill" style="background-color: {{ $service->color }}20; color: {{ $service->color }}; border: 1px solid {{ $service->color }}40; font-size: 0.7rem;">
                                            {{ $service->name }}
                                        </span>
                                    </div>
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
            <x-back-button index-route="provider.budgets.index" />
        </div>
        <small class="text-muted d-none d-md-block">
            Última atualização: {{ $budget->updated_at?->format('d/m/Y H:i') }}
        </small>
        <div class="d-flex gap-2">
            <x-button type="link" :href="route('provider.budgets.edit', $budget->code)"
                variant="primary" icon="pencil-fill" label="Editar" />

            <!-- Desktop: Botões diretos -->
            <x-button type="link" :href="route('provider.budgets.print', $budget->code)"
                variant="outline-secondary" icon="printer" label="Imprimir" target="_blank" class="d-none d-md-inline-flex" />

            <x-button type="link" :href="route('provider.budgets.print', ['budget' => $budget->code, 'pdf' => true, 'download' => true])"
                variant="outline-secondary" icon="download" label="PDF" class="d-none d-md-inline-flex" />

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
