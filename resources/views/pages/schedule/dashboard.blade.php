@extends('layouts.app')

@section('title', 'Dashboard de Agendamentos')

@section('content')
    <div class="container-fluid py-4">
        <x-page-header
            title="Dashboard de Agendamentos"
            icon="calendar-check"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Agendamentos' => route('provider.schedules.index'),
                'Dashboard' => '#'
            ]">
            <p class="text-muted mb-0 small">Visão geral dos agendamentos com métricas e próximos horários.</p>
        </x-page-header>

        @php
            $total = $stats['total_schedules'] ?? 0;
            $pending = $stats['pending_schedules'] ?? 0;
            $confirmed = $stats['confirmed_schedules'] ?? 0;
            $completed = $stats['completed_schedules'] ?? 0;
            $cancelled = $stats['cancelled_schedules'] ?? 0;
            $noShow = $stats['no_show_schedules'] ?? 0;
            $upcoming = $stats['upcoming_schedules'] ?? 0;
            $recent = $stats['recent_upcoming'] ?? collect();
            $breakdown = $stats['status_breakdown'] ?? [];

            // Cálculos adicionais para métricas mais insights
            $completionRate = $total > 0 ? number_format(($completed / $total) * 100, 1, ',', '.') : 0;
            $noShowRate =
                $completed + $noShow > 0 ? number_format(($noShow / ($completed + $noShow)) * 100, 1, ',', '.') : 0;
            $confirmationRate =
                $pending + $confirmed > 0
                    ? number_format(($confirmed / ($pending + $confirmed)) * 100, 1, ',', '.')
                    : 0;
            $todaySchedules = $stats['today_schedules'] ?? 0;
            $thisWeekSchedules = $stats['this_week_schedules'] ?? 0;
        @endphp

        <div class="row g-4 mb-4">
            <div class="col-lg-2 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-primary bg-gradient me-3">
                                <i class="bi bi-calendar text-white"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Total</h6>
                                <h3 class="mb-0">{{ $total }}</h3>
                            </div>
                            <x-button type="link" :href="route('provider.schedules.index')" variant="link" size="sm" icon="chevron-right" class="p-0" />
                        </div>
                        <p class="text-muted small mb-0">Agendamentos registrados.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-info bg-gradient me-3">
                                <i class="bi bi-calendar-date text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Próximos</h6>
                                <h3 class="mb-0">{{ $upcoming }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">Agendamentos futuros.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-success bg-gradient me-3">
                                <i class="bi bi-check2-circle text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Concluídos</h6>
                                <h3 class="mb-0">{{ $completed }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">Finalizados com sucesso.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-warning bg-gradient me-3">
                                <i class="bi bi-graph-up-arrow text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Conclusão</h6>
                                <h3 class="mb-0">{{ $completionRate }}%</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">Taxa de conclusão.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-danger bg-gradient me-3">
                                <i class="bi bi-slash-circle text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">No-show</h6>
                                <h3 class="mb-0">{{ $noShowRate }}%</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">Taxa de faltas.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-secondary bg-gradient me-3">
                                <i class="bi bi-calendar-check text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Confirmação</h6>
                                <h3 class="mb-0">{{ $confirmationRate }}%</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">Taxa de confirmação.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-primary bg-gradient me-3">
                                <i class="bi bi-bar-chart text-white"></i>
                            </div>
                            <h6 class="text-muted mb-0">Distribuição por Status</h6>
                        </div>
                        <div class="chart-container">
                            <canvas id="statusChart" style="max-height: 200px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-info bg-gradient me-3">
                                <i class="bi bi-calendar-week text-white"></i>
                            </div>
                            <h6 class="text-muted mb-0">Agendamentos por Período</h6>
                        </div>
                        <div class="chart-container">
                            <canvas id="periodChart" style="max-height: 200px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-warning bg-gradient me-3">
                                <i class="bi bi-list-check text-white"></i>
                            </div>
                            <h6 class="text-muted mb-0">Resumo por Status</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h5 mb-1 text-warning">{{ $pending }}</div>
                                    <small class="text-muted">Pendentes</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h5 mb-1 text-primary">{{ $confirmed }}</div>
                                    <small class="text-muted">Confirmados</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h5 mb-1 text-danger">{{ $cancelled }}</div>
                                    <small class="text-muted">Cancelados</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h5 mb-1 text-secondary">{{ $noShow }}</div>
                                    <small class="text-muted">No-show</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-success bg-gradient me-3">
                                <i class="bi bi-trophy text-white"></i>
                            </div>
                            <h6 class="text-muted mb-0">Performance</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h5 mb-1 text-success">{{ $completionRate }}%</div>
                                    <small class="text-muted">Conclusão</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h5 mb-1 text-info">{{ $confirmationRate }}%</div>
                                    <small class="text-muted">Confirmação</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h5 mb-1 text-danger">{{ $noShowRate }}%</div>
                                    <small class="text-muted">No-show</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h5 mb-1 text-primary">{{ $todaySchedules }}</div>
                                    <small class="text-muted">Hoje</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>
                            <span class="d-none d-sm-inline">Próximos Agendamentos</span>
                            <span class="d-sm-none">Próximos</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <!-- Desktop View -->
                        <div class="desktop-view">
                            @if ($recent->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="modern-table table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Horário</th>
                                                <th>Cliente</th>
                                                <th>Serviço</th>
                                                <th>Status</th>
                                                <th class="text-center">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($recent as $sc)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($sc->start_date_time)->format('d/m/Y') }}
                                                    </td>
                                                    <td>{{ \Carbon\Carbon::parse($sc->start_date_time)->format('H:i') }} -
                                                        {{ \Carbon\Carbon::parse($sc->end_date_time)->format('H:i') }}</td>
                                                    <td>{{ $sc->service?->customer?->commonData?->first_name ?? 'N/A' }}
                                                    </td>
                                                    <td>{{ $sc->service?->description ?? $sc->service?->code }}</td>
                                                    <td>
                                                        @php
                                                            $statusClass = match ($sc->status) {
                                                                'confirmed' => 'bg-primary',
                                                                'pending' => 'bg-warning',
                                                                'completed' => 'bg-success',
                                                                'cancelled' => 'bg-danger',
                                                                'no_show' => 'bg-secondary',
                                                                default => 'bg-secondary',
                                                            };
                                                            $statusIcon = match ($sc->status) {
                                                                'confirmed' => 'bi-check-circle',
                                                                'pending' => 'bi-hourglass-split',
                                                                'completed' => 'bi-check2-circle',
                                                                'cancelled' => 'bi-x-circle',
                                                                'no_show' => 'bi-slash-circle',
                                                                default => 'bi-question-circle',
                                                            };
                                                            $statusLabel = match ($sc->status) {
                                                                'confirmed' => 'Confirmado',
                                                                'pending' => 'Pendente',
                                                                'completed' => 'Concluído',
                                                                'cancelled' => 'Cancelado',
                                                                'no_show' => 'No-show',
                                                                default => ucfirst(str_replace('_', ' ', $sc->status)),
                                                            };
                                                        @endphp
                                                        <span class="badge {{ $statusClass }}">
                                                            <i class="{{ $statusIcon }} me-1"></i>{{ $statusLabel }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <x-button type="link" :href="route('provider.schedules.show', $sc->id)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                    </td>
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

                        <!-- Mobile View -->
                        <div class="mobile-view">
                            @if ($recent->isNotEmpty())
                                <div class="list-group">
                                    @foreach ($recent as $sc)
                                        @php
                                            $statusClass = match ($sc->status) {
                                                'confirmed' => 'bg-primary',
                                                'pending' => 'bg-warning',
                                                'completed' => 'bg-success',
                                                'cancelled' => 'bg-danger',
                                                'no_show' => 'bg-secondary',
                                                default => 'bg-secondary',
                                            };
                                            $statusIcon = match ($sc->status) {
                                                'confirmed' => 'bi-check-circle',
                                                'pending' => 'bi-hourglass-split',
                                                'completed' => 'bi-check2-circle',
                                                'cancelled' => 'bi-x-circle',
                                                'no_show' => 'bi-slash-circle',
                                                default => 'bi-question-circle',
                                            };
                                        @endphp
                                        <div class="list-group-item py-3">
                                        <div class="d-flex align-items-start">
                                            <i class="bi bi-calendar-event text-muted me-2 mt-1"></i>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold mb-2">
                                                    {{ \Carbon\Carbon::parse($sc->start_date_time)->format('d/m/Y H:i') }}
                                                </div>
                                                <div class="d-flex gap-2 flex-wrap mb-2">
                                                    <span class="badge {{ $statusClass }}">
                                                        <i class="{{ $statusIcon }}"></i>
                                                    </span>
                                                    <small class="text-muted">
                                                        {{ $sc->service?->customer?->commonData?->first_name ?? 'N/A' }}
                                                    </small>
                                                </div>
                                                <div class="small text-muted">
                                                    {{ Str::limit($sc->service?->description ?? $sc->service?->code, 50) }}
                                                </div>
                                            </div>
                                            <div class="ms-2">
                                                <x-button type="link" :href="route('provider.schedules.show', $sc->id)" variant="info" size="sm" icon="eye" />
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
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
            </div>
            <div class="col-lg-4">
                <!-- Insights -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Insights Rápidos</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small text-muted">
                            @if ($pending > 0)
                                <li class="mb-2">
                                    <i class="bi bi-calendar-check text-primary me-2"></i>
                                    {{ $pending }} agendamento{{ $pending > 1 ? 's' : '' }}
                                    pendente{{ $pending > 1 ? 's' : '' }} de confirmação.
                                </li>
                            @endif
                            @if ($noShowRate > 10)
                                <li class="mb-2">
                                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                    Taxa de no-show elevada ({{ $noShowRate }}%). Considere lembretes.
                                </li>
                            @endif
                            @if ($confirmationRate < 70 && $pending > 0)
                                <li class="mb-2">
                                    <i class="bi bi-info-circle text-info me-2"></i>
                                    Apenas {{ $confirmationRate }}% dos agendamentos foram confirmados.
                                </li>
                            @endif
                            @if ($upcoming == 0)
                                <li class="mb-2">
                                    <i class="bi bi-calendar-plus text-success me-2"></i>
                                    Que tal criar novos agendamentos para esta semana?
                                </li>
                            @endif
                            @if ($completionRate > 90)
                                <li class="mb-2">
                                    <i class="bi bi-trophy text-success me-2"></i>
                                    Excelente taxa de conclusão! Continue assim.
                                </li>
                            @endif
                            @if (empty($recent))
                                <li class="mb-2">
                                    <i class="bi bi-plus-circle text-primary me-2"></i>
                                    Comece criando seu primeiro agendamento.
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                <!-- Atalhos -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Atalhos</h6>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <x-button type="link" :href="route('provider.services.index')" variant="success" size="sm" icon="plus-circle" label="Ver Serviços" />
                        <x-button type="link" :href="route('provider.schedules.index')" variant="primary" outline size="sm" icon="calendar3" label="Listar Todos" />
                        <x-button type="link" :href="route('provider.schedules.index', ['status' => 'pending'])" variant="warning" outline size="sm" icon="hourglass-split" label="Pendentes" />
                        <x-button type="link" :href="route('provider.schedules.index', ['status' => 'confirmed'])" variant="info" outline size="sm" icon="check-circle" label="Confirmados" />
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bi bi-rocket me-2"></i>Ações Rápidas</h6>
                    </div>
                    <div class="card-body d-grid gap-2">
                        @if ($pending > 0)
                            <x-button variant="primary" outline size="sm" icon="check-all" label="Confirmar Todos Pendentes" onclick="confirmAllPending()" />
                        @endif
                        <x-button variant="secondary" outline size="sm" icon="download" label="Exportar Agendamentos" onclick="exportSchedules()" />
                        <x-button type="link" :href="route('provider.schedules.calendar')" variant="info" outline size="sm" icon="calendar-week" label="Ver Calendário" />
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 120px;
            width: 100%
        }

        .chart-container canvas {
            max-width: 100% !important;
            height: auto !important
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Status Chart
            const statusData = @json($breakdown);
            const statusLabels = [];
            const statusValues = [];
            const statusColors = [];
            Object.keys(statusData).forEach(k => {
                const s = statusData[k];
                if (s && s.count > 0) {
                    statusLabels.push(k.replace('_', ' ').replace('-', ' '));
                    statusValues.push(s.count);
                    statusColors.push(s.color || '#6c757d');
                }
            });

            if (statusValues.length > 0) {
                const statusCtx = document.getElementById('statusChart');
                if (statusCtx) {
                    new Chart(statusCtx, {
                        type: 'doughnut',
                        data: {
                            labels: statusLabels,
                            datasets: [{
                                data: statusValues,
                                backgroundColor: statusColors,
                                borderWidth: 2,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 15,
                                        usePointStyle: true,
                                        font: {
                                            size: 12
                                        }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const total = context.dataset.data.reduce((a, b) => a + b,
                                                0);
                                            const pct = ((context.parsed / total) * 100).toFixed(1);
                                            return context.label + ': ' + context.parsed + ' (' + pct +
                                                '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            } else {
                const statusContainer = document.querySelector('#statusChart').parentElement;
                if (statusContainer) {
                    statusContainer.innerHTML =
                        '<p class="text-muted text-center mb-0 small">Nenhum agendamento cadastrado</p>';
                }
            }

            // Period Chart (Mock data - would come from backend)
            const periodLabels = ['Hoje', 'Esta Semana', 'Este Mês'];
            const periodValues = [@json($todaySchedules), @json($thisWeekSchedules),
                @json($total)
            ];

            const periodCtx = document.getElementById('periodChart');
            if (periodCtx) {
                new Chart(periodCtx, {
                    type: 'bar',
                    data: {
                        labels: periodLabels,
                        datasets: [{
                            label: 'Agendamentos',
                            data: periodValues,
                            backgroundColor: [
                                'rgba(13, 110, 253, 0.8)',
                                'rgba(25, 135, 84, 0.8)',
                                'rgba(255, 193, 7, 0.8)'
                            ],
                            borderColor: [
                                'rgb(13, 110, 253)',
                                'rgb(25, 135, 84)',
                                'rgb(255, 193, 7)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
        });

        // Quick Actions Functions
        function confirmAllPending() {
            alert(
                'Funcionalidade "Confirmar Todos Pendentes" será implementada em breve.\n\nPor enquanto, confirme os agendamentos individualmente na lista.'
            );
        }

        function exportSchedules() {
            alert(
                'Funcionalidade de exportação será implementada em breve.\n\nUse a lista completa de agendamentos para visualizar todos os registros.'
            );
        }
    </script>
@endpush
