@extends('layouts.app')

@section('title', 'Gestão de Serviços')

@section('content')
    <div class="container-fluid">
        {{-- Cabeçalho --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-gear me-2"></i>Serviços
                </h1>
                <p class="text-muted">Lista de todos os serviços registrados no sistema</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Listar</li>
                </ol>
            </nav>
        </div>

        <!-- Filters Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
            </div>
            <div class="card-body">
                @if (!empty($errorMessage))
                    <div class="alert alert-warning" role="alert">
                        {{ $errorMessage }}
                    </div>
                @endif
                @if (!empty($showAllPrompt))
                    <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert">
                        <span>Você não aplicou filtros. Carregar todos os serviços pode retornar muitos registros.</span>
                        <a href="{{ route('provider.services.index', array_merge(request()->query(), ['all' => 1])) }}"
                            class="btn btn-outline-primary btn-sm">Listar todos</a>
                    </div>
                @endif
                <form id="filtersForm" method="GET" action="{{ route('provider.services.index') }}" class="row g-3">
                    <input type="hidden" name="attempt" value="1">
                    <div class="col-md-2">
                        <label for="search" class="form-label">Buscar por Código</label>
                        <input type="text" name="search" id="search" class="form-control"
                            placeholder="Código do serviço" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Todos os Status</option>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status->value }}"
                                    {{ request('status') == $status->value ? 'selected' : '' }}>
                                    {{ $status->getDescription() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="category_id" class="form-label">Categoria</label>
                        <select name="category_id" id="category_id" class="form-select">
                            <option value="">Todas as Categorias</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="date_from" class="form-label">Data Inicial</label>
                        <input type="date" name="date_from" id="date_from" class="form-control"
                            value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-2">
                        <label for="date_to" class="form-label">Data Final</label>
                        <input type="date" name="date_to" id="date_to" class="form-control"
                            value="{{ request('date_to') }}">
                    </div>

                    <div class="col-12">
                        <div class="d-flex gap-2 flex-nowrap">
                            <button type="submit" id="btnFilter" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i>Filtrar
                            </button>
                            <a href="{{ route('provider.services.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x me-1"></i>Limpar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Card de Tabela --}}
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                        <h5 class="mb-0 d-flex align-items-center flex-wrap">
                            <span class="me-2">
                                <i class="bi bi-list-ul me-1"></i>
                                <span class="d-none d-sm-inline">Lista de Serviços</span>
                                <span class="d-sm-none">Serviços</span>
                            </span>
                            <span class="text-muted" style="font-size: 0.875rem;">
                                @if ($services instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                    ({{ $services->total() }})
                                @else
                                    ({{ $services->count() }})
                                @endif
                            </span>
                        </h5>
                    </div>
                    <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                        <div class="d-flex justify-content-start justify-content-lg-end gap-2">
                            <a href="{{ route('provider.services.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus"></i>
                                <span class="ms-1">Novo</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                {{-- Desktop View --}}
                <div class="desktop-view">
                    <div class="table-responsive">
                        <table class="modern-table table mb-0">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Orçamento</th>
                                    <th>Cliente</th>
                                    <th>Categoria</th>
                                    <th>Status</th>
                                    <th>Data Criação</th>
                                    <th>Valor Total</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($services as $service)
                                    <tr>
                                        <td><strong>{{ $service->code }}</strong></td>
                                        <td><span class="badge bg-info">{{ $service->budget?->code ?? 'N/A' }}</span></td>
                                        <td>
                                            <strong>{{ $service->budget?->customer?->commonData?->first_name ?? 'Cliente não informado' }}</strong>
                                            <br><small class="text-muted">
                                                @if ($service->budget?->customer?->commonData?->cnpj)
                                                    CNPJ: {{ $service->budget->customer->commonData->cnpj }}
                                                @elseif($service->budget?->customer?->commonData?->cpf)
                                                    CPF: {{ $service->budget->customer->commonData->cpf }}
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            @if ($service->category)
                                                <span class="badge bg-secondary">{{ $service->category->name }}</span>
                                            @else
                                                <span class="text-muted">Sem categoria</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: {{ $service->serviceStatus->getColor() }}; color: white;">
                                                {{ $service->serviceStatus->getDescription() }}
                                            </span>
                                        </td>
                                        <td><small>{{ $service->created_at->format('d/m/Y H:i') }}</small></td>
                                        <td><strong>R$ {{ number_format($service->total, 2, ',', '.') }}</strong></td>
                                        <td class="text-center">
                                            <div class="action-btn-group">
                                                <a href="{{ route('provider.services.show', $service->code) }}" class="action-btn action-btn-view" title="Visualizar">
                                                    <i class="bi bi-eye-fill"></i>
                                                </a>
                                                @if ($service->status->canEdit())
                                                    <a href="{{ route('provider.services.edit', $service->code) }}" class="action-btn action-btn-edit" title="Editar">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </a>
                                                @endif
                                                <button type="button" class="action-btn action-btn-delete" onclick="confirmDelete('{{ $service->code }}')"
                                                    title="Excluir">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                            <br>Nenhum serviço encontrado.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile View --}}
                <div class="mobile-view">
                    <div class="list-group">
                        @forelse($services as $service)
                            <a href="{{ route('provider.services.show', $service->code) }}" class="list-group-item list-group-item-action py-3">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-gear text-muted me-2 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold mb-2">{{ $service->code }}</div>
                                        <div class="d-flex gap-2 flex-wrap mb-2">
                                            <span class="badge" style="background-color: {{ $service->serviceStatus->getColor() }}">
                                                {{ $service->serviceStatus->getDescription() }}
                                            </span>
                                            @if ($service->category)
                                                <span class="badge bg-secondary">{{ $service->category->name }}</span>
                                            @endif
                                        </div>
                                        <div class="small text-muted">
                                            <div>Cliente: {{ $service->budget?->customer?->commonData?->first_name ?? 'N/A' }}</div>
                                            <div>Total: R$ {{ number_format($service->total, 2, ',', '.') }}</div>
                                        </div>
                                    </div>
                                    <i class="bi bi-chevron-right text-muted ms-2"></i>
                                </div>
                            </a>
                        @empty
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                <br>Nenhum serviço encontrado.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @if ($services instanceof \Illuminate\Pagination\LengthAwarePaginator && $services->hasPages())
                @include('partials.components.paginator', ['p' => $services->appends(request()->query()), 'show_info' => true])
            @endif
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir este serviço? Esta ação não pode ser desfeita.</p>
                    <p><strong>Código:</strong> <span id="deleteServiceCode"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmAllModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Listar todos os serviços?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Você não aplicou filtros. Listar todos pode retornar muitos registros.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-confirm-all">Listar todos</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function confirmDelete(serviceCode) {
            document.getElementById('deleteServiceCode').textContent = serviceCode;
            document.getElementById('deleteForm').action = '{{ route('provider.services.destroy', 'REPLACE_ID') }}'
                .replace('REPLACE_ID', serviceCode);

            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        function exportExcel() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('export', 'excel');
            window.open(currentUrl.toString(), '_blank');
        }

        function exportPDF() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('export', 'pdf');
            window.open(currentUrl.toString(), '_blank');
        }

        // Auto-submit form when filters change
        document.getElementById('status').addEventListener('change', function() {
            this.closest('form').submit();
        });

        document.getElementById('category_id').addEventListener('change', function() {
            this.closest('form').submit();
        });

        (function() {
            var form = document.getElementById('filtersForm');
            if (!form) return;
            form.addEventListener('submit', function(e) {
                if (!e.submitter || e.submitter.id !== 'btnFilter') return;
                var search = (form.querySelector('#search')?.value || '').trim();
                var status = (form.querySelector('#status')?.value || '').trim();
                var category = (form.querySelector('#category_id')?.value || '').trim();
                var dateFrom = (form.querySelector('#date_from')?.value || '').trim();
                var dateTo = (form.querySelector('#date_to')?.value || '').trim();
                var hasFilters = !!(search || status || category || dateFrom || dateTo);
                if (!hasFilters) {
                    e.preventDefault();
                    var modalEl = document.getElementById('confirmAllModal');
                    var confirmBtn = modalEl.querySelector('.btn-confirm-all');
                    var modal = new bootstrap.Modal(modalEl);
                    var handler = function() {
                        confirmBtn.removeEventListener('click', handler);
                        var hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'all';
                        hidden.value = '1';
                        form.appendChild(hidden);
                        modal.hide();
                        form.submit();
                    };
                    confirmBtn.addEventListener('click', handler);
                    modal.show();
                }
            });
        })();
    </script>
@endpush
