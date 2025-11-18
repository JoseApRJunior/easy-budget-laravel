@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Gerenciamento de Alertas
                </h1>
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.alerts.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Novo Alerta
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-2"></i>Exportar
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.alerts.export', 'excel') }}">
                                <i class="bi bi-file-earmark-excel me-2"></i>Excel
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.alerts.export', 'csv') }}">
                                <i class="bi bi-file-earmark-text me-2"></i>CSV
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cards de Estatísticas --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total de Alertas</h6>
                            <h2 class="mb-0">{{ $stats['total'] }}</h2>
                        </div>
                        <i class="bi bi-exclamation-triangle fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Alertas Ativos</h6>
                            <h2 class="mb-0">{{ $stats['active'] }}</h2>
                        </div>
                        <i class="bi bi-bell fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Resolvidos</h6>
                            <h2 class="mb-0">{{ $stats['resolved'] }}</h2>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Críticos</h6>
                            <h2 class="mb-0">{{ $stats['critical'] }}</h2>
                        </div>
                        <i class="bi bi-lightning fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabela de Alertas --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-ul me-2"></i>Lista de Alertas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Título</th>
                                    <th>Mensagem</th>
                                    <th>Severidade</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($alerts as $alert)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ ucfirst($alert['type']) }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>{{ $alert['title'] }}</strong>
                                        </td>
                                        <td>
                                            {{ Str::limit($alert['message'], 50) }}
                                        </td>
                                        <td>
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
                                        </td>
                                        <td>
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
                                        </td>
                                        <td>
                                            {{ $alert['created_at']->format('d/m/Y H:i') }}
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.alerts.show', $alert['id']) }}" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="Ver Detalhes">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.alerts.edit', $alert['id']) }}" 
                                                   class="btn btn-sm btn-outline-secondary" 
                                                   title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('admin.alerts.destroy', $alert['id']) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Tem certeza que deseja excluir este alerta?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-danger" 
                                                            title="Excluir">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <i class="bi bi-inbox display-4 text-muted"></i>
                                            <p class="text-muted">Nenhum alerta encontrado</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Adicionar animação aos cards de estatísticas
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            }, index * 100);
        });
    });
</script>
@endpush