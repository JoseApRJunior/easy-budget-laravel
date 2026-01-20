@extends('layouts.app')

@section('title', 'Relatório de Produtos')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Relatório de Produtos"
            icon="box-seam"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Relatórios' => route('provider.reports.index'),
                'Produtos' => '#'
            ]">
            <x-ui.button type="link" :href="route('provider.reports.index')" variant="secondary" icon="arrow-left" label="Voltar" />
        </x-layout.page-header>

        <!-- Filtros de Busca -->
        <x-ui.card class="mb-4">
            <x-slot:header>
                <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
            </x-slot:header>
            <form id="filtersFormProducts" method="GET" action="{{ route('provider.reports.products') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <x-form.filter-field
                            type="date"
                            name="start_date"
                            label="Data Inicial"
                            :value="request('start_date')"
                        />
                    </div>

                    <div class="col-md-3">
                        <x-form.filter-field
                            type="date"
                            name="end_date"
                            label="Data Final"
                            :value="request('end_date')"
                        />
                    </div>

                    <div class="col-md-3">
                        <x-ui.form.input 
                            label="Nome do Produto" 
                            name="name" 
                            id="name"
                            :value="request('name') ?? ''" 
                            placeholder="Digite o nome"
                            wrapper-class="mb-0"
                        />
                    </div>

                    <div class="col-md-3">
                        <x-ui.form.input 
                            label="Código do Produto" 
                            name="code" 
                            id="code"
                            :value="request('code') ?? ''" 
                            placeholder="Digite o código"
                            wrapper-class="mb-0"
                        />
                    </div>

                    <div class="col-md-3">
                        <x-ui.form.input 
                            label="Preço Mínimo" 
                            name="price_min" 
                            id="price_min"
                            :value="request('price_min') ? \App\Helpers\CurrencyHelper::format(request('price_min'), 2, false) : ''" 
                            placeholder="0,00"
                            maxlength="20"
                            class="money-input"
                            wrapper-class="mb-0"
                        />
                    </div>

                    <div class="col-md-3">
                        <x-ui.form.input 
                            label="Preço Máximo" 
                            name="price_max" 
                            id="price_max"
                            :value="request('price_max') ? \App\Helpers\CurrencyHelper::format(request('price_max'), 2, false) : ''" 
                            placeholder="0,00"
                            maxlength="20"
                            class="money-input"
                            wrapper-class="mb-0"
                        />
                    </div>

                    <div class="col-md-3">
                        <x-ui.form.select label="Status" name="status" id="status" wrapper-class="mb-0">
                            <option value="">Todos os Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Ativo</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inativo</option>
                        </x-ui.form.select>
                    </div>

                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <x-ui.button type="submit" variant="primary" icon="search" label="Filtrar" class="flex-grow-1" id="btnFilterProducts" />
                            <x-ui.button type="link" :href="route('provider.reports.products')" variant="secondary" icon="x" label="Limpar" />
                        </div>
                    </div>
                </div>
            </form>
        </x-ui.card>

        {{-- Empty State Inicial --}}
        @if (!request()->hasAny(['name', 'code', 'price_min', 'price_max', 'status']))
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
            <x-ui.card>
                <x-slot:header>
                    <div class="row align-items-center">
                        <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                            <h5 class="mb-0 d-flex align-items-center flex-wrap">
                                <span class="me-2">
                                    <i class="bi bi-list-ul me-1"></i>
                                    <span class="d-none d-sm-inline">Lista de Produtos</span>
                                    <span class="d-sm-none">Produtos</span>
                                </span>
                                <span class="text-muted" style="font-size: 0.875rem;">
                                    @if (isset($products) && $products instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                        ({{ $products->total() }})
                                    @elseif (isset($products))
                                        ({{ $products->count() }})
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
                <div class="p-0">

                    <!-- Mobile View -->
                    <div class="mobile-view">
                        <div class="list-group list-group-flush">
                            @forelse($products ?? [] as $product)
                                <a href="{{ route('provider.products.show', $product->code) }}"
                                    class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-box-seam text-muted me-3 mt-1" style="font-size: 1.5rem;"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1">{{ $product->name }}</div>
                                            <p class="text-muted small mb-2">{{ Str::limit($product->description, 50) }}
                                            </p>
                                            <small class="text-muted">
                                                <span class="text-code">{{ $product->code }}</span>
                                                • {{ \App\Helpers\CurrencyHelper::format($product->price) }}
                                                • {{ $product->active ? 'Ativo' : 'Inativo' }}
                                            </small>
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
                                        <th>Nome</th>
                                        <th>Código</th>
                                        <th>Descrição</th>
                                        <th width="120">Preço</th>
                                        <th width="100">Status</th>
                                        <th width="150" class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products ?? [] as $product)
                                        <tr>
                                            <td>
                                                <div class="item-icon">
                                                    <i class="bi bi-box-seam"></i>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="item-name-cell">
                                                    {{ $product->name }}
                                                </div>
                                            </td>
                                            <td><span class="text-code">{{ $product->code }}</span></td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;"
                                                    title="{{ $product->description }}">
                                                    {{ Str::limit($product->description, 50) }}
                                                </div>
                                            </td>
                                            <td>
                                                <strong>{{ \App\Helpers\CurrencyHelper::format($product->price) }}</strong>
                                            </td>
                                            <td>
                                                <span
                                                    class="modern-badge {{ $product->active ? 'badge-active' : 'badge-inactive' }}">
                                                    {{ $product->active ? 'Ativo' : 'Inativo' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-1">
                                                    <x-ui.button type="link" :href="route('provider.products.show', $product->code)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                    <x-ui.button type="link" :href="route('provider.products.edit', $product->code)" variant="primary" size="sm" icon="pencil-square" title="Editar" />
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
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

                    @if ($products instanceof \Illuminate\Pagination\LengthAwarePaginator && $products->hasPages())
                        @include('partials.components.paginator', [
                            'p' => $products->appends(request()->query()),
                            'show_info' => true,
                        ])
                    @endif
                </div>
            </x-ui.card>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const form = document.getElementById('filtersFormProducts');

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

            const validateDates = (input) => {
                if (!startDate.value || !endDate.value) return true;

                const start = parseDate(startDate.value);
                const end = parseDate(endDate.value);

                if (start && end && start > end) {
                    if (window.easyAlert) {
                        window.easyAlert.warning('A data inicial não pode ser maior que a data final.');
                    } else {
                        alert('A data inicial não pode ser maior que a data final.');
                    }
                    if (input) input.value = '';
                    return false;
                }
                return true;
            };

            startDate.addEventListener('change', function() {
                validateDates(this);
            });
            endDate.addEventListener('change', function() {
                validateDates(this);
            });

            form.addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                }
            });

            // Máscara para valores monetários
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

@push('scripts')
    <!-- Adicione a biblioteca SheetJS -->
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <script src="{{ asset('assets/js/modules/table-paginator.js') }}"></script>
    <script src="{{ asset('assets/js/product_report.js') }}"></script>

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