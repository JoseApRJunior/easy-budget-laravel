@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Lista de Clientes"
            icon="people"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Clientes' => route('provider.customers.dashboard'),
                'Lista' => '#'
            ]">
            <p class="text-muted mb-0 small">Lista de todos os clientes registrados no sistema</p>
        </x-layout.page-header>

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
                                        <label for="search" class="form-label small fw-bold text-muted text-uppercase">Buscar</label>
                                        <input type="text" class="form-control" id="search" name="search"
                                            value="{{ $filters['search'] ?? '' }}" placeholder="Nome, e-mail ou documento">
                                    </div>
                                </div>
                               <div class="col-md-2">
                                <div class="form-group">
                                    <label for="active" class="form-label small fw-bold text-muted text-uppercase">Status</label>
                                    <select class="form-select tom-select" id="active" name="active">
                                        @php($selectedActive = $filters['active'] ?? 'active')
                                        <option value="active" {{ $selectedActive === 'active' ? 'selected' : '' }}>
                                            Ativo
                                        </option>
                                        <option value="inactive" {{ $selectedActive === 'inactive' ? 'selected' : '' }}>
                                            Inativo
                                        </option>
                                        <option value="all" {{ $selectedActive === 'all' ? 'selected' : '' }}>
                                            Todos
                                        </option>
                                    </select>
                                </div>
                            </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="type" class="form-label small fw-bold text-muted text-uppercase">Tipo</label>
                                        <select class="form-select tom-select" id="type" name="type">
                                            <option value="">Todos os tipos</option>
                                            <option value="individual"
                                                {{ ($filters['type'] ?? '') === 'individual' ? 'selected' : '' }}>
                                                Pessoa Física
                                            </option>
                                            <option value="company"
                                                {{ ($filters['type'] ?? '') === 'company' ? 'selected' : '' }}>
                                                Pessoa Jurídica</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="area_of_activity" class="form-label small fw-bold text-muted text-uppercase">Área de Atuação</label>
                                        <select class="form-select tom-select" id="area_of_activity" name="area_of_activity">
                                            <option value="">Todas as áreas</option>
                                            @isset($areas_of_activity)
                                                @foreach ($areas_of_activity as $area)
                                                    <option value="{{ $area->slug }}"
                                                        {{ (string) ($filters['area_of_activity'] ?? '') === (string) $area->slug ? 'selected' : '' }}>
                                                        {{ $area->name }}</option>
                                                @endforeach
                                            @endisset
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                <div class="form-group">
                                    <label for="deleted" class="form-label small fw-bold text-muted text-uppercase">Status de Exclusão</label>
                                    <select name="deleted" id="deleted" class="form-select tom-select">
                                        @php($selectedDeleted = $filters['deleted'] ?? 'current')
                                        <option value="current" {{ $selectedDeleted === 'current' ? 'selected' : '' }}>
                                            Atuais
                                        </option>
                                        <option value="only" {{ $selectedDeleted === 'only' ? 'selected' : '' }}>
                                            Deletados
                                        </option>
                                        <option value="all" {{ $selectedDeleted === 'all' ? 'selected' : '' }}>
                                            Todos
                                        </option>
                                    </select>
                                </div>
                            </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="cep" class="form-label small fw-bold text-muted text-uppercase">CEP (Proximidade)</label>
                                        <input type="text" class="form-control" id="cep" name="cep"
                                            value="{{ $filters['cep'] ?? '' }}" placeholder="00000-000" data-mask="00000-000">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="cpf" class="form-label small fw-bold text-muted text-uppercase">CPF</label>
                                        <input type="text" class="form-control" id="cpf" name="cpf"
                                            value="{{ $filters['cpf'] ?? '' }}" placeholder="000.000.000-00" data-mask="000.000.000-00">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="cnpj" class="form-label small fw-bold text-muted text-uppercase">CNPJ</label>
                                        <input type="text" class="form-control" id="cnpj" name="cnpj"
                                            value="{{ $filters['cnpj'] ?? '' }}" placeholder="00.000.000/0000-00" data-mask="00.000.000/0000-00">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="phone" class="form-label small fw-bold text-muted text-uppercase">Telefone</label>
                                        <input type="text" class="form-control" id="phone" name="phone"
                                            value="{{ $filters['phone'] ?? '' }}" placeholder="(00) 00000-0000" data-mask="(00) 00000-0000">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <x-form.filter-field
                                        type="date"
                                        name="start_date"
                                        label="Cadastro inicial"
                                        :value="$filters['start_date'] ?? request('start_date')"
                                    />
                                </div>
                                <div class="col-md-2">
                                    <x-form.filter-field
                                        type="date"
                                        name="end_date"
                                        label="Cadastro final"
                                        :value="$filters['end_date'] ?? request('end_date')"
                                    />
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="per_page" class="form-label small fw-bold text-muted text-uppercase">Por página</label>
                                        <select class="form-select tom-select" id="per_page" name="per_page">
                                            @php($pp = (int) ($filters['per_page'] ?? 10))
                                            <option value="10" {{ $pp === 10 ? 'selected' : '' }}>10</option>
                                            <option value="20" {{ $pp === 20 ? 'selected' : '' }}>20</option>
                                            <option value="50" {{ $pp === 50 ? 'selected' : '' }}>50</option>
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
                                                ({{ count($customers) }})
                                            @endif
                                        </span>
                                    </h5>
                                </div>
                                <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                                    <div class="d-flex justify-content-start justify-content-lg-end gap-2">
                    <div class="dropdown">
                        <x-ui.button variant="outline-secondary" size="sm" icon="download" label="Exportar"
                            class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportDropdown" />
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                            <li>
                                <a class="dropdown-item"
                                    href="{{ route('provider.customers.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}">
                                    <i class="bi bi-file-earmark-excel me-2 text-success"></i> Excel (.xlsx)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item"
                                    href="{{ route('provider.customers.export', array_merge(request()->query(), ['format' => 'pdf'])) }}">
                                    <i class="bi bi-file-earmark-pdf me-2 text-danger"></i> PDF (.pdf)
                                </a>
                            </li>
                        </ul>
                    </div>
                    <x-ui.button type="link" :href="route('provider.customers.create')" size="sm" icon="plus" label="Novo" />
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
                                            <th><i class="bi bi-person" aria-hidden="true"></i></th>
                                            <th>Cliente</th>
                                            <th>Documento</th>
                                            <th class="text-nowrap">E-mail</th>
                                            <th class="text-nowrap">Telefone</th>
                                            <th class="text-nowrap">Cadastro</th>
                                            <th class="text-nowrap">Status</th>
                                            <th class="text-center">Ações</th>
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
                                                                class="text-code">{{ \App\Helpers\MaskHelper::formatCNPJ($customer->commonData->cnpj) }}</span>
                                                        @else
                                                            <span
                                                                class="text-code">{{ \App\Helpers\MaskHelper::formatCPF($customer->commonData->cpf) }}</span>
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
                                                        {{ \App\Helpers\MaskHelper::formatPhone($customer->contact->phone_personal ?? $customer->contact->phone_business) }}
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>{{ $customer->created_at->format('d/m/Y') }}</td>
                                                <td class="text-nowrap">
                                                    <span class="modern-badge {{ $customer->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                                                        {{ $customer->status === 'active' ? 'Ativo' : 'Inativo' }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="action-btn-group">
                                                        @if ($customer->deleted_at)
                                                            <x-ui.button type="link" :href="route('provider.customers.show', $customer->id)" variant="info" icon="eye" title="Visualizar" />
                                                            <x-ui.button variant="success" icon="arrow-counterclockwise"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#restoreModal"
                                                                    data-restore-url="{{ route('provider.customers.restore', $customer->id) }}"
                                                                    data-name="{{ $customer->commonData ? ($customer->commonData->isCompany() ? $customer->commonData->company_name : $customer->commonData->first_name . ' ' . $customer->commonData->last_name) : 'Cliente' }}"
                                                                    title="Restaurar" />
                                                        @else
                                                            <x-ui.button type="link" :href="route('provider.customers.show', $customer->id)" variant="info" icon="eye" title="Visualizar" />
                                                            <x-ui.button type="link" :href="route('provider.customers.edit', $customer->id)" icon="pencil-square" title="Editar" />

                                                            <x-ui.button variant="danger" icon="trash"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#deleteModal"
                                                                    data-delete-url="{{ route('provider.customers.destroy', $customer->id) }}"
                                                                    data-name="{{ $customer->commonData ? ($customer->commonData->isCompany() ? $customer->commonData->company_name : $customer->commonData->first_name . ' ' . $customer->commonData->last_name) : 'Cliente' }}"
                                                                    title="Excluir" />
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">
                                                    <i class="bi bi-inbox mb-2" aria-hidden="true" style="font-size: 2rem;"></i>
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

            <!-- Modal de Restauração -->
            <div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="restoreModalLabel">Confirmar Restauração</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            Tem certeza de que deseja restaurar o cliente <strong id="restoreCustomerName"></strong>?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <form id="restoreForm" action="#" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success">Restaurar</button>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const form = document.getElementById('filtersFormCustomers');

            if (!form || !startDate || !endDate) return;

            const parseDate = (str) => {
                if (!str) return null;
                const parts = str.split('/');
                if (parts.length === 3) {
                    const d = new Date(parts[2], parts[1] - 1, parts[0]);
                    return isNaN(d.getTime()) ? null : d;
                }
                return null;
            };

            const validateDates = () => {
                if (!startDate.value || !endDate.value) return true;

                const start = parseDate(startDate.value);
                const end = parseDate(endDate.value);

                if (start && end && start > end) {
                    const message = 'A data inicial não pode ser maior que a data final.';
                    if (window.easyAlert) {
                        window.easyAlert.warning(message);
                    } else {
                        alert(message);
                    }
                    return false;
                }
                return true;
            };

            form.addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                    return;
                }

                if (startDate.value && !endDate.value) {
                    e.preventDefault();
                    const message = 'Para filtrar por período, informe as datas inicial e final.';
                    if (window.easyAlert) {
                        window.easyAlert.error(message);
                    } else {
                        alert(message);
                    }
                    endDate.focus();
                } else if (!startDate.value && endDate.value) {
                    e.preventDefault();
                    const message = 'Para filtrar por período, informe as datas inicial e final.';
                    if (window.easyAlert) {
                        window.easyAlert.error(message);
                    } else {
                        alert(message);
                    }
                    startDate.focus();
                }
            });
        });
    </script>
@endpush
