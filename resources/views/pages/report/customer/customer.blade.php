@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        {{-- Cabeçalho --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-people me-2"></i>Relatório de Clientes
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/provider">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/provider/reports">Relatórios</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Clientes</li>
                </ol>
            </nav>
        </div>

        {{-- Filtros --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <form id="filter-form" class="row g-3">
                    @csrf

                    <!-- Nome -->
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="name" name="name" placeholder="Nome do Cliente"
                                value="{{ $filters[ 'name' ] ?? '' }}">
                            <label for="name">
                                <i class="bi bi-person me-1"></i>Nome do Cliente
                            </label>
                        </div>
                    </div>

                    <!-- CPF/CNPJ -->
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="document" name="document" placeholder="CPF ou CNPJ"
                                value="{{ $filters[ 'document' ] ?? '' }}">
                            <label for="document">
                                <i class="bi bi-file-person me-1"></i>CPF ou CNPJ
                            </label>
                        </div>
                    </div>

                    <!-- Data de Cadastro Inicial -->
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="start_date" name="start_date"
                                placeholder="Data de Cadastro Inicial" pattern="\d{4}-\d{2}-\d{2}"
                                value="{{ $filters[ 'start_date' ] ?? '' }}">
                            <label for="start_date">
                                <i class="bi bi-calendar-event me-1"></i>Data de Cadastro Inicial
                            </label>
                        </div>
                    </div>

                    <!-- Data de Cadastro Final -->
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="end_date" name="end_date"
                                placeholder="Data de Cadastro Final" pattern="\d{4}-\d{2}-\d{2}"
                                value="{{ $filters[ 'end_date' ] ?? '' }}">
                            <label for="end_date">
                                <i class="bi bi-calendar-event me-1"></i>Data de Cadastro Final
                            </label>
                        </div>
                    </div>

                    <!-- Botões -->
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

        {{-- Mensagem Inicial --}}
        <div id="initial-message" class="card border-0 shadow-sm text-center py-1 @if( isset( $customers ) ) d-none @endif">
            <div class="card-body">
                <i class="bi bi-funnel-fill text-primary mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-gray-800 mb-3">Utilize os filtros acima para gerar o relatório</h5>
                <p class="text-muted mb-0">
                    Configure os critérios desejados e clique em "Filtrar" para visualizar os resultados
                </p>
            </div>
        </div>

        {{-- Loading Spinner --}}
        <div id="loading-spinner" class="d-none">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-1">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="text-muted mt-3 mb-0">Processando sua solicitação...</p>
                </div>
            </div>
        </div>

        {{-- Resultados --}}
        <div id="results-container" class="d-none">
            <div class="card border-0 shadow-sm">
                {{-- Cabeçalho dos Resultados --}}
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span id="results-count" class="text-muted">
                            @if( isset( $customers ) )
                                Mostrando {{ count( $customers ) }} resultados
                            @endif
                        </span>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary" title="Exportar PDF" id="export-pdf">
                                <i class="bi bi-file-pdf me-2"></i>PDF
                            </button>
                            <button type="button" class="btn btn-outline-primary" title="Exportar Excel" id="export-excel">
                                <i class="bi bi-file-excel me-2"></i>Excel
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Tabela de Resultados --}}
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="results-table" class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 30%; text-align: left;">Nome</th>
                                    <th style="width: 20%; text-align: left;">Email</th>
                                    <th style="width: 20%; text-align: left;">Telefone</th>
                                    <th style="width: 15%; text-align: left;">CPF/CNPJ</th>
                                    <th style="width: 15%; text-align: left;">Data de Cadastro</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Será preenchido via JavaScript --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Paginação --}}
                @include( 'partials.components.table_paginator' )
            </div>
        </div>
    </div>
@endsection

@section( 'scripts' )
    <!-- Adicione a biblioteca SheetJS -->
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <script src="{{ asset( 'assets/js/modules/table-paginator.js' ) }}"></script>
    <script src="{{ asset( 'assets/js/customer_report.js' ) }}"></script>
@endsection
