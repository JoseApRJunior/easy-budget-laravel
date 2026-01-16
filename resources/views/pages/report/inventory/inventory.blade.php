@extends('layouts.app')

@section('title', 'Relatório de Estoque')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Relatório de Estoque"
            icon="boxes"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Relatórios' => route('provider.reports.index'),
                'Estoque' => '#'
            ]">
            <x-ui.button type="link" :href="route('provider.reports.index')" variant="secondary" icon="arrow-left" label="Voltar" />
        </x-layout.page-header>

        <!-- Filtros de Busca -->
        <x-ui.card class="mb-4">
            <x-slot:header>
                <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
            </x-slot:header>
            
            <form id="filtersFormInventory" method="GET" action="{{ route('provider.inventory.report') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <x-ui.form.input 
                            name="product_name" 
                            label="Nome do Produto" 
                            :value="request('product_name')" 
                            placeholder="Digite o nome" 
                        />
                    </div>

                    <div class="col-md-3">
                        <x-ui.form.select 
                            name="status" 
                            label="Status do Estoque" 
                            :options="[
                                'in_stock' => 'Em Estoque',
                                'low_stock' => 'Estoque Baixo',
                                'out_of_stock' => 'Sem Estoque'
                            ]"
                            :selected="request('status')"
                            placeholder="Todos os Status"
                        />
                    </div>

                    <div class="col-md-3">
                        <x-ui.form.input 
                            type="number"
                            name="min_quantity" 
                            label="Quantidade Mínima" 
                            :value="request('min_quantity')" 
                            placeholder="0" 
                            min="0"
                        />
                    </div>

                    <div class="col-md-3">
                        <x-ui.form.input 
                            type="number"
                            name="max_quantity" 
                            label="Quantidade Máxima" 
                            :value="request('max_quantity')" 
                            placeholder="0" 
                            min="0"
                        />
                    </div>

                    <div class="col-12">
                        <div class="d-flex gap-2 flex-nowrap">
                            <x-ui.button type="submit" variant="primary" icon="search" label="Filtrar" id="btnFilterInventory" />
                            <x-ui.button type="link" :href="route('provider.inventory.report')" variant="secondary" icon="x" label="Limpar" />
                        </div>
                    </div>
                </div>
            </form>
        </x-ui.card>

        <!-- Empty State Inicial -->
        @if (!request()->hasAny(['product_name', 'status', 'min_quantity', 'max_quantity']))
            <x-ui.card class="border-0 shadow-sm text-center py-4">
                <i class="bi bi-funnel-fill text-primary mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-gray-800 mb-3">Utilize os filtros acima para gerar o relatório</h5>
                <p class="text-muted mb-3">
                    Configure os critérios desejados e clique em "Filtrar" para visualizar os resultados
                </p>
                <x-ui.button type="link" :href="route('provider.products.create')" variant="primary" icon="plus" label="Criar Primeiro Produto" />
            </x-ui.card>
        @else
            <!-- Resultados -->
            <x-ui.card class="p-0">
                <x-slot:header>
                    <div class="row align-items-center">
                        <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                            <h5 class="mb-0 d-flex align-items-center flex-wrap">
                                <span class="me-2">
                                    <i class="bi bi-list-ul me-1"></i>
                                    <span class="d-none d-sm-inline">Lista de Produtos em Estoque</span>
                                    <span class="d-sm-none">Estoque</span>
                                </span>
                                <span class="text-muted" style="font-size: 0.875rem;">
                                    @if (isset($inventory) && $inventory instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                        ({{ $inventory->total() }})
                                    @elseif (isset($inventory))
                                        ({{ $inventory->count() }})
                                    @endif
                                </span>
                            </h5>
                        </div>
                        <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                            <div class="d-flex justify-content-start justify-content-lg-end">
                                <div class="d-flex gap-1" role="group">
                                    <x-ui.button type="button" variant="primary" size="sm" icon="file-earmark-pdf" label="PDF" id="export-pdf" title="Exportar PDF" />
                                    <x-ui.button type="button" variant="success" size="sm" icon="file-earmark-excel" label="Excel" id="export-excel" title="Exportar Excel" />
                                </div>
                            </div>
                        </div>
                    </div>
                </x-slot:header>

                <!-- Mobile View -->
                <div class="mobile-view">
                    <div class="list-group list-group-flush">
                        @forelse($inventory ?? [] as $item)
                            <a href="{{ route('provider.products.show', $item->product->code ?? '#') }}"
                                class="list-group-item list-group-item-action py-3">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-box-seam text-muted me-3 mt-1" style="font-size: 1.5rem;"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold mb-1">
                                            {{ $item->product->name ?? 'Produto não encontrado' }}</div>
                                        <p class="text-muted small mb-2">
                                            {{ $item->product->code ?? 'Código não informado' }}</p>
                                        <small class="text-muted">
                                            <span class="text-code">Quantidade: {{ $item->quantity }}</span>
                                            • Mínimo: {{ $item->min_quantity }}
                                            @if ($item->max_quantity)
                                                • Máximo: {{ $item->max_quantity }}
                                            @endif
                                        </small>
                                        <div class="mt-2">
                                            @if ($item->quantity <= $item->min_quantity)
                                                <span class="badge bg-danger">Estoque Baixo</span>
                                            @elseif($item->quantity == 0)
                                                <span class="badge bg-dark">Sem Estoque</span>
                                            @else
                                                <span class="badge bg-success">Em Estoque</span>
                                            @endif
                                        </div>
                                    </div>
                                    <i class="bi bi-chevron-right text-muted ms-2"></i>
                                </div>
                            </a>
                        @empty
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                <br>
                                <strong>Nenhum produto encontrado</strong>
                                <br>
                                <small>Ajuste os filtros ou <a href="{{ route('provider.products.create') }}">cadastre
                                        um novo produto</a></small>
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
                                    <th width="50"><i class="bi bi-box-seam" aria-hidden="true"></i></th>
                                    <th>Produto</th>
                                    <th>Código</th>
                                    <th width="100">Quantidade</th>
                                    <th width="100">Mínimo</th>
                                    <th width="100">Máximo</th>
                                    <th width="120">Status</th>
                                    <th width="150" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventory ?? [] as $item)
                                    <tr>
                                        <td>
                                            <div class="item-icon">
                                                <i class="bi bi-box-seam"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="item-name-cell">
                                                {{ $item->product->name ?? 'Produto não encontrado' }}
                                            </div>
                                        </td>
                                        <td><span class="text-code">{{ $item->product->code ?? 'N/A' }}</span></td>
                                        <td>
                                            <strong>{{ $item->quantity }}</strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $item->min_quantity }}
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $item->max_quantity ?? '—' }}
                                            </small>
                                        </td>
                                        <td>
                                            @if ($item->quantity <= $item->min_quantity)
                                                <span class="modern-badge badge-warning">Estoque Baixo</span>
                                            @elseif($item->quantity == 0)
                                                <span class="modern-badge badge-inactive">Sem Estoque</span>
                                            @else
                                                <span class="modern-badge badge-active">Em Estoque</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <x-ui.button type="link" :href="route('provider.products.show', $item->product->code ?? '#')" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                <x-ui.button type="link" :href="route('provider.products.edit', $item->product->code ?? '#')" variant="primary" size="sm" icon="pencil-square" title="Editar" />
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                            <br>
                                            <strong>Nenhum produto encontrado</strong>
                                            <br>
                                            <small>Ajuste os filtros ou <a
                                                    href="{{ route('provider.products.create') }}">cadastre um novo
                                                    produto</a></small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($inventory instanceof \Illuminate\Pagination\LengthAwarePaginator && $inventory->hasPages())
                    @include('partials.components.paginator', [
                        'p' => $inventory->appends(request()->query()),
                        'show_info' => true,
                    ])
                @endif
            </x-ui.card>
        @endif
    </div>
@endsection

@push('scripts')
    <!-- Adicione a biblioteca SheetJS -->
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <script src="{{ asset('assets/js/modules/table-paginator.js') }}"></script>
    <script src="{{ asset('assets/js/inventory_report.js') }}"></script>

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
