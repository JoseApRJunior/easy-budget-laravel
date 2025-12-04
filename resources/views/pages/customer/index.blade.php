@extends('layouts.app')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-person-plus me-2"></i>Clientes
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('provider.customers.index') }}">Clientes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Listar</li>
                </ol>
            </nav>
        </div>



        <!-- Filtros de Busca -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
            </div>
            <div class="card-body">
                <form id="filtersFormCustomers" method="GET" action="{{ route('provider.customers.index') }}">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="search">Buscar</label>
                                <input type="text" class="form-control" id="search" name="search"
                                    value="{{ $filters['search'] ?? '' }}" placeholder="Nome, e-mail ou documento">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="active"
                                        {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>
                                        Ativo</option>
                                    <option value="inactive"
                                        {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inativo
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="type">Tipo</label>
                                <select class="form-control" id="type" name="type">
                                    <option value="">Todos</option>
                                    <option value="pf" {{ ($filters['type'] ?? '') === 'pf' ? 'selected' : '' }}>
                                        Pessoa Física
                                    </option>
                                    <option value="pj" {{ ($filters['type'] ?? '') === 'pj' ? 'selected' : '' }}>
                                        Pessoa
                                        Jurídica</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="area_of_activity_id">Área de Atuação</label>
                                <select class="form-control" id="area_of_activity_id" name="area_of_activity_id">
                                    <option value="">Todas</option>
                                    @isset($areas_of_activity)
                                        @foreach ($areas_of_activity as $area)
                                            <option value="{{ $area->id }}"
                                                {{ (string) ($filters['area_of_activity_id'] ?? '') === (string) $area->id ? 'selected' : '' }}>
                                                {{ $area->name }}</option>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="deleted">Registros</label>
                                <select class="form-control" id="deleted" name="deleted">
                                    <option value="">Atuais</option>
                                    <option value="only"
                                        {{ ($filters['deleted'] ?? '') === 'only' ? 'selected' : '' }}>
                                        Deletados</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex gap-2 flex-nowrap">
                                <button type="button" id="btnFilterCustomers" class="btn btn-primary" aria-label="Filtrar">
                                    <i class="bi bi-search me-1" aria-hidden="true"></i>Filtrar
                                </button>
                                <a href="{{ route('provider.customers.index') }}" class="btn btn-secondary"
                                    aria-label="Limpar filtros">
                                    <i class="bi bi-x me-1" aria-hidden="true"></i>Limpar
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>



        {{-- Mensagem Inicial --}}
        <div id="initial-message"
            class="card border-0 shadow-sm text-center py-1 @if (isset($customers)) d-none @endif">
            <div class="card-body">
                <i class="bi bi-funnel-fill text-primary mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-gray-800 mb-3">Utilize os filtros acima</h5>
                <p class="text-muted mb-0">Procure por clientes usando busca, status, tipo e área de atuação.</p>
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
        <div id="results-container" class="{{ isset($customers) ? '' : 'd-none' }}">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-1"></i> Lista de Clientes
                        @if (isset($customers) && $customers instanceof \Illuminate\Pagination\LengthAwarePaginator)
                            ({{ $customers->total() }} registros)
                        @elseif(isset($customers))
                            ({{ $customers->count() }} registros)
                        @endif
                    </h5>
                    <a href="{{ route('provider.customers.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus me-1" aria-hidden="true"></i>Novo Cliente
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="results-table" class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Documento</th>
                                    <th>E-mail</th>
                                    <th>Telefone</th>
                                    <th>Cadastro</th>
                                    <th>Status</th>
                                    <th class="text-center text-nowrap">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Preenchido via JavaScript --}}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    @if (isset($customers) && $customers instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <div class="d-flex justify-content-center">
                            {{ $customers->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div id="pagination" class="pagination justify-content-center"></div>
                        <div id="pagination-info" class="text-center text-muted small"></div>
                    @endif
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

    <div class="modal fade" id="confirmAllCustomersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Listar todos os clientes?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Você não aplicou filtros. Listar todos pode retornar muitos registros.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-confirm-all-customers">Listar todos</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/modules/table-paginator.js') }}"></script>
    <script src="{{ asset('assets/js/customer.js') }}?v={{ time() }}"></script>
@endpush
