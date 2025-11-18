@extends('layouts.app')

@section('title', 'Dashboard de Faturas')

@section('content')
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-0"><i class="bi bi-receipt me-2"></i>Dashboard de Faturas</h1>
      <p class="text-muted mb-0">Acompanhe suas faturas, recebimentos e pendências.</p>
    </div>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('provider.invoices.index') }}">Faturas</a></li>
        <li class="breadcrumb-item active">Dashboard</li>
      </ol>
    </nav>
  </div>

  @php
    $total      = $stats['total_invoices'] ?? 0;
    $paid       = $stats['paid_invoices'] ?? 0;
    $pending    = $stats['pending_invoices'] ?? 0;
    $overdue    = $stats['overdue_invoices'] ?? 0;
    $cancelled  = $stats['cancelled_invoices'] ?? 0;
    $billed     = $stats['total_billed'] ?? 0;
    $received   = $stats['total_received'] ?? 0;
    $toReceive  = $stats['total_pending'] ?? 0;
    $recent     = $stats['recent_invoices'] ?? collect();
    $breakdown  = $stats['status_breakdown'] ?? [];
    $paidRate   = $total > 0 ? number_format(($paid / $total) * 100, 1, ',', '.') : 0;
  @endphp

  <div class="row g-4 mb-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="d-flex align-items-center mb-3"><div class="avatar-circle bg-primary bg-gradient me-3"><i class="bi bi-receipt text-white"></i></div><div><h6 class="text-muted mb-1">Total de Faturas</h6><h3 class="mb-0">{{ $total }}</h3></div></div><p class="text-muted small mb-0">Quantidade total emitida.</p></div></div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="d-flex align-items-center mb-3"><div class="avatar-circle bg-success bg-gradient me-3"><i class="bi bi-check-circle-fill text-white"></i></div><div><h6 class="text-muted mb-1">Pagas</h6><h3 class="mb-0">{{ $paid }}</h3></div></div><p class="text-muted small mb-0">Faturas liquidadas.</p></div></div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="d-flex align-items-center mb-3"><div class="avatar-circle bg-warning bg-gradient me-3"><i class="bi bi-hourglass-split text-white"></i></div><div><h6 class="text-muted mb-1">Pendentes</h6><h3 class="mb-0">{{ $pending }}</h3></div></div><p class="text-muted small mb-0">Aguardando pagamento.</p></div></div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="d-flex align-items-center mb-3"><div class="avatar-circle bg-purple bg-gradient me-3" style="background:#6f42c1"><i class="bi bi-calendar-x-fill text-white"></i></div><div><h6 class="text-muted mb-1">Vencidas</h6><h3 class="mb-0">{{ $overdue }}</h3></div></div><p class="text-muted small mb-0">Passaram do vencimento.</p></div></div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="d-flex align-items-center mb-3"><div class="avatar-circle bg-success bg-gradient me-3"><i class="bi bi-currency-dollar text-white"></i></div><h6 class="text-muted mb-0">Totais Financeiros</h6></div><div class="row text-center"><div class="col"><div class="border rounded p-3"><div class="text-muted small">Faturado</div><div class="h5 mb-0">R$ {{ number_format($billed,2,',','.') }}</div></div></div><div class="col"><div class="border rounded p-3"><div class="text-muted small">Recebido</div><div class="h5 mb-0">R$ {{ number_format($received,2,',','.') }}</div></div></div><div class="col"><div class="border rounded p-3"><div class="text-muted small">A Receber</div><div class="h5 mb-0">R$ {{ number_format($toReceive,2,',','.') }}</div></div></div></div></div></div>
    </div>
    <div class="col-md-6">
      <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="d-flex align-items-center mb-3"><div class="avatar-circle bg-primary bg-gradient me-3"><i class="bi bi-bar-chart text-white"></i></div><h6 class="text-muted mb-0">Distribuição por Status</h6></div><div class="chart-container"><canvas id="statusChart" style="max-height: 120px;"></canvas></div><div class="text-muted small mt-3">Taxa de faturas pagas: {{ $paidRate }}%</div></div></div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Faturas Recentes</h6>
          <a href="{{ route('provider.invoices.index') }}" class="btn btn-sm btn-outline-primary">Ver Todas</a>
        </div>
        <div class="card-body">
          @if($recent->isNotEmpty())
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Código</th><th>Cliente</th><th>Status</th><th>Total</th><th>Vencimento</th><th></th></tr></thead>
                <tbody>
                  @foreach($recent as $inv)
                  <tr>
                    <td><code class="text-code">{{ $inv->code }}</code></td>
                    <td>{{ $inv->customer?->commonData?->first_name ?? 'N/A' }}</td>
                    <td><span class="badge" style="background: {{ $inv->invoiceStatus?->getColor() }}">{{ $inv->invoiceStatus?->getDescription() }}</span></td>
                    <td>R$ {{ number_format($inv->total,2,',','.') }}</td>
                    <td>{{ optional($inv->due_date)->format('d/m/Y') }}</td>
                    <td class="text-end"><a href="{{ route('provider.invoices.show', $inv->code) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="text-center py-5"><i class="bi bi-receipt display-4 text-muted mb-3"></i><h6 class="text-muted">Nenhuma fatura encontrada</h6><p class="text-muted mb-0">Emita faturas para visualizar aqui.</p></div>
          @endif
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm mb-3"><div class="card-header bg-transparent border-0"><h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Insights</h6></div><div class="card-body"><ul class="list-unstyled mb-0 small text-muted"><li class="mb-2"><i class="bi bi-calendar-x text-purple me-2" style="color:#6f42c1"></i>Priorize faturas vencidas.</li><li class="mb-2"><i class="bi bi-hourglass-split text-warning me-2"></i>Evite pendências próximas ao vencimento.</li></ul></div></div>
      <div class="card border-0 shadow-sm"><div class="card-header bg-transparent border-0"><h6 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Atalhos</h6></div><div class="card-body d-grid gap-2"><a href="{{ route('provider.invoices.create') }}" class="btn btn-sm btn-success"><i class="bi bi-plus-circle me-2"></i>Nova Fatura</a><a href="{{ route('provider.invoices.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-receipt me-2"></i>Listar Faturas</a></div></div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .avatar-circle{width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center}
  .text-code{font-family:'Courier New',monospace;background:#f8f9fa;padding:2px 6px;border-radius:3px;font-size:.85em}
  .chart-container{display:flex;justify-content:center;align-items:center;min-height:120px;width:100%}
  .chart-container canvas{max-width:100% !important;height:auto !important}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
  const statusData=@json($breakdown);const labels=[];const values=[];const colors=[];Object.keys(statusData).forEach(k=>{const s=statusData[k];if(s && s.count>0){labels.push(k);values.push(s.count);colors.push(s.color||'#6c757d');}});if(values.length===0){const c=document.querySelector('.chart-container');if(c){c.innerHTML='<p class="text-muted text-center mb-0 small">Nenhuma fatura cadastrada</p>';}return;}const ctx=document.getElementById('statusChart');if(!ctx){return;}new Chart(ctx,{type:'doughnut',data:{labels:labels,datasets:[{data:values,backgroundColor:colors,borderWidth:2,borderColor:'#ffffff'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{padding:20,usePointStyle:true}},tooltip:{callbacks:{label:function(context){const total=context.dataset.data.reduce((a,b)=>a+b,0);const pct=((context.parsed/total)*100).toFixed(1);return context.label+': '+context.parsed+' ('+pct+'%)';}}}}}});
});
</script>
@endpush