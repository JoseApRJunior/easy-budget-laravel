@extends('layouts.app')

@section('title', 'Relatório Financeiro')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-graph-up me-2"></i>
                    Relatório Financeiro
                </h1>
                <p class="text-muted">Análise completa da situação financeira da empresa</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('provider.reports.index') }}">Relatórios</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Financeiro</li>
                </ol>
            </nav>
        </div>

        <!-- Filtros de Busca -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
            </div>
            <div class="card-body">
                <form id="filtersFormFinancial" method="GET" action="{{ route('provider.reports.financial') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="period">Período</label>
                                <select class="form-control" id="period" name="period">
                                    <option value="">Selecione o período</option>
                                    <option value="current_month"
                                        {{ request('period') == 'current_month' ? 'selected' : '' }}>
                                        Mês Atual</option>
                                    <option value="last_month" {{ request('period') == 'last_month' ? 'selected' : '' }}>Mês
                                        Anterior</option>
                                    <option value="current_year"
                                        {{ request('period') == 'current_year' ? 'selected' : '' }}>Ano
                                        Atual</option>
                                    <option value="last_year" {{ request('period') == 'last_year' ? 'selected' : '' }}>Ano
                                        Anterior</option>
                                    <option value="custom" {{ request('period') == 'custom' ? 'selected' : '' }}>Período
                                        Personalizado</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="start_date">Data Inicial</label>
                                <input type="text" class="form-control" id="start_date" name="start_date"
                                    value="{{ request('start_date') ?? '' }}" placeholder="DD/MM/AAAA">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="end_date">Data Final</label>
                                <input type="text" class="form-control" id="end_date" name="end_date"
                                    value="{{ request('end_date') ?? '' }}" placeholder="DD/MM/AAAA">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="transaction_type">Tipo de Transação</label>
                                <select class="form-control" id="transaction_type" name="transaction_type">
                                    <option value="">Todos os Tipos</option>
                                    <option value="revenue"
                                        {{ request('transaction_type') == 'revenue' ? 'selected' : '' }}>
                                        Receitas</option>
                                    <option value="expense"
                                        {{ request('transaction_type') == 'expense' ? 'selected' : '' }}>
                                        Despesas</option>
                                    <option value="invoice"
                                        {{ request('transaction_type') == 'invoice' ? 'selected' : '' }}>
                                        Faturas</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <x-button type="submit" variant="primary" icon="search" label="Filtrar" class="flex-grow-1" id="btnFilterFinancial" />
                                <x-button type="link" :href="route('provider.reports.financial')" variant="outline-secondary" icon="x" label="Limpar" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Empty State Inicial --}}
        @if (!request()->hasAny(['period', 'start_date', 'end_date', 'transaction_type']))
            <div class="card border-0 shadow-sm text-center py-4">
                <div class="card-body">
                    <i class="bi bi-graph-up text-primary mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-gray-800 mb-3">Relatório Financeiro</h5>
                    <p class="text-muted mb-3">
                        Esta funcionalidade está em desenvolvimento. Configure os filtros e clique em "Filtrar" para
                        visualizar análises financeiras.
                    </p>
                    <div class="row justify-content-center">
                        <div class="col-md-4">
                            <div class="card border-primary border-opacity-25">
                                <div class="card-body text-center">
                                    <i class="bi bi-currency-dollar text-success mb-2" style="font-size: 2rem;"></i>
                                    <h6>Receitas</h6>
                                    <p class="text-muted small mb-0">Total de ingresos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-primary border-opacity-25">
                                <div class="card-body text-center">
                                    <i class="bi bi-credit-card text-danger mb-2" style="font-size: 2rem;"></i>
                                    <h6>Despesas</h6>
                                    <p class="text-muted small mb-0">Total de gastos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-primary border-opacity-25">
                                <div class="card-body text-center">
                                    <i class="bi bi-graph-up-arrow text-info mb-2" style="font-size: 2rem;"></i>
                                    <h6>Lucro</h6>
                                    <p class="text-muted small mb-0">Resultado líquido</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Resultados -->
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                            <h5 class="mb-0 d-flex align-items-center flex-wrap">
                                <span class="me-2">
                                    <i class="bi bi-bar-chart-line me-1"></i>
                                    <span class="d-none d-sm-inline">Análise Financeira</span>
                                    <span class="d-sm-none">Financeiro</span>
                                </span>
                            </h5>
                        </div>
                        <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                            <div class="d-flex justify-content-start justify-content-lg-end">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" title="Exportar PDF"
                                        id="export-pdf">
                                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm" title="Exportar Excel"
                                        id="export-excel">
                                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">

                    <!-- Mobile View -->
                    <div class="mobile-view">
                        <div class="p-3">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="card bg-success bg-opacity-10 border-success border-opacity-25">
                                        <div class="card-body text-center">
                                            <i class="bi bi-currency-dollar text-success mb-2"
                                                style="font-size: 2rem;"></i>
                                            <h5 class="text-success">Receitas Totais</h5>
                                            <h3 class="text-success mb-0">R$ 0,00</h3>
                                            <small class="text-muted">Período selecionado</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="card bg-danger bg-opacity-10 border-danger border-opacity-25">
                                        <div class="card-body text-center">
                                            <i class="bi bi-credit-card text-danger mb-2" style="font-size: 2rem;"></i>
                                            <h5 class="text-danger">Despesas Totais</h5>
                                            <h3 class="text-danger mb-0">R$ 0,00</h3>
                                            <small class="text-muted">Período selecionado</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="card bg-info bg-opacity-10 border-info border-opacity-25">
                                        <div class="card-body text-center">
                                            <i class="bi bi-graph-up-arrow text-info mb-2" style="font-size: 2rem;"></i>
                                            <h5 class="text-info">Lucro Líquido</h5>
                                            <h3 class="text-info mb-0">R$ 0,00</h3>
                                            <small class="text-muted">Receitas - Despesas</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State para Mobile -->
                            <div class="text-center py-4">
                                <i class="bi bi-graph-up text-primary mb-3" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mb-3">Dados em Desenvolvimento</h5>
                                <p class="text-muted small mb-3">
                                    O sistema de análise financeira está sendo desenvolvido. Em breve você poderá visualizar
                                    gráficos e análises completas.
                                </p>
                                <a href="{{ route('provider.dashboard') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-arrow-left me-2"></i>Voltar ao Dashboard
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop View -->
                    <div class="desktop-view">
                        <div class="p-4">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="card bg-success bg-opacity-10 border-success border-opacity-25 h-100">
                                        <div class="card-body text-center">
                                            <i class="bi bi-currency-dollar text-success mb-2"
                                                style="font-size: 2rem;"></i>
                                            <h5 class="text-success">Receitas Totais</h5>
                                            <h3 class="text-success mb-0">R$ 0,00</h3>
                                            <small class="text-muted">Período selecionado</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-danger bg-opacity-10 border-danger border-opacity-25 h-100">
                                        <div class="card-body text-center">
                                            <i class="bi bi-credit-card text-danger mb-2" style="font-size: 2rem;"></i>
                                            <h5 class="text-danger">Despesas Totais</h5>
                                            <h3 class="text-danger mb-0">R$ 0,00</h3>
                                            <small class="text-muted">Período selecionado</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-info bg-opacity-10 border-info border-opacity-25 h-100">
                                        <div class="card-body text-center">
                                            <i class="bi bi-graph-up-arrow text-info mb-2" style="font-size: 2rem;"></i>
                                            <h5 class="text-info">Lucro Líquido</h5>
                                            <h3 class="text-info mb-0">R$ 0,00</h3>
                                            <small class="text-muted">Receitas - Despesas</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State para Desktop -->
                            <div class="text-center py-5">
                                <i class="bi bi-graph-up text-primary mb-3" style="font-size: 4rem;"></i>
                                <h5 class="text-muted mb-3">Dados em Desenvolvimento</h5>
                                <p class="text-muted mb-4">
                                    O sistema de análise financeira está sendo desenvolvido. Em breve você poderá
                                    visualizar:
                                </p>
                                <div class="row justify-content-center mb-4">
                                    <div class="col-md-8">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="text-start">
                                                    <i class="bi bi-check-circle text-success me-2"></i>Gráficos de
                                                    receitas e despesas<br>
                                                    <i class="bi bi-check-circle text-success me-2"></i>Análise de fluxo de
                                                    caixa<br>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="text-start">
                                                    <i class="bi bi-check-circle text-success me-2"></i>Projeções
                                                    financeiras<br>
                                                    <i class="bi bi-check-circle text-success me-2"></i>Comparativos por
                                                    período<br>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ route('provider.dashboard') }}" class="btn btn-primary">
                                    <i class="bi bi-arrow-left me-2"></i>Voltar ao Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        const parseDate = (str) => {
            const parts = str.split('/');
            if (parts.length === 3) {
                return new Date(parts[2], parts[1] - 1, parts[0]);
            }
            return new Date(str);
        };

        // Mostrar/ocultar campos de data baseado no período selecionado
        document.addEventListener('DOMContentLoaded', function() {
            const periodSelect = document.getElementById('period');
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const form = startDate ? startDate.closest('form') : null;

            function toggleDateInputs() {
                const isCustom = periodSelect.value === 'custom';
                startDate.parentElement.parentElement.style.display = isCustom ? 'block' : 'none';
                endDate.parentElement.parentElement.style.display = isCustom ? 'block' : 'none';
            }

            if (periodSelect) {
                periodSelect.addEventListener('change', toggleDateInputs);
                toggleDateInputs(); // Chamar na inicialização
            }

            if (form) {
                if (typeof VanillaMask !== 'undefined') {
                    new VanillaMask('start_date', 'date');
                    new VanillaMask('end_date', 'date');
                }

                form.addEventListener('submit', function(e) {
                    if (periodSelect.value === 'custom') {
                        if (startDate.value && endDate.value) {
                            const start = parseDate(startDate.value);
                            const end = parseDate(endDate.value);

                            if (start > end) {
                                e.preventDefault();
                                if (window.easyAlert) {
                                    window.easyAlert.error('A data inicial não pode ser maior que a data final.');
                                } else {
                                    alert('A data inicial não pode ser maior que a data final.');
                                }
                                startDate.focus();
                                return;
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
