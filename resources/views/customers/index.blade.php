@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 text-gray-800">
                    <i class="bi bi-people-fill me-2"></i>Clientes
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url( '/provider' ) }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Clientes</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-plus-circle me-2"></i>Novo Cliente
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="{{ route( 'provider.customers.create-pessoa-fisica' ) }}">
                                <i class="bi bi-person me-2"></i>Pessoa Física
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route( 'provider.customers.create-pessoa-juridica' ) }}">
                                <i class="bi bi-building me-2"></i>Pessoa Jurídica
                            </a>
                        </li>
                    </ul>
                </div>
                <a href="{{ url( '/provider/reports/customers' ) }}" class="btn btn-outline-primary">
                    <i class="bi bi-graph-up me-2"></i>Relatórios
                </a>
            </div>
        </div>

        <!-- Tabela de Clientes -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i>
                    Lista de Clientes
                </h5>
                @if( isset( $customers ) && $customers->count() > 0 )
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">
                            Total: {{ $customers->total() ?? 0 }} clientes
                        </small>
                    </div>
                @endif
            </div>
            <div class="card-body p-0">
                @if( isset( $customers ) && $customers->count() > 0 )
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Cliente</th>
                                    <th>Tipo</th>
                                    <th>Contato</th>
                                    <th>Status</th>
                                    <th>Data de Cadastro</th>
                                    <th width="120">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach( $customers as $customer )
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="avatar-sm bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3">
                                                    @if( $customer->isCompany() )
                                                        <i class="bi bi-building text-primary"></i>
                                                    @else
                                                        <i class="bi bi-person text-primary"></i>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-medium">
                                                        {{ $customer->isCompany() ? $customer->commonData?->company_name : ( $customer->commonData?->first_name . ' ' . $customer->commonData?->last_name ) }}
                                                    </div>
                                                    @if( $customer->isCompany() )
                                                        <small class="text-muted">{{ $customer->commonData?->fantasy_name }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-{{ $customer->isCompany() ? 'success' : 'info' }}-subtle text-{{ $customer->isCompany() ? 'success' : 'info' }}">
                                                {{ $customer->isCompany() ? 'Pessoa Jurídica' : 'Pessoa Física' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="contact-info">
                                                @if( $customer->contact?->email_personal )
                                                    <div class="mb-1">
                                                        <i class="bi bi-envelope me-1"></i>
                                                        <small>{{ $customer->contact->email_personal }}</small>
                                                    </div>
                                                @endif
                                                @if( $customer->contact?->phone_personal )
                                                    <div>
                                                        <i class="bi bi-telephone me-1"></i>
                                                        <small>{{ $customer->contact->phone_personal }}</small>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-{{ $customer->status === 'active' ? 'success' : ( $customer->status === 'inactive' ? 'secondary' : 'warning' ) }}">
                                                {{ ucfirst( $customer->status ) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <small>{{ \Carbon\Carbon::parse( $customer->created_at )->format( 'd/m/Y' ) }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route( 'provider.customers.show', $customer ) }}"
                                                    class="btn btn-sm btn-outline-primary" title="Visualizar">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route( 'provider.customers.edit', $customer ) }}"
                                                    class="btn btn-sm btn-outline-warning" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if( isset( $customers ) && method_exists( $customers, 'links' ) )
                        <div class="card-footer">
                            {{ $customers->appends( request()->query() )->links() }}
                        </div>
                    @endif

                @else
                    <div class="text-center py-5">
                        <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">Nenhum cliente encontrado</h5>
                        <p class="text-muted">Comece criando um novo cliente ou ajuste os filtros de busca.</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="{{ route( 'provider.customers.create-pessoa-fisica' ) }}" class="btn btn-primary">
                                <i class="bi bi-person-plus me-2"></i>Novo Cliente PF
                            </a>
                            <a href="{{ route( 'provider.customers.create-pessoa-juridica' ) }}" class="btn btn-success">
                                <i class="bi bi-building-add me-2"></i>Novo Cliente PJ
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .avatar-sm {
            width: 2.5rem;
            height: 2.5rem;
        }

        .contact-info small {
            line-height: 1.2;
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

        .dropdown-item:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }

        @media (max-width: 768px) {
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
        }
    </style>

    {{-- Filtros Avançados --}}
    <div class="card border-0 rounded-4 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route( 'provider.customers.index' ) }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <div class="search-wrapper position-relative">
                        <input type="text" name="search" class="form-control" placeholder="Nome, e-mail, CPF ou CNPJ"
                            value="{{ request( 'search' ) }}" autocomplete="off">
                        <span class="position-absolute top-50 end-0 translate-middle-y pe-3">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <select name="type" class="form-select">
                        <option value="">Todos</option>
                        <option value="pessoa_fisica" {{ request( 'type' ) == 'pessoa_fisica' ? 'selected' : '' }}>
                            Pessoa Física
                        </option>
                        <option value="pessoa_juridica" {{ request( 'type' ) == 'pessoa_juridica' ? 'selected' : '' }}>
                            Pessoa Jurídica
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="active" {{ request( 'status' ) == 'active' ? 'selected' : '' }}>
                            Ativo
                        </option>
                        <option value="inactive" {{ request( 'status' ) == 'inactive' ? 'selected' : '' }}>
                            Inativo
                        </option>
                        <option value="prospect" {{ request( 'status' ) == 'prospect' ? 'selected' : '' }}>
                            Prospect
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Ordenar por</label>
                    <select name="sort" class="form-select">
                        <option value="name" {{ request( 'sort' ) == 'name' ? 'selected' : '' }}>
                            Nome
                        </option>
                        <option value="created_at" {{ request( 'sort' ) == 'created_at' ? 'selected' : '' }}>
                            Data de Cadastro
                        </option>
                        <option value="email" {{ request( 'sort' ) == 'email' ? 'selected' : '' }}>
                            E-mail
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Ordem</label>
                    <select name="direction" class="form-select">
                        <option value="asc" {{ request( 'direction' ) == 'asc' ? 'selected' : '' }}>
                            Ascendente
                        </option>
                        <option value="desc" {{ request( 'direction' ) == 'desc' ? 'selected' : '' }}>
                            Descendente
                        </option>
                    </select>
                </div>
                <div class="col-md-12">
                    <div class="d-flex gap-2 justify-content-between align-items-end">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>Filtrar
                            </button>
                            <a href="{{ route( 'provider.customers.index' ) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>Limpar
                            </a>
                        </div>
                        <small class="text-muted">
                            Mostrando {{ $customers->total() ?? 0 }} resultados
                        </small>
                    </div>
                </div>
            </form>
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
