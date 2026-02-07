<x-app-layout title="Detalhes do Alerta">
    <x-layout.page-container>
        <x-layout.page-header
            title="Detalhes do Alerta"
            icon="exclamation-triangle"
            :breadcrumb-items="[
                'Admin' => route('admin.dashboard'),
                'Alertas' => route('admin.alerts.index'),
                'Detalhes' => '#'
            ]">
            <x-slot:actions>
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.alerts.edit', $alert['id']) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-2"></i>Editar
                    </a>
                    <a href="{{ route('admin.alerts.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Voltar
                    </a>
                </div>
            </x-slot:actions>
        </x-layout.page-header>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Informações do Alerta
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Título:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $alert['title'] }}
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Mensagem:</strong>
                        </div>
                        <div class="col-sm-9">
                            <div class="alert alert-{{ $alert['severity'] == 'danger' ? 'danger' : ($alert['severity'] == 'warning' ? 'warning' : 'info') }}">
                                {{ $alert['message'] }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Tipo:</strong>
                        </div>
                        <div class="col-sm-9">
                            <span class="badge bg-secondary">
                                {{ ucfirst($alert['type']) }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Severidade:</strong>
                        </div>
                        <div class="col-sm-9">
                            @php
                                $badgeClass = match($alert['severity']) {
                                    'danger' => 'bg-danger',
                                    'warning' => 'bg-warning text-dark',
                                    'info' => 'bg-info',
                                    default => 'bg-secondary'
                                };
                                $severityText = match($alert['severity']) {
                                    'danger' => 'Crítico',
                                    'warning' => 'Aviso',
                                    'info' => 'Informativo',
                                    default => 'Desconhecido'
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">
                                {{ $severityText }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Status:</strong>
                        </div>
                        <div class="col-sm-9">
                            @php
                                $statusClass = match($alert['status']) {
                                    'active' => 'bg-success',
                                    'resolved' => 'bg-secondary',
                                    default => 'bg-light text-dark'
                                };
                                $statusText = match($alert['status']) {
                                    'active' => 'Ativo',
                                    'resolved' => 'Resolvido',
                                    default => 'Desconhecido'
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">
                                {{ $statusText }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Criado em:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $alert['created_at']->format('d/m/Y H:i:s') }}
                        </div>
                    </div>
                    
                    @if(isset($alert['updated_at']))
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <strong>Atualizado em:</strong>
                            </div>
                            <div class="col-sm-9">
                                {{ $alert['updated_at']->format('d/m/Y H:i:s') }}
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <form action="{{ route('admin.alerts.destroy', $alert['id']) }}" 
                              method="POST" 
                              class="d-inline"
                              onsubmit="return confirm('Tem certeza que deseja excluir este alerta?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash me-2"></i>Excluir Alerta
                            </button>
                        </form>
                        
                        <div class="btn-group">
                            <a href="{{ route('admin.alerts.edit', $alert['id']) }}" class="btn btn-primary">
                                <i class="bi bi-pencil me-2"></i>Editar
                            </a>
                            <a href="{{ route('admin.alerts.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Voltar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock me-2"></i>Timeline
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Alerta Criado</h6>
                                <small class="text-muted">
                                    {{ $alert['created_at']->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                        
                        @if(isset($alert['updated_at']) && $alert['updated_at'] != $alert['created_at'])
                            <div class="timeline-item">
                                <div class="timeline-marker bg-warning"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Alerta Atualizado</h6>
                                    <small class="text-muted">
                                        {{ $alert['updated_at']->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                        @endif
                        
                        @if($alert['status'] == 'resolved')
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Alerta Resolvido</h6>
                                    <small class="text-muted">
                                        Problema resolvido
                                    </small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lightbulb me-2"></i>Ações Recomendadas
                    </h5>
                </div>
                <div class="card-body">
                    @if($alert['severity'] == 'danger')
                        <div class="alert alert-danger">
                            <strong>Ação Imediata Requerida!</strong>
                            <p class="mb-0 mt-2">Este alerta é crítico e requer atenção imediata.</p>
                        </div>
                    @elseif($alert['severity'] == 'warning')
                        <div class="alert alert-warning">
                            <strong>Ação Recomendada</strong>
                            <p class="mb-0 mt-2">Verifique este alerta assim que possível.</p>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <strong>Informação</strong>
                            <p class="mb-0 mt-2">Este é um alerta informativo.</p>
                        </div>
                    @endif
                    
                    <div class="d-grid gap-2">
                        <x-ui.button variant="primary" icon="printer" label="Imprimir" onclick="window.print()" />
                        <x-ui.button variant="secondary" icon="clipboard" label="Copiar Link" onclick="navigator.clipboard.writeText(window.location.href)" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    
    .timeline-marker {
        position: absolute;
        left: -23px;
        top: 5px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 3px solid #fff;
        box-shadow: 0 0 0 2px #dee2e6;
    }
    
    .timeline-content {
        background: #f8f9fa;
        padding: 10px 15px;
        border-radius: 5px;
        margin-left: 10px;
    }
</style>
@endpush