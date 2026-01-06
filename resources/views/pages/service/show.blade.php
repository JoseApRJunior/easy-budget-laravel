@extends('layouts.app')
@section('title', 'Detalhes do Serviço')
@section('content')
<div class="container-fluid py-4">
    <x-page-header
        title="Detalhes do Serviço"
        icon="tools"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Serviços' => route('provider.services.dashboard'),
            $service->code => '#'
        ]">
        <p class="text-muted mb-0">Visualize todas as informações do serviço {{ $service->code }}</p>
    </x-page-header>

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

        <div class="row">
            <div class="col-lg-8">
                {{-- Informações Básicas do Serviço --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">Serviço {{ $service->code }}</h5>
                                <small class="text-muted">Criado em {{ $service->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                            <div>
                                <x-status-badge :item="$service" statusField="status" />
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row g-4 mb-4">
                            <div class="col-12 col-md-6">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-tag me-2"></i>
                                    Informações Gerais
                                </h6>
                                <div class="mb-3">
                                    <small class="small fw-bold text-muted text-uppercase d-block mb-1">Categoria</small>
                                    <span class="text-dark fw-bold">{{ $service->category?->name ?? 'Não definida' }}</span>
                                </div>
                                <div class="mb-3">
                                    <small class="small fw-bold text-muted text-uppercase d-block mb-1">Orçamento</small>
                                    <a href="{{ route('provider.budgets.show', $service->budget?->code) }}"
                                        class="text-decoration-none fw-bold">
                                        {{ $service->budget?->code ?? 'N/A' }}
                                    </a>
                                </div>
                                @if ($service->due_date)
                                    <div class="mb-3">
                                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">Prazo</small>
                                        <span class="text-dark fw-bold">
                                            {{ \Carbon\Carbon::parse($service->due_date)->format('d/m/Y') }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="col-12 col-md-6">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-currency-dollar me-2"></i>
                                    Valores
                                </h6>
                                <div class="mb-3">
                                    <small class="small fw-bold text-muted text-uppercase d-block mb-1">Total</small>
                                    <span class="text-success fw-bold fs-5">
                                        {{ \App\Helpers\CurrencyHelper::format($service->total) }}</span>
                                </div>
                                <div class="mb-3">
                                    <small class="small fw-bold text-muted text-uppercase d-block mb-1">Desconto</small>
                                    <span class="text-danger fw-bold">
                                        {{ \App\Helpers\CurrencyHelper::format($service->discount) }}</span>
                                </div>
                                <div class="mb-3">
                                    <small class="small fw-bold text-muted text-uppercase d-block mb-1">Subtotal</small>
                                    <span class="text-dark fw-bold">
                                        {{ \App\Helpers\CurrencyHelper::format($service->total + $service->discount) }}</span>
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
                        <div class="card-body p-0">
                            {{-- Desktop View --}}
                            <div class="desktop-view">
                                <div class="table-responsive">
                                    <table class="modern-table table mb-0">
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
                                                                <br><small class="text-muted">{{ $item->product->description }}</small>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>{{ \App\Helpers\CurrencyHelper::format($item->quantity, false) }}</td>
                                                    <td>{{ \App\Helpers\CurrencyHelper::format($item->unit_value) }}</td>
                                                    <td><strong>{{ \App\Helpers\CurrencyHelper::format($item->total) }}</strong></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-secondary">
                                                <th colspan="3">Total dos Itens:</th>
                                                <th>{{ \App\Helpers\CurrencyHelper::format($service->serviceItems->sum('total')) }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            {{-- Mobile View --}}
                            <div class="mobile-view">
                                <div class="list-group">
                                    @foreach ($service->serviceItems as $item)
                                        <div class="list-group-item py-3">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-box-seam text-muted me-2 mt-1"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold mb-2">{{ $item->product?->name ?? 'Produto não encontrado' }}</div>
                                                    <div class="small text-muted mb-2">
                                                        <span class="me-3"><strong>Qtd:</strong> {{ $item->quantity }}</span>
                                                        <span><strong>Unit:</strong> {{ \App\Helpers\CurrencyHelper::format($item->unit_value) }}</span>
                                                    </div>
                                                    <div class="text-success fw-semibold">Total: {{ \App\Helpers\CurrencyHelper::format($item->total) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    <div class="list-group-item bg-body-secondary">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>Total dos Itens:</strong>
                                            <strong class="text-success">{{ \App\Helpers\CurrencyHelper::format($service->serviceItems->sum('total')) }}</strong>
                                        </div>
                                    </div>
                                </div>
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
                                <x-button
                                    href="{{ route('provider.services.edit', $service->code) }}"
                                    variant="outline-primary"
                                    icon="bi bi-pencil">
                                    Editar Serviço
                                </x-button>
                            @endif
                            @if ($service->budget)
                                <x-button
                                    href="{{ route('provider.budgets.show', $service->budget->code) }}"
                                    variant="outline-info"
                                    icon="bi bi-receipt">
                                    Ver Orçamento
                                </x-button>
                            @endif

                            {{-- Botões de Fatura --}}
                            @if ($service->status->isFinished() || $service->status->value === 'COMPLETED')
                                {{-- Serviço finalizado - pode criar fatura completa --}}
                                <x-button
                                    href="{{ route('provider.invoices.create.from-service', $service->code) }}"
                                    variant="outline-success"
                                    icon="bi bi-receipt">
                                    Criar Fatura
                                </x-button>
                            @elseif($service->status->isActive() && $service->serviceItems && $service->serviceItems->count() > 0)
                                {{-- Serviço ativo com itens - pode criar fatura parcial --}}
                                <x-button
                                    href="{{ route('provider.invoices.create.partial-from-service', $service->code) }}"
                                    variant="outline-warning"
                                    icon="bi bi-receipt">
                                    Criar Fatura Parcial
                                </x-button>
                            @endif
                            <x-button
                                type="button"
                                variant="outline-success"
                                onclick="window.print()"
                                icon="bi bi-printer">
                                Imprimir
                            </x-button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Botões de Ação (Footer) --}}
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="d-flex gap-2">
                <x-button
                    href="{{ url()->previous(route('provider.services.index')) }}"
                    variant="outline-secondary"
                    icon="bi bi-arrow-left">
                    Voltar
                </x-button>
            </div>
            <small class="text-muted d-none d-md-block">
                Última atualização: {{ $service->updated_at?->format('d/m/Y H:i') }}
            </small>
            <div class="d-flex gap-2">
                @if ($service->canBeEdited())
                    <x-button
                        href="{{ route('provider.services.edit', $service->code) }}"
                        variant="primary"
                        icon="bi bi-pencil-fill">
                        Editar
                    </x-button>
                @endif
                <x-button
                    type="button"
                    variant="outline-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteModal"
                    icon="bi bi-trash-fill">
                    Excluir
                </x-button>
            </div>
        </div>

        {{-- Modal de Confirmação de Exclusão --}}
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar Exclusão</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Tem certeza de que deseja excluir o serviço <strong>{{ $service->code }}</strong>?
                        <br><small class="text-muted">Esta ação não pode ser desfeita.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <form action="{{ route('provider.services.destroy', $service->code) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Excluir</button>
                        </form>
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
