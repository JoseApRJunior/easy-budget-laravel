@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-people me-2"></i>Clientes
                </h1>
                <p class="text-muted small">Lista de todos os clientes registrados no sistema</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
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
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="cep">CEP (Proximidade)</label>
                                        <input type="text" class="form-control" id="cep" name="cep"
                                            value="{{ $filters['cep'] ?? '' }}" placeholder="00000-000">
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
                            <!-- Mobile View -->
                            <div class="mobile-view">
                                <div class="list-group list-group-flush">
                                    @forelse($customers as $customer)
                                        <a href="{{ route('provider.customers.show', $customer->id) }}"
                                            class="list-group-item list-group-item-action py-3">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-person text-muted me-2 mt-1"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold mb-2">
                                                        @if ($customer->commonData)
                                                            @if ($customer->commonData->isCompany())
                                                                {{ $customer->commonData->company_name }}
                                                            @else
                                                                {{ $customer->commonData->first_name }} {{ $customer->commonData->last_name }}
                                                            @endif
                                                        @else
                                                            Nome não informado
                                                        @endif
                                                    </div>
                                                    <div class="d-flex gap-2 flex-wrap mb-2">
                                                        @if ($customer->status === 'active')
                                                            <span class="badge bg-success-subtle text-success">Ativo</span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger">Inativo</span>
                                                        @endif
                                                    </div>
                                                    <small class="text-muted">{{ $customer->created_at->format('d/m/Y') }}</small>
                                                </div>
                                                <i class="bi bi-chevron-right text-muted ms-2"></i>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="p-4 text-center text-muted">
                                            <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                            <br>
                                            @if (($filters['deleted'] ?? '') === 'only')
                                                Nenhum cliente deletado encontrado.
                                            @else
                                                Nenhum cliente encontrado.
                                            @endif
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Versão Desktop: Tabela -->
                            <div class="desktop-view">
                                <div class="table-responsive">
                                <table class="modern-table table mb-0">
                                    <thead>
                                        <tr>
                                            <th width="60"><i class="bi bi-person" aria-hidden="true"></i></th>
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
                                                    <div class="item-icon">
                                                        <i class="bi bi-person-fill"></i>
                                                    </div>
                                                </td>
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
                                                    <span class="modern-badge {{ $customer->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                                                        {{ $customer->status === 'active' ? 'Ativo' : 'Inativo' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-btn-group">
                                                        @if ($customer->deleted_at)
                                                            <form action="{{ route('provider.customers.restore', $customer->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-success" title="Restaurar">
                                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                                </button>
                                                            </form>
                                                        @else
                                                            <a href="{{ route('provider.customers.show', $customer->id) }}" class="action-btn action-btn-view" title="Visualizar">
                                                                <i class="bi bi-eye-fill"></i>
                                                            </a>
                                                            <a href="{{ route('provider.customers.edit', $customer->id) }}" class="action-btn action-btn-edit" title="Editar">
                                                                <i class="bi bi-pencil-fill"></i>
                                                            </a>
                                                            <form action="{{ route('provider.customers.toggle-status', $customer->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit" class="action-btn {{ $customer->status === 'active' ? 'action-btn-warning' : 'action-btn-success' }}" title="{{ $customer->status === 'active' ? 'Desativar' : 'Ativar' }}" onclick="return confirm('{{ $customer->status === 'active' ? 'Desativar' : 'Ativar' }} este cliente?')">
                                                                    <i class="bi bi-{{ $customer->status === 'active' ? 'slash-circle' : 'check-lg' }}"></i>
                                                                </button>
                                                            </form>
                                                            <button type="button" class="action-btn action-btn-delete" onclick="confirmDelete({{ $customer->id }})" title="Excluir">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">
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
                    </div>
                    @if (isset($customers) && $customers instanceof \Illuminate\Pagination\LengthAwarePaginator && $customers->hasPages())
                        @include('partials.components.paginator', ['p' => $customers->appends(request()->query()), 'show_info' => true])
                    @endif
                </div>
            </div>
            <!-- Modal de Confirmação -->
            <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            Tem certeza de que deseja excluir o cliente <strong id="deleteCustomerName"></strong>?
                            <br><small class="text-muted">Esta ação não pode ser desfeita.</small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <form id="deleteForm" action="#" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Excluir</button>
                            </form>
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
