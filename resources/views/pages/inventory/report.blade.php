@extends('layouts.app')

@section('title', 'Relatório de Inventário')

@section('content')
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-0"><i class="bi bi-clipboard-data me-2"></i>Relatório de Inventário</h1>
      <p class="text-muted mb-0">Resumo e detalhes conforme filtros.</p>
    </div>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('provider.inventory.index') }}">Estoque</a></li>
        <li class="breadcrumb-item active">Relatório</li>
      </ol>
    </nav>
  </div>

  <form method="GET" action="{{ route('provider.inventory.report') }}" class="mb-4">
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Tipo</label>
        <select name="type" class="form-select">
          <option value="summary" {{ ($type ?? 'summary')==='summary' ? 'selected' : '' }}>Resumo</option>
          <option value="movements" {{ ($type ?? '')==='movements' ? 'selected' : '' }}>Movimentos</option>
          <option value="valuation" {{ ($type ?? '')==='valuation' ? 'selected' : '' }}>Valoração</option>
          <option value="low-stock" {{ ($type ?? '')==='low-stock' ? 'selected' : '' }}>Baixo Estoque</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Data Inicial</label>
        <input type="date" name="start_date" value="{{ $startDate ?? '' }}" class="form-control" />
      </div>
      <div class="col-md-3">
        <label class="form-label">Data Final</label>
        <input type="date" name="end_date" value="{{ $endDate ?? '' }}" class="form-control" />
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-primary w-100"><i class="bi bi-search me-2"></i>Filtrar</button>
      </div>
    </div>
  </form>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
      <h6 class="mb-0">Resultados</h6>
      <div class="btn-group">
        <a href="{{ route('provider.inventory.export', ['type'=>'pdf','report_type'=>$type ?? 'summary']) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf me-2"></i>PDF</a>
        <a href="{{ route('provider.inventory.export', ['type'=>'xlsx','report_type'=>$type ?? 'summary']) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-excel me-2"></i>Excel</a>
        <a href="{{ route('provider.inventory.export', ['type'=>'csv','report_type'=>$type ?? 'summary']) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-csv me-2"></i>CSV</a>
      </div>
    </div>
    <div class="card-body">
      @if(is_iterable($reportData) && count($reportData) > 0)
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                @php $first = $reportData[0] ?? (is_array($reportData) ? reset($reportData) : null); @endphp
                @if(is_array($first))
                  @foreach(array_keys($first) as $col)
                    <th>{{ ucfirst(str_replace('_',' ', $col)) }}</th>
                  @endforeach
                @else
                  <th>Item</th>
                @endif
              </tr>
            </thead>
            <tbody>
              @foreach($reportData as $row)
                <tr>
                  @if(is_array($row))
                    @foreach($row as $val)
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
        <div class="text-center py-5"><i class="bi bi-inbox display-4 text-muted mb-3"></i><h6 class="text-muted">Nenhum dado encontrado</h6></div>
      @endif
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>.avatar-circle{width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center}</style>
@endpush