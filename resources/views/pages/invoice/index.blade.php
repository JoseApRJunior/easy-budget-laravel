@extends( 'layout.app' )

@section( 'content' )
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-receipt me-2"></i>Faturas
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Faturas</li>
                </ol>
            </nav>
        </div>

        <!-- Action Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card h-100 shadow-sm border-0 hover-shadow">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-opacity-10 me-3">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            <h5 class="card-title mb-0">Relatório de Faturas</h5>
                        </div>
                        <p class="card-text text-muted flex-grow-1">
                            Visualize todas as faturas geradas no sistema.
                        </p>
                        <a href="{{ route( 'provider.reports.invoices' ) }}" class="btn btn-primary">
                            Acessar Relatório
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 shadow-sm border-0 hover-shadow">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-opacity-10 me-3">
                                <i class="bi bi-info-circle"></i>
                            </div>
                            <h5 class="card-title mb-0">Como Gerar Faturas</h5>
                        </div>
                        <p class="card-text text-muted flex-grow-1">
                            Faturas são geradas a partir de serviços concluídos. Navegue até um serviço e clique em "Gerar
                            Fatura".
                        </p>
                        <a href="{{ route( 'provider.services.index' ) }}" class="btn btn-info text-white">
                            Ver Serviços
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <form id="filter-form" class="row g-3">
                    @csrf
                    <div class="col-md-2">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="code" name="code" placeholder="Nº Fatura"
                                value="{{ $filters[ 'code' ] ?? '' }}">
                            <label for="code"><i class="bi bi-hash me-1"></i>Nº Fatura</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="start_date" name="start_date"
                                placeholder="Data Inicial" value="{{ $filters[ 'start_date' ] ?? '' }}">
                            <label for="start_date"><i class="bi bi-calendar-event me-1"></i>Data Inicial</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="end_date" name="end_date" placeholder="Data Final"
                                value="{{ $filters[ 'end_date' ] ?? '' }}">
                            <label for="end_date"><i class="bi bi-calendar-event me-1"></i>Data Final</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="customer_name" name="customer_name"
                                placeholder="Cliente" value="{{ $filters[ 'customer_name' ] ?? '' }}" autocomplete="off">
                            <label for="customer_name"><i class="bi bi-person me-1"></i>Cliente</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <input type="text" class="form-control money-input" id="total" name="total"
                                placeholder="Valor Mínimo (R$)"
                                value="{{ isset( $filters[ 'total' ] ) ? number_format( $filters[ 'total' ], 2, ',', '.' ) : '' }}"
                                autocomplete="off" maxlength="20">
                            <label for="total"><i class="bi bi-currency-dollar me-1"></i>Valor Mínimo (R$)</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <select class="form-select" id="status" name="status">
                                {!! invoice_status_options( $invoice_statuses, $filters[ 'status' ] ?? null ) !!}
                            </select>
                            <label for="status"><i class="bi bi-flag me-1"></i>Status</label>
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                        <button type="button" id="clear-filters" class="btn btn-light btn-lg px-4">
                            <i class="bi bi-x-circle me-2"></i>Limpar
                        </button>
                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-search me-2"></i>Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Initial Message -->
        <div id="initial-message" class="card border-0 shadow-sm text-center py-5 @if( isset( $invoices ) ) d-none @endif">
            <div class="card-body">
                <i class="bi bi-funnel-fill text-primary mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-gray-800 mb-3">Utilize os filtros acima para buscar faturas</h5>
                <p class="text-muted mb-0">Configure os critérios desejados e clique em "Filtrar" para visualizar os
                    resultados</p>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loading-spinner" class="d-none">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="text-muted mt-3 mb-0">Processando sua solicitação...</p>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div id="results-container" class="d-none">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0"><i class="bi bi-list me-2"></i>Faturas Encontradas</h2>
                        <span id="results-count" class="text-muted">
                            @if ( isset( $invoices ) )
                                Mostrando {{ count( $invoices ) }} resultados
                            @endif
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="results-table" class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th># Fatura</th>
                                        <th>Cliente</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th>Data Criação</th>
                                        <th>Vencimento</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Será preenchido via JavaScript --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @include( 'partials.components.table_paginator' )
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script src="{{ asset( 'assets/js/modules/table-paginator.js' ) }}"></script>
    <script src="{{ asset( 'assets/js/invoice.js' ) }}"></script>
    <script>
        function confirmDelete( invoiceId ) {
            // Sua lógica de confirmação aqui
        }
    </script>
@endpush
