@extends('layouts.app')

@section('title', 'Relatório de Inventário')

@section('content')
    @php
        // Variável para controlar se há filtros aplicados (excluindo paginação)
        $hasResults = !empty($filters) && collect($filters)->except(['per_page', 'page', 'type'])->filter()->isNotEmpty();
    @endphp

    <div class="container-fluid py-4">
        <!-- Cabeçalho -->
        <x-layout.page-header
            title="Relatório de Inventário"
            icon="clipboard-data"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Inventário' => route('provider.inventory.dashboard'),
                'Relatório' => '#'
            ]">
            <p class="text-muted mb-0">Resumo e detalhes conforme filtros aplicados</p>
        </x-layout.page-header>

        <!-- Card de Filtros -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-filter me-2"></i>Filtros de Busca</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('provider.inventory.report') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="type" class="form-label small fw-bold text-muted text-uppercase">Tipo de Relatório</label>
                                <select class="form-select tom-select" id="type" name="type">
                                    <option value="summary" {{ ($type ?? 'summary') === 'summary' ? 'selected' : '' }}>
                                        Resumo Geral</option>
                                    <option value="movements" {{ ($type ?? '') === 'movements' ? 'selected' : '' }}>
                                        Movimentos</option>
                                    <option value="valuation" {{ ($type ?? '') === 'valuation' ? 'selected' : '' }}>
                                        Valoração</option>
                                    <option value="low-stock" {{ ($type ?? '') === 'low-stock' ? 'selected' : '' }}>
                                        Baixo Estoque</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <x-form.filter-field
                                type="date"
                                name="start_date"
                                id="start_date"
                                label="Período Inicial"
                                :value="$startDate ?? request('start_date')"
                            />
                        </div>
                        <div class="col-md-3">
                            <x-form.filter-field
                                type="date"
                                name="end_date"
                                id="end_date"
                                label="Período Final"
                                :value="$endDate ?? request('end_date')"
                            />
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="per_page" class="form-label small fw-bold text-muted text-uppercase">Por Página</label>
                                <select name="per_page" id="per_page" class="form-select tom-select">
                                    <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                                    <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <x-ui.button type="submit" variant="primary" icon="search" label="Filtrar" id="btnFilterInventory" class="flex-grow-1" />
                                <x-ui.button type="link" :href="route('provider.inventory.report')" variant="secondary" icon="x" label="Limpar" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card de Resultados -->
        <div class="card border-0 shadow-sm">
            <div class="card-header py-3">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                        <h5 class="mb-0 d-flex align-items-center flex-wrap">
                            <span class="me-2">
                                <i class="bi bi-list-ul me-2"></i>
                                <span class="d-none d-sm-inline">Resultados do Relatório</span>
                                <span class="d-sm-none">Resultados</span>
                            </span>
                            <span class="text-muted small fw-normal">
                                @if ($reportData && $reportData->total() > 0)
                                    ({{ $reportData->total() }} itens encontrados)
                                @else
                                    (0 itens)
                                @endif
                            </span>
                        </h5>
                    </div>
                    <div class="col-12 col-lg-4 text-start text-lg-end">
                        <div class="d-flex justify-content-start justify-content-lg-end">
                            <div class="d-flex gap-1" role="group">
                                <x-ui.button type="link" :href="route('provider.inventory.export', array_merge(request()->query(), ['type' => 'pdf', 'report_type' => $type ?? 'summary']))" variant="primary" size="sm" icon="file-earmark-pdf" label="PDF" id="export-pdf" title="Exportar PDF" />
                                <x-ui.button type="link" :href="route('provider.inventory.export', array_merge(request()->query(), ['type' => 'xlsx', 'report_type' => $type ?? 'summary']))" variant="success" size="sm" icon="file-earmark-excel" label="Excel" id="export-excel" title="Exportar Excel" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if ($reportData && $reportData->total() > 0)
                    <!-- Desktop View -->
                    <div class="desktop-view">
                        <div class="table-responsive">
                            <table class="modern-table table mb-0">
                                <thead>
                                    <tr>
                                        @php
                                            $first = $reportData->first();
                                        @endphp
                                        @if (is_array($first))
                                            @foreach (array_keys($first) as $col)
                                                <th>{{ ucfirst(str_replace('_', ' ', $col)) }}</th>
                                            @endforeach
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reportData as $row)
                                        <tr>
                                            @foreach ($row as $val)
                                                <td>{{ is_numeric($val) && !str_contains($val, 'R$') ? \App\Helpers\CurrencyHelper::format((float)$val, 0, false) : $val }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile View -->
                    <div class="mobile-view">
                        <div class="list-group list-group-flush">
                            @foreach ($reportData as $row)
                                <div class="list-group-item py-3">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3 mt-1">
                                            <div class="avatar-circle bg-secondary bg-gradient me-3" style="width: 35px; height: 35px;">
                                                <i class="bi bi-file-text text-white" style="font-size: 0.9rem;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="bg-body-secondary rounded p-3">
                                                @foreach ($row as $key => $val)
                                                    <div class="d-flex justify-content-between align-items-center mb-2 last-child-mb-0">
                                                        <span class="small fw-bold text-muted text-uppercase me-2" style="font-size: 0.7rem;">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                        <span class="small text-body text-end">{{ is_numeric($val) && !str_contains($val, 'R$') ? \App\Helpers\CurrencyHelper::format((float)$val, 0, false) : $val }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if ($reportData->hasPages())
                        @include('partials.components.paginator', [
                            'p' => $reportData->appends(request()->query()),
                            'show_info' => true
                        ])
                    @endif
                @else
                    <div class="p-5 text-center">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-{{ $hasResults ? 'search' : 'filter' }} text-muted opacity-50 fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-dark">
                            {{ $hasResults ? 'Nenhum dado encontrado' : 'Aguardando Filtros' }}
                        </h5>
                        <p class="text-muted mx-auto mb-0" style="max-width: 400px;">
                            @if($hasResults)
                                Não encontramos dados para o período selecionado ou com os filtros aplicados.
                                <br>
                                <a href="{{ route('provider.inventory.report') }}" class="text-primary text-decoration-none small mt-2 d-inline-block">
                                    <i class="bi bi-x-circle me-1"></i>Limpar filtros
                                </a>
                            @else
                                Selecione os parâmetros acima e clique em <strong>Filtrar</strong> para gerar o relatório.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Ações do Footer -->
        <div class="mt-4 pb-2">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-auto">
                    <x-ui.back-button index-route="provider.inventory.dashboard" class="w-100 w-md-auto px-md-3" />
                </div>
                <div class="col-12 col-md text-center d-none d-md-block">
                    <small class="text-muted">
                        Relatório gerado em: {{ now()->format('d/m/Y H:i') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const form = startDate ? startDate.closest('form') : null;

    if (!form || !startDate || !endDate) return;

    const parseDate = (str) => {
        if (!str) return null;
        // Suporta YYYY-MM-DD (input date) ou DD/MM/AAAA (antigo)
        if (str.includes('-')) {
            const d = new Date(str + 'T00:00:00');
            return isNaN(d.getTime()) ? null : d;
        }
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
});
</script>
@endpush
