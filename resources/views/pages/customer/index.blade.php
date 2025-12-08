@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-person-plus me-2"></i>Clientes
                </h1>
                <p class="text-muted">Lista de todos os clientes registrados no sistema</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('provider.customers.dashboard') }}">Clientes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Listar</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-12">
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
                                            <option value="pf"
                                                {{ ($filters['type'] ?? '') === 'pf' ? 'selected' : '' }}>
                                                Pessoa Física
                                            </option>
                                            <option value="pj"
                                                {{ ($filters['type'] ?? '') === 'pj' ? 'selected' : '' }}>
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
                                        <button type="submit" id="btnFilterCustomers" class="btn btn-primary"
                                            aria-label="Filtrar">
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
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                                    <h5 class="mb-0 d-flex align-items-center flex-wrap">
                                        <span class="me-2">
                                            <i class="bi bi-list-ul me-1"></i> 
                                            <span class="d-none d-sm-inline">Lista de Clientes</span>
                                            <span class="d-sm-none">Clientes</span>
                                        </span>
                                        <span class="text-muted" style="font-size: 0.875rem;">
                                            @if (isset($customers) && $customers instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                                ({{ $customers->total() }})
                                            @elseif(isset($customers))
                                                ({{ $customers->count() }})
                                            @endif
                                        </span>
                                    </h5>
                                </div>
                                <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                                    <div class="d-flex justify-content-start justify-content-lg-end">
                                        <a href="{{ route('provider.customers.create') }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus" aria-hidden="true"></i>
                                            <span class="ms-1">Novo</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="desktop-view">
                                <div class="table-responsive">
                                <table id="results-table" class="modern-table table mb-0">
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
                                        @forelse($customers as $customer)
                                            <tr>
                                                <td>
                                                    @if ($customer->commonData)
                                                        @if ($customer->commonData->isCompany())
                                                            <strong>{{ $customer->commonData->company_name }}</strong>
                                                            @if ($customer->commonData->fantasy_name)
                                                                <br><small
                                                                    class="text-muted">{{ $customer->commonData->fantasy_name }}</small>
                                                            @endif
                                                        @else
                                                            <strong>{{ $customer->commonData->first_name }}
                                                                {{ $customer->commonData->last_name }}</strong>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">Nome não informado</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($customer->commonData)
                                                        @if ($customer->commonData->isCompany())
                                                            <span
                                                                class="text-code">{{ $customer->commonData->cnpj ?? 'N/A' }}</span>
                                                        @else
                                                            <span
                                                                class="text-code">{{ $customer->commonData->cpf ?? 'N/A' }}</span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($customer->contact)
                                                        {{ $customer->contact->email_personal ?? ($customer->contact->email_business ?? 'N/A') }}
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($customer->contact)
                                                        {{ $customer->contact->phone_personal ?? ($customer->contact->phone_business ?? 'N/A') }}
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>{{ $customer->created_at->format('d/m/Y') }}</td>
                                                <td>
                                                    @if ($customer->status === 'active')
                                                        <span class="badge bg-success">Ativo</span>
                                                    @else
                                                        <span class="badge bg-danger">Inativo</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-2">
                                                        @if ($customer->deleted_at)
                                                            {{-- Cliente deletado: apenas restaurar --}}
                                                            <form
                                                                action="{{ route('provider.customers.restore', $customer->id) }}"
                                                                method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-success btn-sm"
                                                                    title="Restaurar" aria-label="Restaurar">
                                                                    <i class="bi bi-arrow-counterclockwise"
                                                                        aria-hidden="true"></i>
                                                                </button>
                                                            </form>
                                                        @else
                                                            {{-- Cliente ativo: show, edit, toggle, delete --}}
                                                            <a href="{{ route('provider.customers.show', $customer->id) }}"
                                                                class="btn btn-info btn-sm" title="Visualizar"
                                                                aria-label="Visualizar">
                                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                                            </a>
                                                            <a href="{{ route('provider.customers.edit', $customer->id) }}"
                                                                class="btn btn-warning btn-sm" title="Editar"
                                                                aria-label="Editar">
                                                                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                            </a>
                                                            <form
                                                                action="{{ route('provider.customers.toggle-status', $customer->id) }}"
                                                                method="POST" class="d-inline">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit"
                                                                    class="btn {{ $customer->status === 'active' ? 'btn-warning' : 'btn-success' }} btn-sm"
                                                                    title="{{ $customer->status === 'active' ? 'Desativar' : 'Ativar' }}"
                                                                    aria-label="{{ $customer->status === 'active' ? 'Desativar' : 'Ativar' }}"
                                                                    onclick="return confirm('{{ $customer->status === 'active' ? 'Desativar' : 'Ativar' }} este cliente?')">
                                                                    <i class="bi bi-{{ $customer->status === 'active' ? 'slash-circle' : 'check-lg' }}"
                                                                        aria-hidden="true"></i>
                                                                </button>
                                                            </form>
                                                            <button type="button" class="btn btn-danger btn-sm"
                                                                onclick="confirmDelete({{ $customer->id }})"
                                                                title="Excluir" aria-label="Excluir">
                                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    <i class="bi bi-inbox mb-2" aria-hidden="true"
                                                        style="font-size: 2rem;"></i>
                                                    <br>
                                                    @if (($filters['deleted'] ?? '') === 'only')
                                                        Nenhum cliente deletado encontrado.
                                                        <br>
                                                        <small>Você ainda não deletou nenhum cliente.</small>
                                                    @else
                                                        Nenhum cliente encontrado.
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            @if (isset($customers) && $customers instanceof \Illuminate\Pagination\LengthAwarePaginator && $customers->hasPages())
                                <div class="d-flex justify-content-center">
                                    {{ $customers->appends(request()->query())->links() }}
                                </div>
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
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Fechar"></button>
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

        @push('scripts')
            <script src="{{ asset('assets/js/customer.js') }}?v={{ time() }}"></script>
        @endpush
