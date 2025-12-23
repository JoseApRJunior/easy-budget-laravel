@extends('layouts.app')

@section('content')
    <div class="container-fluid py-1">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4"><i class="bi bi-graph-up me-2"></i>Dashboard de Métricas</h2>
            </div>
        </div>

        <!-- Cards de Resumo -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Middlewares Ativos</h6>
                                <h3>{{ $active_middlewares ?? 0 }}</h3>
                            </div>
                            <i class="bi bi-shield-check fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Taxa de Sucesso</h6>
                                <h3>{{ number_format($success_rate ?? 0, 1) }}%</h3>
                            </div>
                            <i class="bi bi-check-circle fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Tempo Médio</h6>
                                <h3>{{ number_format($average_time ?? 0, 0) }}ms</h3>
                            </div>
                            <i class="bi bi-clock fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Execuções/Hora</h6>
                                <h3>{{ number_format($executions_per_hour ?? 0, 1) }}k</h3>
                            </div>
                            <i class="bi bi-activity fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status dos Middlewares -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Status dos Middlewares</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Middleware</th>
                                        <th>Status</th>
                                        <th>Execuções</th>
                                        <th>Tempo Médio</th>
                                        <th>Última Execução</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($middlewares as $middleware)
                                        <tr>
                                            <td><span
                                                    class="badge bg-{{ $middleware['color'] ?? 'primary' }}">{{ $middleware['name'] }}</span>
                                            </td>
                                            <td><span
                                                    class="badge bg-{{ $middleware['status'] == 'Ativo' ? 'success' : 'danger' }}">{{ $middleware['status'] }}</span>
                                            </td>
                                            <td>{{ number_format($middleware['executions']) }}</td>
                                            <td>{{ $middleware['average_time'] }}ms</td>
                                            <td>{{ $middleware['last_execution'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">Nenhum middleware encontrado.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ações Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <a href="{{ url('/admin/monitoring') }}" class="btn btn-outline-primary w-100 mb-2">
                                    <i class="bi bi-graph-up me-2"></i>Monitoramento Geral
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="{{ url('/admin/monitoring/metrics') }}" class="btn btn-outline-info w-100 mb-2">
                                    <i class="bi bi-speedometer2 me-2"></i>Métricas Detalhadas
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="{{ url('/admin/logs') }}" class="btn btn-outline-secondary w-100 mb-2">
                                    <i class="bi bi-terminal me-2"></i>Logs do Sistema
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
