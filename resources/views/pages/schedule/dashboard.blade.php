@extends('layouts.app')

@section('title', 'Dashboard de Agendamentos')

@section('content')
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-0">
        <i class="bi bi-calendar-check me-2"></i>Dashboard de Agendamentos
      </h1>
      <p class="text-muted mb-0">Visão geral dos agendamentos com métricas e próximos horários.</p>
    </div>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('provider.schedules.index') }}">Agendamentos</a></li>
        <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
      </ol>
    </nav>
  </div>

  @php
    $total      = $stats['total_schedules'] ?? 0;
    $pending    = $stats['pending_schedules'] ?? 0;
    $confirmed  = $stats['confirmed_schedules'] ?? 0;
    $completed  = $stats['completed_schedules'] ?? 0;
    $cancelled  = $stats['cancelled_schedules'] ?? 0;
    $noShow     = $stats['no_show_schedules'] ?? 0;
    $upcoming   = $stats['upcoming_schedules'] ?? 0;
    $recent     = $stats['recent_upcoming'] ?? collect();
    $breakdown  = $stats['status_breakdown'] ?? [];
    $completionRate = $total > 0 ? number_format(($completed / $total) * 100, 1, ',', '.') : 0;
  @endphp

  <div class="row g-4 mb-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <div class="d-flex align-items-center mb-3">
            <div class="avatar-circle bg-primary bg-gradient me-3"><i class="bi bi-calendar text-white"></i></div>
            <div>
              <h6 class="text-muted mb-1">Total de Agendamentos</h6>
              <h3 class="mb-0">{{ $total }}</h3>
            </div>
          </div>
          <p class="text-muted small mb-0">Agendamentos registrados no tenant.</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <div class="d-flex align-items-center mb-3">
            <div class="avatar-circle bg-info bg-gradient me-3"><i class="bi bi-calendar-date text-white"></i></div>
            <div>
              <h6 class="text-muted mb-1">Próximos</h6>
              <h3 class="mb-0">{{ $upcoming }}</h3>
            </div>
          </div>
          <p class="text-muted small mb-0">Agendamentos futuros ativos.</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <div class="d-flex align-items-center mb-3">
            <div class="avatar-circle bg-success bg-gradient me-3"><i class="bi bi-check2-circle text-white"></i></div>
            <div>
              <h6 class="text-muted mb-1">Concluídos</h6>
              <h3 class="mb-0">{{ $completed }}</h3>
            </div>
          </div>
          <p class="text-muted small mb-0">Finalizados com sucesso.</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <div class="d-flex align-items-center mb-3">
            <div class="avatar-circle bg-secondary bg-gradient me-3"><i class="bi bi-graph-up-arrow text-white"></i></div>
            <div>
              <h6 class="text-muted mb-1">Taxa de Conclusão</h6>
              <h3 class="mb-0">{{ $completionRate }}%</h3>
            </div>
          </div>
          <p class="text-muted small mb-0">Concluídos sobre o total.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-3">
            <div class="avatar-circle bg-primary bg-gradient me-3"><i class="bi bi-bar-chart text-white"></i></div>
            <h6 class="text-muted mb-0">Distribuição por Status</h6>
          </div>
          <div class="chart-container"><canvas id="statusChart" style="max-height: 120px;"></canvas></div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-3">
            <div class="avatar-circle bg-warning bg-gradient me-3"><i class="bi bi-list-check text-white"></i></div>
            <h6 class="text-muted mb-0">Resumo</h6>
          </div>
          <ul class="list-unstyled small text-muted mb-0">
            <li class="mb-2"><i class="bi bi-hourglass-split text-warning me-2"></i>Pendentes: {{ $pending }}</li>
            <li class="mb-2"><i class="bi bi-calendar-check text-primary me-2"></i>Confirmados: {{ $confirmed }}</li>
            <li class="mb-2"><i class="bi bi-x-circle text-danger me-2"></i>Cancelados: {{ $cancelled }}</li>
            <li class="mb-2"><i class="bi bi-slash-circle text-secondary me-2"></i>No-show: {{ $noShow }}</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Próximos Agendamentos</h6>
          <a href="{{ route('provider.schedules.index') }}" class="btn btn-sm btn-outline-primary">Ver Todos</a>
        </div>
        <div class="card-body">
          @if($recent->isNotEmpty())
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Cliente</th>
                    <th>Serviço</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($recent as $sc)
                    <tr>
                      <td>{{ \Carbon\Carbon::parse($sc->start_date_time)->format('d/m/Y') }}</td>
                      <td>{{ \Carbon\Carbon::parse($sc->start_date_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($sc->end_date_time)->format('H:i') }}</td>
                      <td>{{ $sc->service?->customer?->commonData?->first_name ?? 'N/A' }}</td>
                      <td>{{ $sc->service?->description ?? $sc->service?->code }}</td>
                      <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_',' ', $sc->status)) }}</span></td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="text-center py-5">
              <i class="bi bi-calendar2-event display-4 text-muted mb-3"></i>
              <h6 class="text-muted">Nenhum agendamento encontrado</h6>
              <p class="text-muted mb-0">Crie agendamentos para visualizar aqui.</p>
            </div>
          @endif
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent border-0"><h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Insights</h6></div>
        <div class="card-body">
          <ul class="list-unstyled mb-0 small text-muted">
            <li class="mb-2"><i class="bi bi-calendar-check text-primary me-2"></i>Confirme pendentes para evitar no-shows.</li>
            <li class="mb-2"><i class="bi bi-x-circle text-danger me-2"></i>Reagende cancelados quando possível.</li>
          </ul>
        </div>
      </div>
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0"><h6 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Atalhos</h6></div>
        <div class="card-body d-grid gap-2">
          <a href="{{ route('provider.schedules.create', ['service' => 0]) }}" class="btn btn-sm btn-success"><i class="bi bi-plus-circle me-2"></i>Novo Agendamento</a>
          <a href="{{ route('provider.schedules.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-calendar3 me-2"></i>Listar Agendamentos</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .avatar-circle{width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center}
  .chart-container{display:flex;justify-content:center;align-items:center;min-height:120px;width:100%}
  .chart-container canvas{max-width:100% !important;height:auto !important}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
  const statusData = @json($breakdown);
  const labels = [];
  const values = [];
  const colors = [];
  Object.keys(statusData).forEach(k=>{const s=statusData[k];if(s && s.count>0){labels.push(k.replace('_',' ').replace('-',' '));values.push(s.count);colors.push(s.color||'#6c757d');}});
  if(values.length===0){const c=document.querySelector('.chart-container');if(c){c.innerHTML='<p class="text-muted text-center mb-0 small">Nenhum agendamento cadastrado</p>'; } return;}
  const ctx=document.getElementById('statusChart');if(!ctx){return;}
  new Chart(ctx,{type:'doughnut',data:{labels:labels,datasets:[{data:values,backgroundColor:colors,borderWidth:2,borderColor:'#ffffff'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{padding:20,usePointStyle:true}},tooltip:{callbacks:{label:function(context){const total=context.dataset.data.reduce((a,b)=>a+b,0);const pct=((context.parsed/total)*100).toFixed(1);return context.label+': '+context.parsed+' ('+pct+'%)';}}}}}});
});
</script>
@endpush