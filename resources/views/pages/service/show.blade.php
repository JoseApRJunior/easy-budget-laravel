@extends('layouts.app')

@section('content')
    <div class="container-fluid py-1">
        <div class="row">
            <div class="col-12">
                {{-- Breadcrumbs --}}
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('provider.dashboard') }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ route('provider.services.index') }}">Serviços</a>
                                </li>
                                <li class="breadcrumb-item active">{{ $service->code }}</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex gap-2">
                        @if ($service->canBeEdited())
                            <a href="{{ route('provider.services.edit', $service->code) }}"
                                class="btn btn-outline-primary">
                                <i class="bi bi-pencil me-2"></i>
                                Editar
                            </a>
                        @endif
                        <a href="{{ route('provider.services.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Voltar
                        </a>
                    </div>
                </div>

                {{-- Alerta de Faturas Existentes --}}
                @if ($service->invoices && $service->invoices->count() > 0)
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        Este serviço já possui {{ $service->invoices->count() }} fatura(s).
                        <a href="{{ route('provider.invoices.index', ['search' => $service->code]) }}" class="alert-link">
                            Ver faturas
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                {{-- Informações Básicas do Serviço --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-1">Serviço {{ $service->code }}</h5>
                                <small class="text-muted">Criado em
                                    {{ $service->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                            <div>
                                @php($statusEnum = $service->serviceStatus)
                                @if ($statusEnum)
                                    <span class="badge fs-6 px-3 py-2"
                                        style="background-color: {{ $statusEnum->getColor() }}">
                                        <i class="bi {{ $statusEnum->getIcon() }} me-1"></i>
                                        {{ $statusEnum->getDescription() }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary fs-6 px-3 py-2">
                                        <i class="bi bi-circle me-1"></i>
                                        Status não definido
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-tag me-2"></i>
                                    Informações Gerais
                                </h6>
                                <div class="mb-2">
                                    <strong>Categoria:</strong>
                                    <span class="text-muted">{{ $service->category?->name ?? 'Não definida' }}</span>
                                </div>
                                <div class="mb-2">
                                    <strong>Orçamento:</strong>
                                    <a href="{{ route('provider.budgets.show', $service->budget?->code) }}"
                                        class="text-decoration-none">
                                        {{ $service->budget?->code ?? 'N/A' }}
                                    </a>
                                </div>
                                @if ($service->due_date)
                                    <div class="mb-2">
                                        <strong>Prazo:</strong>
                                        <span class="text-muted">
                                            {{ \Carbon\Carbon::parse($service->due_date)->format('d/m/Y') }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-currency-dollar me-2"></i>
                                    Valores
                                </h6>
                                <div class="mb-2">
                                    <strong>Total:</strong>
                                    <span class="text-success fs-5">R$
                                        {{ number_format($service->total, 2, ',', '.') }}</span>
                                </div>
                                <div class="mb-2">
                                    <strong>Desconto:</strong>
                                    <span class="text-danger">R$
                                        {{ number_format($service->discount, 2, ',', '.') }}</span>
                                </div>
                                <div class="mb-2">
                                    <strong>Subtotal:</strong>
                                    <span class="text-muted">R$
                                        {{ number_format($service->total + $service->discount, 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        @if ($service->description)
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-card-text me-2"></i>
                                    Descrição
                                </h6>
                                <p class="text-muted">{{ $service->description }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Itens do Serviço --}}
                @if ($service->serviceItems && $service->serviceItems->count() > 0)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0">
                                <i class="bi bi-list-ul me-2"></i>
                                Itens do Serviço
                                <span class="badge bg-primary ms-2">{{ $service->serviceItems->count() }}</span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Produto</th>
                                            <th>Quantidade</th>
                                            <th>Valor Unitário</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($service->serviceItems as $item)
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong>{{ $item->product?->name ?? 'Produto não encontrado' }}</strong>
                                                        @if ($item->product?->description)
                                                            <br><small
                                                                class="text-muted">{{ $item->product->description }}</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>R$ {{ number_format($item->unit_value, 2, ',', '.') }}</td>
                                                <td>
                                                    <strong>R$ {{ number_format($item->total, 2, ',', '.') }}</strong>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <th colspan="3">Total dos Itens:</th>
                                            <th>R$
                                                {{ number_format($service->serviceItems->sum('total'), 2, ',', '.') }}
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Agendamentos --}}
                @if ($service->schedules && $service->schedules->count() > 0)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0">
                                <i class="bi bi-calendar me-2"></i>
                                Agendamentos
                            </h6>
                        </div>
                        <div class="card-body">
                            @foreach ($service->schedules as $schedule)
                                <div class="d-flex align-items-center mb-3 p-3 border rounded">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-calendar-event text-primary me-2"></i>
                                            <strong>{{ \Carbon\Carbon::parse($schedule->start_date_time)->format('d/m/Y') }}</strong>
                                            <span class="text-muted ms-2">
                                                {{ \Carbon\Carbon::parse($schedule->start_date_time)->format('H:i') }}
                                                -
                                                {{ \Carbon\Carbon::parse($schedule->end_date_time)->format('H:i') }}
                                            </span>
                                        </div>
                                        @if ($schedule->location)
                                            <div class="text-muted">
                                                <i class="bi bi-geo-alt me-1"></i>
                                                {{ $schedule->location }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                {{-- Informações do Cliente --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="mb-0">
                            <i class="bi bi-person-circle me-2"></i>
                            Cliente
                        </h6>
                    </div>
                    <div class="card-body">
                        @if ($service->budget?->customer)
                            <div class="mb-3">
                                <a href="{{ route('provider.customers.show', $service->budget->customer) }}"
                                    class="text-decoration-none">
                                    <strong>{{ $service->budget->customer->commonData?->first_name }}
                                        {{ $service->budget->customer->commonData?->last_name }}</strong>
                                </a>
                                @if ($service->budget->customer->commonData?->company_name)
                                    <br>
                                    <a href="{{ route('provider.customers.show', $service->budget->customer) }}"
                                        class="text-decoration-none">
                                        <small
                                            class="text-muted">{{ $service->budget->customer->commonData->company_name }}</small>
                                    </a>
                                @endif
                            </div>

                            @if ($service->budget->customer->contact)
                                <div class="mb-2">
                                    <i class="bi bi-envelope me-2 text-muted"></i>
                                    <a href="mailto:{{ $service->budget->customer->contact->email }}"
                                        class="text-decoration-none">
                                        {{ $service->budget->customer->contact->email }}
                                    </a>
                                </div>
                                @if ($service->budget->customer->contact->phone)
                                    <div class="mb-2">
                                        <i class="bi bi-telephone me-2 text-muted"></i>
                                        <a href="tel:{{ $service->budget->customer->contact->phone }}"
                                            class="text-decoration-none">
                                            {{ $service->budget->customer->contact->phone }}
                                        </a>
                                    </div>
                                @endif
                                @if ($service->budget->customer->contact->phone_business)
                                    <div class="mb-2">
                                        <i class="bi bi-building me-2 text-muted"></i>
                                        <a href="tel:{{ $service->budget->customer->contact->phone_business }}"
                                            class="text-decoration-none">
                                            {{ $service->budget->customer->contact->phone_business }}
                                        </a>
                                    </div>
                                @endif
                            @endif
                        @else
                            <p class="text-muted mb-0">Cliente não informado</p>
                        @endif
                    </div>
                </div>

                {{-- Ações Rápidas --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="mb-0">
                            <i class="bi bi-lightning me-2"></i>
                            Ações Rápidas
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if ($service->canBeEdited())
                                <a href="{{ route('provider.services.edit', $service->code) }}"
                                    class="btn btn-outline-primary">
                                    <i class="bi bi-pencil me-2"></i>
                                    Editar Serviço
                                </a>
                            @endif
                            @if ($service->budget)
                                <a href="{{ route('provider.budgets.show', $service->budget->code) }}"
                                    class="btn btn-outline-info">
                                    <i class="bi bi-receipt me-2"></i>
                                    Ver Orçamento
                                </a>
                            @endif

                            {{-- Botões de Fatura --}}
                            @if ($service->status->isFinished() || $service->status->value === 'COMPLETED')
                                {{-- Serviço finalizado - pode criar fatura completa --}}
                                <a href="{{ route('provider.invoices.create.from-service', $service->code) }}"
                                    class="btn btn-outline-success">
                                    <i class="bi bi-receipt me-2"></i>
                                    Criar Fatura
                                </a>
                            @elseif($service->status->isActive() && $service->serviceItems && $service->serviceItems->count() > 0)
                                {{-- Serviço ativo com itens - pode criar fatura parcial --}}
                                <a href="{{ route('provider.invoices.create.partial-from-service', $service->code) }}"
                                    class="btn btn-outline-warning">
                                    <i class="bi bi-receipt me-2"></i>
                                    Criar Fatura Parcial
                                </a>
                            @endif
                            <button type="button" class="btn btn-outline-success" onclick="window.print()">
                                <i class="bi bi-printer me-2"></i>
                                Imprimir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .card {
            border-radius: 12px;
        }

        .badge {
            border-radius: 20px;
        }

        .btn {
            border-radius: 8px;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #6c757d;
        }

        .breadcrumb {
            background: none;
            padding: 0;
        }

        .breadcrumb-item+.breadcrumb-item::before {
            content: "›";
            color: #6c757d;
        }
    </style>
@endpush
