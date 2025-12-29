@extends('layouts.app')

@section('title', 'Relatório de Inventário')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <x-page-header
            title="Relatório de Inventário"
            icon="clipboard-data"
            :breadcrumb-items="[
                'Estoque' => route('provider.inventory.index'),
                'Relatório' => '#'
            ]">
            <p class="text-muted mb-0">Resumo e detalhes conforme filtros aplicados</p>
        </x-page-header>

        <!-- Card de Filtros -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('provider.inventory.report') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="type">Tipo de Relatório</label>
                                <select class="form-control" id="type" name="type">
                                    <option value="summary" {{ ($type ?? 'summary') === 'summary' ? 'selected' : '' }}>
                                        Resumo Geral</option>
                                    <option value="movements" {{ ($type ?? '') === 'movements' ? 'selected' : '' }}>
                                        Movimentos</option>
                                    <option value="valuation" {{ ($type ?? '') === 'valuation' ? 'selected' : '' }}>
                                        Valoração</option>
                                    <option value="low-stock" {{ ($type ?? '') === 'low-stock' ? 'selected' : '' }}>Baixo
                                        Estoque</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="start_date">Data Inicial</label>
                                <input type="date" class="form-control" id="start_date" name="start_date"
                                    value="{{ $startDate ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="end_date">Data Final</label>
                                <input type="date" class="form-control" id="end_date" name="end_date"
                                    value="{{ $endDate ?? '' }}">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex gap-2 flex-nowrap">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-1"></i>Filtrar
                                </button>
                                <a href="{{ route('provider.inventory.report') }}" class="btn btn-secondary">
                                    <i class="bi bi-x me-1"></i>Limpar
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card de Resultados -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                        <h5 class="mb-0 d-flex align-items-center flex-wrap">
                            <span class="me-2">
                                <i class="bi bi-list-ul me-1"></i>
                                <span class="d-none d-sm-inline">Resultados do Relatório</span>
                                <span class="d-sm-none">Resultados</span>
                            </span>
                            <span class="text-muted" style="font-size: 0.875rem;">
                                @if (is_iterable($reportData) && count($reportData) > 0)
                                    ({{ count($reportData) }})
                                @else
                                    (0)
                                @endif
                            </span>
                        </h5>
                    </div>
                    <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                        <div class="d-flex justify-content-start justify-content-lg-end">
                            <div class="btn-group" role="group">
                                <a href="{{ route('provider.inventory.export', ['type' => 'pdf', 'report_type' => $type ?? 'summary']) }}"
                                    class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                                </a>
                                <a href="{{ route('provider.inventory.export', ['type' => 'xlsx', 'report_type' => $type ?? 'summary']) }}"
                                    class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-file-earmark-excel me-1"></i>Excel
                                </a>
                                <a href="{{ route('provider.inventory.export', ['type' => 'csv', 'report_type' => $type ?? 'summary']) }}"
                                    class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-filetype-csv me-1"></i>CSV
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if (is_iterable($reportData) && count($reportData) > 0)
                    <div class="table-responsive">
                        <table class="modern-table table mb-0">
                            <thead>
                                <tr>
                                    @php $first = $reportData[0] ?? (is_array($reportData) ? reset($reportData) : null); @endphp
                                    @if (is_array($first))
                                        @foreach (array_keys($first) as $col)
                                            <th>{{ ucfirst(str_replace('_', ' ', $col)) }}</th>
                                        @endforeach
                                    @else
                                        <th>Item</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reportData as $row)
                                    <tr>
                                        @if (is_array($row))
                                            @foreach ($row as $val)
                                                <td>{{ is_numeric($val) ? number_format($val, 2, ',', '.') : $val }}</td>
                                            @endforeach
                                        @else
                                            <td>{{ $row }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-4 text-muted mb-3"></i>
                        <h6 class="text-muted mb-2">Nenhum dado encontrado</h6>
                        <p class="text-muted small mb-0">Ajuste os filtros ou <a
                                href="{{ route('provider.inventory.index') }}">acesse o inventário</a></p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const form = startDate ? startDate.closest('form') : null;

    if (form) {
        form.addEventListener('submit', function(e) {
            if (startDate.value && endDate.value && startDate.value > endDate.value) {
                e.preventDefault();
                if (window.easyAlert) {
                    window.easyAlert.error('A data inicial não pode ser maior que a data final.');
                } else {
                    alert('A data inicial não pode ser maior que a data final.');
                }
                startDate.focus();
            }
        });

        startDate.addEventListener('change', function() {
            if (this.value && endDate.value && this.value > endDate.value) {
                if (window.easyAlert) {
                    window.easyAlert.warning('A data inicial não pode ser maior que a data final.');
                } else {
                    alert('A data inicial não pode ser maior que a data final.');
                }
                this.value = '';
            }
        });

        endDate.addEventListener('change', function() {
            if (this.value && startDate.value && this.value < startDate.value) {
                if (window.easyAlert) {
                    window.easyAlert.warning('A data final não pode ser menor que a data inicial.');
                } else {
                    alert('A data final não pode ser menor que a data inicial.');
                }
                this.value = '';
            }
        });
    }
});
</script>
@endsection
