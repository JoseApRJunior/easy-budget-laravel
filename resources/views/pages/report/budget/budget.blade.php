@extends('layouts.app')

@section('title', 'Relatório de Orçamentos')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Relatório de Orçamentos"
            icon="file-earmark-bar-graph"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Relatórios' => route('provider.reports.index'),
                'Orçamentos' => '#'
            ]">
            <x-ui.button type="link" :href="route('provider.reports.index')" variant="secondary" icon="arrow-left" label="Voltar" />
        </x-layout.page-header>

        <!-- Filtros de Busca -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
            </div>
            <div class="card-body">
                <form id="filtersFormBudgets" method="GET" action="{{ route('provider.reports.budgets') }}">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="code">Nº Orçamento</label>
                                <input type="text" class="form-control" id="code" name="code"
                                    value="{{ request('code') ?? '' }}" placeholder="Digite o número">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <x-form.filter-field
                                type="date"
                                name="start_date"
                                label="Data Inicial"
                                :value="request('start_date')"
                            />
                        </div>

                        <div class="col-md-2">
                            <x-form.filter-field
                                type="date"
                                name="end_date"
                                label="Data Final"
                                :value="request('end_date')"
                            />
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="customer_name">Cliente</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name"
                                    value="{{ request('customer_name') ?? '' }}" placeholder="Nome, CPF ou CNPJ">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="total_min">Valor Mínimo</label>
                                <input type="text" class="form-control money-input" id="total_min" name="total_min"
                                    value="{{ request('total_min') ? number_format(request('total_min'), 2, ',', '.') : '' }}"
                                    placeholder="0,00" maxlength="20">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">Todos os Status</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendente
                                    </option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>
                                        Aprovado</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>
                                        Rejeitado</option>
                                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expirado
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <x-ui.button type="submit" variant="primary" icon="search" label="Filtrar" class="flex-grow-1" id="btnFilterBudgets" />
                                <x-ui.button type="link" :href="route('provider.reports.budgets')" variant="outline-secondary" icon="x" label="Limpar" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Empty State Inicial --}}
        @if (!request()->hasAny(['code', 'start_date', 'end_date', 'customer_name', 'total_min', 'status']))
            <div class="card border-0 shadow-sm text-center py-4">
                <div class="card-body">
                    <i class="bi bi-funnel-fill text-primary mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-gray-800 mb-3">Utilize os filtros acima para gerar o relatório</h5>
                    <p class="text-muted mb-3">
                        Configure os critérios desejados e clique em "Filtrar" para visualizar os resultados
                    </p>
                    <a href="{{ route('provider.budgets.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Criar Primeiro Orçamento
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
                                    <span class="d-none d-sm-inline">Lista de Orçamentos</span>
                                    <span class="d-sm-none">Orçamentos</span>
                                </span>
                                <span class="text-muted" style="font-size: 0.875rem;">
                                    @if (isset($budgets) && $budgets instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                        ({{ $budgets->total() }})
                                    @elseif (isset($budgets))
                                        ({{ $budgets->count() }})
                                    @endif
                                </span>
                            </h5>
                        </div>
                        <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                            <div class="d-flex justify-content-start justify-content-lg-end">
                                <div class="d-flex gap-2">
                                    <x-ui.button type="button" variant="primary" size="sm" icon="file-earmark-pdf" label="PDF" id="export-pdf" title="Exportar PDF" />
                                    <x-ui.button type="button" variant="success" size="sm" icon="file-earmark-excel" label="Excel" id="export-excel" title="Exportar Excel" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">

                    <!-- Mobile View -->
                    <div class="mobile-view">
                        <div class="list-group list-group-flush">
                            @forelse($budgets ?? [] as $budget)
                                <a href="{{ route('provider.budgets.show', $budget->code) }}"
                                    class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-file-earmark-bar-graph text-muted me-3 mt-1"
                                            style="font-size: 1.5rem;"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1">{{ $budget->code }}</div>
                                            <p class="text-muted small mb-2">
                                                {{ $budget->customer->name ?? 'Cliente não informado' }}</p>
                                            <small class="text-muted">
                                                <span class="text-code">{{ $budget->created_at->format('d/m/Y') }}</span>
                                                • {{ \App\Helpers\CurrencyHelper::format($budget->total) }}
                                                •
                                                {{ is_string($budget->status) ? ucfirst($budget->status) : $budget->status->value }}
                                            </small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted ms-2"></i>
                                    </div>
                                </a>
                            @empty
                                <div class="p-4 text-center text-muted">
                                    <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                    <br>
                                    <strong>Nenhum orçamento encontrado</strong>
                                    <br>
                                    <small>Ajuste os filtros ou <a href="{{ route('provider.budgets.create') }}">crie um
                                            novo orçamento</a></small>
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
                                        <th width="50"><i class="bi bi-file-earmark-bar-graph"
                                                aria-hidden="true"></i></th>
                                        <th>Nº Orçamento</th>
                                        <th>Cliente</th>
                                        <th>Descrição</th>
                                        <th width="100">Data Criação</th>
                                        <th width="100">Vencimento</th>
                                        <th width="120">Valor Total</th>
                                        <th width="100">Status</th>
                                        <th width="150" class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($budgets ?? [] as $budget)
                                        <tr>
                                            <td>
                                                <div class="item-icon">
                                                    <i class="bi bi-file-earmark-bar-graph"></i>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="item-name-cell">
                                                    <span class="text-code">{{ $budget->code }}</span>
                                                </div>
                                            </td>
                                            <td>{{ $budget->customer->name ?? 'Cliente não informado' }}</td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;"
                                                    title="{{ $budget->description }}">
                                                    {{ $budget->description ?? 'Sem descrição' }}
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $budget->created_at->format('d/m/Y') }}
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $budget->due_date ? $budget->due_date->format('d/m/Y') : '—' }}
                                                </small>
                                            </td>
                                            <td>
                                                <strong>{{ \App\Helpers\CurrencyHelper::format($budget->total) }}</strong>
                                            </td>
                                            <td>
                                                <span
                                                    class="modern-badge {{ $budget->status == 'approved' ? 'badge-active' : ($budget->status == 'pending' ? 'badge-warning' : 'badge-inactive') }}">
                                                    {{ is_string($budget->status) ? ucfirst($budget->status) : $budget->status->value }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-1">
                                                    <x-ui.button type="link" :href="route('provider.budgets.show', $budget->code)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                    <x-ui.button type="link" :href="route('provider.budgets.edit', $budget->code)" variant="primary" size="sm" icon="pencil-square" title="Editar" />
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">
                                                <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                                <br>
                                                <strong>Nenhum orçamento encontrado</strong>
                                                <br>
                                                <small>Ajuste os filtros ou <a
                                                        href="{{ route('provider.budgets.create') }}">crie um novo
                                                        orçamento</a></small>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($budgets instanceof \Illuminate\Pagination\LengthAwarePaginator && $budgets->hasPages())
                        @include('partials.components.paginator', [
                            'p' => $budgets->appends(request()->query()),
                            'show_info' => true,
                        ])
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <!-- Adicione a biblioteca SheetJS -->
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <script src="{{ asset('assets/js/modules/table-paginator.js') }}"></script>
    <script src="{{ asset('assets/js/budget.js') }}"></script>
    <script src="{{ asset('assets/js/budget_report.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const form = document.getElementById('filtersFormBudgets');

            if (!form || !startDate || !endDate) return;

            if (typeof VanillaMask !== 'undefined') {
                new VanillaMask('start_date', 'date');
                new VanillaMask('end_date', 'date');
            }

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

        function updatePerPage(value) {
            const url = new URL(window.location);
            url.searchParams.set('per_page', value);
            window.location.href = url.toString();
        }
    </script>
@endpush
