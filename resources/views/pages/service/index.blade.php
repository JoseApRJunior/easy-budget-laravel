@extends( 'layouts.app' )

@section( 'title', 'Gestão de Serviços' )

@section( 'content' )
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-tools me-2"></i>
                Serviços
            </h1>
            <p class="text-muted">Lista de todos os serviços registrados no sistema</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route( 'provider.services.index' ) }}">Serviços</a></li>
                <li class="breadcrumb-item active" aria-current="page">Listar</li>
            </ol>
        </nav>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
        </div>
        <div class="card-body">
            @if(!empty($errorMessage))
            <div class="alert alert-warning" role="alert">
                {{ $errorMessage }}
            </div>
            @endif
            @if(!empty($showAllPrompt))
            <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert">
                <span>Você não aplicou filtros. Carregar todos os serviços pode retornar muitos registros.</span>
                <a href="{{ route('provider.services.index', array_merge(request()->query(), ['all' => 1])) }}" class="btn btn-outline-primary btn-sm">Listar todos</a>
            </div>
            @endif
            <form id="filtersForm" method="GET" action="{{ route( 'provider.services.index' ) }}" class="row g-3">
                <input type="hidden" name="attempt" value="1">
                <div class="col-md-2">
                    <label for="search" class="form-label">Buscar por Código</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Código do serviço"
                        value="{{ request( 'search' ) }}">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos os Status</option>
                        @foreach( $statusOptions as $status )
                        <option value="{{ $status->value }}" {{ ( request( 'status' ) == $status->value ) ? 'selected' : '' }}>
                            {{ $status->getDescription() }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="category_id" class="form-label">Categoria</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">Todas as Categorias</option>
                        @foreach( $categories as $category )
                        <option value="{{ $category->id }}" {{ ( request( 'category_id' ) == $category->id ) ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="date_from" class="form-label">Data Inicial</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request( 'date_from' ) }}">
                </div>

                <div class="col-md-2">
                    <label for="date_to" class="form-label">Data Final</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request( 'date_to' ) }}">
                </div>

                <div class="col-12 d-flex gap-2 flex-nowrap">
                    <button type="submit" id="btnFilter" class="btn btn-primary" aria-label="Filtrar">
                        <i class="bi bi-search me-1" aria-hidden="true"></i>Filtrar
                    </button>
                    <a href="{{ route( 'provider.services.index' ) }}" class="btn btn-secondary" aria-label="Limpar filtros">
                        <i class="bi bi-x me-1" aria-hidden="true"></i>Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-1"></i> Lista de Serviços
                @if( $services instanceof \Illuminate\Pagination\LengthAwarePaginator )
                ({{ $services->total() }} registros)
                @else
                ({{ $services->count() }} registros)
                @endif
            </h5>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="exportExcel()">
                    <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Excel
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="exportPDF()">
                    <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>PDF
                </button>
                <a href="{{ route( 'provider.services.create' ) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus me-1" aria-hidden="true"></i>Novo Serviço
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            @if( $services instanceof \Illuminate\Pagination\LengthAwarePaginator && $services->isEmpty() )
            <div class="text-center py-5">
                <i class="bi bi-inbox text-muted mb-3" style="font-size: 2rem;" aria-hidden="true"></i>
                <h5 class="text-muted">Nenhum serviço encontrado</h5>
                <p class="text-muted">Tente ajustar os filtros ou cadastre um novo serviço.</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0">
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
                        @forelse( $services as $service )
                        <tr>
                            <td>
                                <strong>{{ $service->code }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $service->budget?->code ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $service->budget?->customer?->commonData?->first_name ?? 'Cliente não informado' }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        @if( $service->budget?->customer?->commonData?->cnpj )
                                        CNPJ: {{ $service->budget->customer->commonData->cnpj }}
                                        @elseif( $service->budget?->customer?->commonData?->cpf )
                                        CPF: {{ $service->budget->customer->commonData->cpf }}
                                        @endif
                                    </small>
                                </div>
                            </td>
                            <td>
                                @if( $service->category )
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
                            <td>
                                <small>{{ $service->created_at->format( 'd/m/Y H:i' ) }}</small>
                            </td>
                            <td>
                                <strong>R$ {{ number_format( $service->total, 2, ',', '.' ) }}</strong>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route( 'provider.services.show', $service->code ) }}" class="btn btn-info btn-sm" title="Visualizar" aria-label="Visualizar">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                    @if( $service->status->canEdit() )
                                    <a href="{{ route( 'provider.services.edit', $service->code ) }}" class="btn btn-warning btn-sm" title="Editar" aria-label="Editar">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    </a>
                                    @endif
                                    <button type="button" class="btn btn-danger btn-sm" title="Excluir" aria-label="Excluir" onclick="confirmDelete('{{ $service->code }}')">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="bi bi-inbox text-muted mb-2" style="font-size: 2rem;" aria-hidden="true"></i>
                                <br>
                                <span class="text-muted">Nenhum serviço encontrado</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        @if( $services instanceof \Illuminate\Pagination\LengthAwarePaginator && !$services->isEmpty() )
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">
                        Mostrando {{ $services->firstItem() }} a {{ $services->lastItem() }}
                        de {{ $services->total() }} resultados
                    </small>
                </div>
                <div>
                    {{ $services->appends( request()->query() )->links() }}
                </div>
            </div>
        </div>
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
                    @method( 'DELETE' )
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

@push( 'scripts' )
<script>
    function confirmDelete(serviceCode) {
        document.getElementById('deleteServiceCode').textContent = serviceCode;
        document.getElementById('deleteForm').action = '{{ route( "provider.services.destroy", "REPLACE_ID" ) }}'.replace('REPLACE_ID', serviceCode);

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
