@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-people-fill me-2"></i>Clientes
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url( '/provider' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Clientes</li>
                </ol>
            </nav>
        </div>

        <!-- Cards de Ação -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 hover-shadow">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class=" bg-opacity-10 me-3">
                                <i class="bi bi-graph-up "></i>
                            </div>
                            <h5 class="card-title mb-0">Relatório de Clientes</h5>
                        </div>
                        <p class="card-text text-muted flex-grow-1">
                            Visualize todos os clientes cadastrados no sistema.
                        </p>
                        <a href="{{ url( '/provider/reports/customers' ) }}" class="btn btn-primary">
                            Acessar Relatório
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 hover-shadow">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-opacity-10 me-3">
                                <i class="bi bi-person-plus "></i>
                            </div>
                            <h5 class="card-title mb-0">Novo Cliente</h5>
                        </div>
                        <p class="card-text text-muted flex-grow-1">
                            Crie um novo cliente no sistema.
                        </p>
                        <a href="{{ url( '/provider/customers/create' ) }}" class="btn btn-success">
                            Criar Cliente
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Componente de Busca --}}
        <div class="card border-0 rounded-4 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="search-wrapper position-relative">
                            <input type="text" class="form-control form-control-lg rounded-3" id="search"
                                placeholder="Buscar por nome, email, CPF ou CNPJ..." autocomplete="off">
                            <span class="position-absolute top-50 end-0 translate-middle-y pe-3">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2 justify-content-end h-100">
                            <button type="button" id="clearMainSearch"
                                class="btn btn-light btn-lg rounded-3 px-4 flex-grow-1">
                                <i class="bi bi-x-circle me-2"></i>Limpar
                            </button>
                            <button type="submit" id="mainSearch" class="btn btn-primary btn-lg rounded-3 px-4 flex-grow-1">
                                <i class="bi bi-search me-2"></i>Buscar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mensagem Inicial --}}
        <div id="initial-message" class="card border-0 shadow-sm text-center py-1 @if( isset( $customers ) ) d-none @endif">
            <div class="card-body">
                <i class="bi bi-funnel-fill text-primary mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-gray-800 mb-3">Utilize o campo de busca acima</h5>
                <p class="text-muted mb-0">
                    Procure por clientes cadastrados no sistema, por nome, email, CPF ou CNPJ.
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
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">
                            <i class="bi bi-list me-2"></i>Clientes
                        </h2>
                        <span id="results-count" class="text-muted">
                            @if( isset( $customers ) )
                                Mostrando {{ count( $customers ) }} resultados
                            @endif
                        </span>
                    </div>

                    {{-- Tabela de Resultados --}}
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="results-table" class="table table-hover align-middle mb-0 modern-table">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 fw-semibold border-0">Cliente</th>
                                        <th scope="col" class="px-3 py-3 fw-semibold border-0">Documento</th>
                                        <th scope="col" class="px-3 py-3 fw-semibold border-0">E-mail</th>
                                        <th scope="col" class="px-3 py-3 fw-semibold border-0">Telefone</th>
                                        <th scope="col" class="px-3 py-3 fw-semibold border-0">Cadastro</th>
                                        <th scope="col" class="text-center px-4 py-3 fw-semibold border-0">Ações</th>
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
    </div>
    <!-- Modal de Confirmação -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja excluir este cliente?
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Excluir</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'styles' )
    <style>
        .modern-table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .modern-table thead th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .table-row-hover:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
        }

        .document-info,
        .email-info,
        .phone-info {
            line-height: 1.4;
        }

        .date-info {
            text-align: center;
        }

        .btn-group .btn {
            border-radius: 6px;
            margin: 0 2px;
            transition: all 0.2s ease;
        }

        .btn-group .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }

        .search-wrapper:focus-within {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            border-radius: 0.375rem;
        }

        @media (max-width: 768px) {
            .modern-table {
                font-size: 0.875rem;
            }

            .btn-group .btn {
                padding: 0.25rem 0.4rem;
                min-width: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .btn-group .btn i {
                font-size: 0.875rem;
                line-height: 1;
            }

            .modern-table td {
                vertical-align: middle !important;
            }
        }
    </style>
@endpush

@push( 'scripts' )
    <script src="{{ asset( 'assets/js/modules/table-paginator.js' ) }}"></script>
    <script src="{{ asset( 'assets/js/customer.js' ) }}?v={{ time() }}"></script>
@endpush
