@extends('layouts.app')

@section('title', 'Relatório de Serviços')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-tools me-2"></i>
                    Relatório de Serviços
                </h1>
                <p class="text-muted">Visualize e analise todos os serviços prestados no sistema</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('provider.reports.index') }}">Relatórios</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Serviços</li>
                </ol>
            </nav>
        </div>

        <!-- Filtros de Busca -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
            </div>
            <div class="card-body">
                <form id="filtersFormServices" method="GET" action="{{ route('provider.reports.services') }}">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="name">Nome do Serviço</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="{{ request('name') ?? '' }}" placeholder="Digite o nome">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="price_min">Preço Mínimo</label>
                                <input type="text" class="form-control money-input" id="price_min" name="price_min"
                                    value="{{ request('price_min') ? number_format(request('price_min'), 2, ',', '.') : '' }}"
                                    placeholder="0,00" maxlength="20">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="price_max">Preço Máximo</label>
                                <input type="text" class="form-control money-input" id="price_max" name="price_max"
                                    value="{{ request('price_max') ? number_format(request('price_max'), 2, ',', '.') : '' }}"
                                    placeholder="0,00" maxlength="20">
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex gap-2 flex-nowrap">
                                <button type="submit" id="btnFilterServices" class="btn btn-primary" aria-label="Filtrar">
                                    <i class="bi bi-search me-1" aria-hidden="true"></i>Filtrar
                                </button>
                                <a href="{{ route('provider.reports.services') }}" class="btn btn-secondary"
                                    aria-label="Limpar filtros">
                                    <i class="bi bi-x me-1" aria-hidden="true"></i>Limpar
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Empty State Inicial --}}
        @if (!request()->hasAny(['name', 'price_min', 'price_max']))
            <div class="card border-0 shadow-sm text-center py-4">
                <div class="card-body">
                    <i class="bi bi-funnel-fill text-primary mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-gray-800 mb-3">Utilize os filtros acima para gerar o relatório</h5>
                    <p class="text-muted mb-3">
                        Configure os critérios desejados e clique em "Filtrar" para visualizar os resultados
                    </p>
                    <a href="{{ route('provider.services.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Criar Primeiro Serviço
                    </a>
                </div>
            </div>
        @else
            <!-- Resultados -->
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
                                    @if (isset($services) && $services instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                        ({{ $services->total() }})
                                    @elseif (isset($services))
                                        ({{ $services->count() }})
                                    @endif
                                </span>
                            </h5>
                        </div>
                        <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                            <div class="d-flex justify-content-start justify-content-lg-end">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" title="Exportar PDF"
                                        id="export-pdf">
                                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm" title="Exportar Excel"
                                        id="export-excel">
                                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">

                    <!-- Mobile View -->
                    <div class="mobile-view">
                        <div class="list-group list-group-flush">
                            @forelse($services ?? [] as $service)
                                <a href="{{ route('provider.services.show', $service) }}"
                                    class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-tools text-muted me-3 mt-1" style="font-size: 1.5rem;"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1">{{ $service->name ?? 'Nome não informado' }}
                                            </div>
                                            <p class="text-muted small mb-2">{{ Str::limit($service->description, 50) }}
                                            </p>
                                            <small class="text-muted">
                                                R$ {{ number_format($service->price, 2, ',', '.') }}
                                            </small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted ms-2"></i>
                                    </div>
                                </a>
                            @empty
                                <div class="p-4 text-center text-muted">
                                    <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                    <br>
                                    <strong>Nenhum serviço encontrado</strong>
                                    <br>
                                    <small>Ajuste os filtros ou <a href="{{ route('provider.services.create') }}">cadastre
                                            um novo serviço</a></small>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Desktop View -->
                    <div class="desktop-view">
                        <div class="table-responsive">
                            <table class="modern-table table mb-0">
                                <thead>
                                    <tr>
                                        <th width="50"><i class="bi bi-tools" aria-hidden="true"></i></th>
                                        <th>Nome</th>
                                        <th>Descrição</th>
                                        <th width="120">Preço</th>
                                        <th width="150" class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($services ?? [] as $service)
                                        <tr>
                                            <td>
                                                <div class="item-icon">
                                                    <i class="bi bi-tools"></i>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="item-name-cell">
                                                    {{ $service->name ?? 'Nome não informado' }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 300px;"
                                                    title="{{ $service->description }}">
                                                    {{ Str::limit($service->description, 60) }}
                                                </div>
                                            </td>
                                            <td>
                                                <strong>R$ {{ number_format($service->price, 2, ',', '.') }}</strong>
                                            </td>
                                            <td>
                                                <div class="action-btn-group">
                                                    <a href="{{ route('provider.services.show', $service) }}"
                                                        class="action-btn action-btn-view" title="Visualizar">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </a>
                                                    <a href="{{ route('provider.services.edit', $service) }}"
                                                        class="action-btn action-btn-edit" title="Editar">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                                <br>
                                                <strong>Nenhum serviço encontrado</strong>
                                                <br>
                                                <small>Ajuste os filtros ou <a
                                                        href="{{ route('provider.services.create') }}">cadastre um novo
                                                        serviço</a></small>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($services instanceof \Illuminate\Pagination\LengthAwarePaginator && $services->hasPages())
                        @include('partials.components.paginator', [
                            'p' => $services->appends(request()->query()),
                            'show_info' => true,
                        ])
                    @endif
                </div>
        @endif
    </div>
@endsection

@push('scripts')
    <!-- Adicione a biblioteca SheetJS -->
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <script src="{{ asset('assets/js/modules/table-paginator.js') }}"></script>
    <script src="{{ asset('assets/js/service_report.js') }}"></script>

    <script>
        function updatePerPage(value) {
            const url = new URL(window.location);
            url.searchParams.set('per_page', value);
            window.location.href = url.toString();
        }

        // Máscara para valores monetários
        document.addEventListener('DOMContentLoaded', function() {
            const moneyInputs = document.querySelectorAll('.money-input');
            moneyInputs.forEach(function(input) {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    value = (value / 100).toFixed(2);
                    value = value.replace('.', ',');
                    e.target.value = value;
                });
            });
        });
    </script>
@endpush
