@extends('layouts.app')

@section('content')
    <x-layout.page-container>
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-primary fw-bold">
                <i class="bi bi-file-earmark-text me-2"></i>Detalhes do Orçamento
            </h1>
            {{-- Breadcrumb omitted for public page or can be simplified --}}
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Início</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $budget->code }}</li>
                </ol>
            </nav>
        </div>

        <x-layout.grid-row>
            <!-- Main Details -->
            <div class="col-md-8">
                <x-ui.card class="mb-4">
                    <!-- Budget Info -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <small class="text-muted text-uppercase fw-bold">Código</small>
                            <h5 class="fw-bold mb-0 text-dark">{{ $budget->code }}</h5>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted text-uppercase fw-bold">Cliente</small>
                            <h5 class="mb-0 text-dark">{{ $budget->customer->first_name }} {{ $budget->customer->last_name }}</h5>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted text-uppercase fw-bold">Status</small>
                            <div>
                                <span class="badge" style="background-color: {{ $budget->budgetStatus->color }}; font-size: 0.9rem;">
                                    {{ $budget->budgetStatus->name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <small class="text-muted text-uppercase fw-bold">Descrição</small>
                        <p class="lead mb-0 text-dark">{{ $budget->description }}</p>
                    </div>

                    <!-- Additional Details -->
                    <div class="card bg-light border-0 rounded-3">
                        <div class="card-body">
                            <h6 class="mb-3 text-primary fw-bold"><i class="bi bi-info-circle me-2"></i>Detalhes Adicionais</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <small class="text-muted fw-bold">Criado em:</small>
                                    <div class="text-dark">{{ $budget->created_at ? $budget->created_at->format('d/m/Y H:i') : 'Não informado' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted fw-bold">Atualizado em:</small>
                                    <div class="text-dark">{{ $budget->updated_at ? $budget->updated_at->format('d/m/Y H:i') : 'Não informado' }}</div>
                                </div>
                                @if ($budget->payment_terms)
                                    <div class="col-12">
                                        <small class="text-muted fw-bold">Condições de Pagamento:</small>
                                        <div class="text-dark">{{ $budget->payment_terms }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <!-- Financial Summary -->
            <div class="col-md-4">
                <x-ui.card class="h-100">
                    <x-slot:header>
                        <h5 class="mb-0 text-success fw-bold"><i class="bi bi-currency-dollar me-2"></i>Resumo Financeiro</h5>
                    </x-slot:header>

                    @php
                        $cancelled_total = $budget->services->where('status.slug', 'CANCELLED')->sum('total');
                        $total_discount = $budget->discount + $budget->services->sum('discount');
                        $real_total = $budget->total - $cancelled_total - $total_discount;
                    @endphp

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between bg-transparent px-0">
                            <span class="text-muted">Total Bruto:</span>
                            <span class="fw-bold text-dark">R$ {{ \App\Helpers\CurrencyHelper::format($budget->total) }}</span>
                        </li>
                        @if ($cancelled_total > 0)
                            <li class="list-group-item d-flex justify-content-between text-danger bg-transparent px-0">
                                <span>Cancelados:</span>
                                <span>- R$ {{ \App\Helpers\CurrencyHelper::format($cancelled_total) }}</span>
                            </li>
                        @endif
                        <li class="list-group-item d-flex justify-content-between text-danger bg-transparent px-0">
                            <span>Descontos:</span>
                            <span>- R$ {{ \App\Helpers\CurrencyHelper::format($total_discount) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between h5 mb-0 bg-transparent px-0 pt-3 border-top">
                            <strong class="text-dark">Total:</strong>
                            <strong class="text-success">R$ {{ \App\Helpers\CurrencyHelper::format($real_total) }}</strong>
                        </li>
                    </ul>
                    
                    <div class="d-flex justify-content-between mt-3 mb-2">
                        <span class="text-muted small text-uppercase fw-bold">Vencimento:</span>
                        <span class="fw-bold @if ($budget->due_date && $budget->due_date->isPast()) text-danger @else text-dark @endif">
                            {{ $budget->due_date ? $budget->due_date->format('d/m/Y') : 'Não informado' }}
                        </span>
                    </div>

                    <!-- Action Buttons for SENT status -->
                    @if ($budget->budgetStatus->slug === 'sent')
                        <div class="mt-4 pt-3 border-top">
                            <form action="{{ route('budgets.public.choose-status.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="token" value="{{ $token }}">

                                <div class="mb-3">
                                    <label for="action" class="form-label fw-bold text-primary">Escolha uma ação:</label>
                                    <select name="action" id="action" class="form-select" required>
                                        <option value="">Selecione uma opção...</option>
                                        <option value="approve">Aprovar Orçamento</option>
                                        <option value="reject">Rejeitar Orçamento</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="comment" class="form-label fw-bold">Observações (opcional):</label>
                                    <textarea name="comment" id="comment" class="form-control" rows="3" maxlength="500"></textarea>
                                </div>

                                <x-ui.button type="submit" variant="primary" class="w-100 btn-lg" icon="check-circle-fill" label="Confirmar Decisão" />
                            </form>
                        </div>
                    @else
                        <div class="alert alert-info text-center mt-4 mb-0">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            Este orçamento já foi <strong>{{ strtolower($budget->budgetStatus->name ?? $budget->status) }}</strong>.
                        </div>
                    @endif
                </x-ui.card>
            </div>
        </x-layout.grid-row>

        <!-- Linked Services -->
        <x-layout.grid-row class="mt-4">
            <div class="col-12">
                <x-ui.card>
                    <x-slot:header>
                        <h4 class="mb-0 text-primary fw-bold"><i class="bi bi-tools me-2"></i>Serviços Vinculados</h4>
                    </x-slot:header>

                    @forelse($budget->services as $service)
                        <div class="card mb-3 border border-light shadow-sm">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-tag me-2 text-primary"></i>{{ $service->category->name }}</h6>
                                <span class="badge" style="background-color: {{ $service->status->getColor() }};">
                                    {{ $service->status->getDescription() }}
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row g-4 align-items-center">
                                    <div class="col-md-8">
                                        <p class="mb-2 text-muted">{{ $service->description }}</p>
                                        <p class="mb-0 small text-muted">
                                            <i class="bi bi-calendar-event me-1"></i>Vencimento: 
                                            <span class="fw-bold">{{ $service->due_date ? $service->due_date->format('d/m/Y') : 'Não informado' }}</span>
                                        </p>
                                    </div>
                                    <div class="col-md-4 border-start border-light ps-md-4">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted small">Total:</span>
                                            <span class="fw-bold text-success">R$ {{ \App\Helpers\CurrencyHelper::format($service->total) }}</span>
                                        </div>
                                        @if ($service->discount > 0)
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted small">Desconto:</span>
                                                <span class="fw-bold text-danger">- R$ {{ \App\Helpers\CurrencyHelper::format($service->discount) }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                                <span class="fw-bold text-dark">Subtotal:</span>
                                                <span class="fw-bold text-dark">R$ {{ \App\Helpers\CurrencyHelper::format($service->total - $service->discount) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-clipboard-x display-4 mb-3 d-block"></i>
                            <p>Nenhum serviço vinculado a este orçamento.</p>
                        </div>
                    @endforelse
                </x-ui.card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection
