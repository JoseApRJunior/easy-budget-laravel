@extends('layouts.app')

@section('title', 'Detalhes do Orçamento')

@section('content')
<div class="container-fluid py-4">
    <x-page-header
        title="Detalhes do Orçamento"
        icon="file-earmark-text"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
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
                        <i class="bi bi-info-circle me-2"></i>
                        <span>Informações Básicas</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-item mb-3">
                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">Código</small>
                        <span class="text-dark fw-bold">{{ $budget->code }}</span>
                    </div>

                    <div class="info-item mb-3">
                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">Status</small>
                        <div>
                            <x-status-badge :item="$budget" statusField="status" />
                        </div>
                    </div>

                    <div class="info-item mb-3">
                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">Data de Criação</small>
                        <span class="text-dark fw-bold">{{ $budget->created_at->format('d/m/Y H:i') }}</span>
                    </div>

                    @if ($budget->due_date)
                    <div class="info-item">
                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">Data de Vencimento</small>
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
                        <i class="bi bi-person me-2"></i>
                        <span>Cliente</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if ($budget->customer && $budget->customer->commonData)
                    <div class="info-item mb-3">
                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">Nome/Razão Social</small>
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
                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">CNPJ</small>
                        <span class="text-dark fw-bold">{{ \App\Helpers\DocumentHelper::formatCnpj($budget->customer->commonData->cnpj) }}</span>
                    </div>
                    @elseif($budget->customer->commonData->cpf)
                    <div class="info-item mb-3">
                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">CPF</small>
                        <span class="text-dark fw-bold">{{ \App\Helpers\DocumentHelper::formatCpf($budget->customer->commonData->cpf) }}</span>
                    </div>
                    @endif

                    @if ($budget->customer->contact)
                    @if ($budget->customer->contact->email_personal)
                    <div class="info-item mb-3">
                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">E-mail</small>
                        <span class="text-dark fw-bold text-break">{{ $budget->customer->contact->email_personal }}</span>
                    </div>
                    @endif

                    @if ($budget->customer->contact->phone_personal)
                    <div class="info-item">
                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">Telefone</small>
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
                        <i class="bi bi-cash-stack me-2"></i>
                        <span>Resumo Financeiro</span>
                    </h5>
                </div>
                <div class="card-body">
                    @php
                    $servicesSubtotal = $budget->services?->sum('total') ?? 0;
                    @endphp

                    <div class="info-item mb-3">
                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">Subtotal (Serviços)</small>
                        <h5 class="mb-0 text-dark fw-bold">R$ {{ \App\Helpers\CurrencyHelper::format($servicesSubtotal) }}</h5>
                    </div>

                    <div class="info-item mb-3">
                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">Desconto</small>
                        <h5 class="mb-0 text-warning fw-bold">R$ {{ \App\Helpers\CurrencyHelper::format($budget->discount) }}</h5>
                    </div>

                    <div class="info-item">
                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">Total Geral</small>
                        <h4 class="mb-0 text-success fw-bold">R$ {{ \App\Helpers\CurrencyHelper::format($budget->total) }}</h4>
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
                        <i class="bi bi-file-text me-2"></i>
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
                        <i class="bi bi-credit-card me-2"></i>
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
                                <i class="bi bi-tools me-2"></i>
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
                                        <td class="fw-bold text-dark">{{ $service->code }}</td>
                                        <td>{{ $service->description }}</td>
                                        <td>{{ $service->category?->name ?? '-' }}</td>
                                        <td>
                                            <x-status-badge :item="$service" />
                                        </td>
                                        <td>{{ $service->due_date ? \Carbon\Carbon::parse($service->due_date)->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="text-end text-warning fw-semibold">
                                            R$ {{ \App\Helpers\CurrencyHelper::format($service->discount) }}
                                        </td>
                                        <td class="text-end text-primary fw-bold">
                                            R$ {{ \App\Helpers\CurrencyHelper::format($service->total) }}
                                        </td>
                                        <td class="text-center">
                                            <x-action-buttons
                                                :item="$service"
                                                resource="services"
                                                identifier="code"
                                                size="sm"
                                                :showDelete="false"
                                            />
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
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-dark">{{ $service->code }}</span>
                                            <span class="text-muted small">{{ $service->due_date ? \Carbon\Carbon::parse($service->due_date)->format('d/m/Y') : '-' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <small class="small fw-bold text-muted text-uppercase d-block mb-1">Descrição</small>
                                    <div class="text-dark fw-semibold">{{ $service->description }}</div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">Valor Total</small>
                                        <span class="fw-bold text-primary">R$ {{ \App\Helpers\CurrencyHelper::format($service->total) }}</span>
                                    </div>
                                    <div class="col-6 text-end">
                                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">Status</small>
                                        <x-status-badge :item="$service" />
                                    </div>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @else
                    <div class="card-body py-5">
                        <x-empty-state
                            resource="serviços"
                            icon="tools"
                            message="Nenhum serviço vinculado a este orçamento"
                        />
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

            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download me-2"></i>
                    <span class="d-none d-md-inline">Exportar / Imprimir</span>
                    <span class="d-md-none">Ações</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('provider.budgets.print', $budget->code) }}" target="_blank">
                            <i class="bi bi-printer me-2 text-secondary"></i>
                            Imprimir Orçamento
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('provider.budgets.print', ['code' => $budget->code, 'pdf' => true, 'download' => true]) }}">
                            <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>
                            Exportar para PDF
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('provider.budgets.export-xlsx', $budget->code) }}">
                            <i class="bi bi-file-earmark-excel me-2 text-success"></i>
                            Exportar para Excel (XLSX)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
