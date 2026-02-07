@extends('layouts.app')

@section('title', 'Relatório de Serviços')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Relatório de Serviços"
            icon="tools"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Relatórios' => route('provider.reports.index'),
                'Serviços' => '#'
            ]">
            <x-ui.button type="link" :href="route('provider.reports.index')" variant="secondary" icon="arrow-left" label="Voltar" />
        </x-layout.page-header>

        <!-- Filtros de Busca -->
        <x-ui.card class="mb-4">
            <x-slot:header>
                <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
            </x-slot:header>
            
            <form id="filtersFormServices" method="GET" action="{{ route('provider.reports.services') }}">
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
                            name="name" 
                            label="Nome do Serviço" 
                            :value="request('name')" 
                            placeholder="Digite o nome" 
                        />
                    </div>

                    <div class="col-md-3">
                        <x-ui.form.input 
                            name="price_min" 
                            label="Preço Mínimo" 
                            class="money-input"
                            :value="request('price_min') ? \App\Helpers\CurrencyHelper::format(request('price_min'), 2, false) : ''" 
                            placeholder="0,00" 
                            maxlength="20"
                        />
                    </div>

                    <div class="col-md-3">
                        <x-ui.form.input 
                            name="price_max" 
                            label="Preço Máximo" 
                            class="money-input"
                            :value="request('price_max') ? \App\Helpers\CurrencyHelper::format(request('price_max'), 2, false) : ''" 
                            placeholder="0,00" 
                            maxlength="20"
                        />
                    </div>

                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <x-ui.button type="submit" variant="primary" icon="search" label="Filtrar" class="flex-grow-1" id="btnFilterServices" />
                            <x-ui.button type="link" :href="route('provider.reports.services')" variant="secondary" icon="x" label="Limpar" />
                        </div>
                    </div>
                </div>
            </form>
        </x-ui.card>

        {{-- Empty State Inicial --}}
        @if (!request()->hasAny(['name', 'price_min', 'price_max']))
            <x-ui.card class="border-0 shadow-sm text-center py-4">
                <i class="bi bi-funnel-fill text-primary mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-gray-800 mb-3">Utilize os filtros acima para gerar o relatório</h5>
                <p class="text-muted mb-3">
                    Configure os critérios desejados e clique em "Filtrar" para visualizar os resultados
                </p>
                <x-ui.button type="link" :href="route('provider.services.create')" variant="primary" icon="plus" label="Criar Primeiro Serviço" />
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
                                            {{ \App\Helpers\CurrencyHelper::format($service->price) }}
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
                                            <strong>{{ \App\Helpers\CurrencyHelper::format($service->price) }}</strong>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <x-ui.button type="link" :href="route('provider.services.show', $service->code)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                <x-ui.button type="link" :href="route('provider.services.edit', $service->code)" variant="primary" size="sm" icon="pencil-square" title="Editar" />
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
            </x-ui.card>
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

        document.addEventListener('DOMContentLoaded', function() {
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

            // Validação de Datas
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const form = document.getElementById('filtersFormServices');

            if (!startDate || !endDate || !form) return;

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
                const startVal = startDate.value;
                const endVal = endDate.value;

                if (!startVal || !endVal) return true;

                const start = parseDate(startVal);
                const end = parseDate(endVal);

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
        });
    </script>
@endpush
