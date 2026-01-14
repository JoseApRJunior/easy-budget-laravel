@extends('layouts.app')

@section('title', 'Dashboard de Agendamentos')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Dashboard de Agendamentos"
            icon="calendar-check"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Agendamentos' => route('provider.schedules.index'),
                'Dashboard' => '#'
            ]"
            description="Visão geral dos seus compromissos, taxas de presença e performance de agenda."
        />

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

            $completionRate = $total > 0 ? number_format(($completed / $total) * 100, 1, ',', '.') : 0;
            $noShowRate = ($completed + $noShow > 0) ? number_format(($noShow / ($completed + $noShow)) * 100, 1, ',', '.') : 0;
            $confirmationRate = ($pending + $confirmed > 0) ? number_format(($confirmed / ($pending + $confirmed)) * 100, 1, ',', '.') : 0;
            $todaySchedules = $stats['today_schedules'] ?? 0;
            $thisWeekSchedules = $stats['this_week_schedules'] ?? 0;
        @endphp

        <!-- Cards de Métricas -->
        <x-layout.grid-row>
            <x-dashboard.stat-card
                title="Total de Agendamentos"
                :value="$total"
                description="Total de registros na base."
                icon="calendar"
                variant="primary"
            />

            <x-dashboard.stat-card
                title="Próximos Compromissos"
                :value="$upcoming"
                description="Agendamentos futuros ativos."
                icon="calendar-date"
                variant="info"
            />

            <x-dashboard.stat-card
                title="Taxa de Conclusão"
                :value="$completionRate . '%'"
                description="Percentual de serviços finalizados."
                icon="check2-circle"
                variant="success"
            />

            <x-dashboard.stat-card
                title="Taxa de No-show"
                :value="$noShowRate . '%'"
                description="Percentual de faltas registradas."
                icon="slash-circle"
                variant="danger"
            />
        </x-layout.grid-row>

        <!-- Gráficos e Distribuição -->
        <x-layout.grid-row>
            <x-layout.grid-col size="col-lg-6">
                <x-resource.resource-list-card
                    title="Distribuição por Status"
                    icon="pie-chart"
                    padding="p-4"
                >
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </x-resource.resource-list-card>
            </x-layout.grid-col>

            <x-layout.grid-col size="col-lg-6">
                <x-resource.resource-list-card
                    title="Agendamentos por Período"
                    icon="bar-chart"
                    padding="p-4"
                >
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="periodChart"></canvas>
                    </div>
                </x-resource.resource-list-card>
            </x-layout.grid-col>
        </x-layout.grid-row>

        <!-- Conteúdo Principal -->
        <x-layout.grid-row>
            <!-- Próximos Agendamentos (8 colunas) -->
            <x-layout.grid-col size="col-lg-8">
                <x-resource.resource-list-card
                    title="Próximos Agendamentos"
                    icon="clock-history"
                    :total="$recent->count()"
                >
                    @if ($recent->isNotEmpty())
                        <x-slot:desktop>
                            <x-resource.resource-table>
                                <x-slot:thead>
                                    <x-resource.table-row>
                                        <x-resource.table-cell header>Data/Hora</x-resource.table-cell>
                                        <x-resource.table-cell header>Cliente</x-resource.table-cell>
                                        <x-resource.table-cell header>Serviço</x-resource.table-cell>
                                        <x-resource.table-cell header>Status</x-resource.table-cell>
                                        <x-resource.table-cell header align="center">Ações</x-resource.table-cell>
                                    </x-resource.table-row>
                                </x-slot:thead>

                                @foreach ($recent as $sc)
                                    <x-resource.table-row>
                                        <x-resource.table-cell>
                                            <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($sc->start_date_time)->format('d/m/Y') }}</div>
                                            <small class="text-muted small">
                                                {{ \Carbon\Carbon::parse($sc->start_date_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($sc->end_date_time)->format('H:i') }}
                                            </small>
                                        </x-resource.table-cell>
                                        <x-resource.table-cell class="fw-medium">
                                            {{ $sc->service?->customer?->commonData?->first_name ?? 'Cliente N/A' }}
                                        </x-resource.table-cell>
                                        <x-resource.table-cell>
                                            <x-resource.table-cell-truncate :text="$sc->service?->description ?? $sc->service?->code" />
                                        </x-resource.table-cell>
                                        <x-resource.table-cell>
                                            @php
                                                $statusVariant = match ($sc->status) {
                                                    'confirmed' => 'primary',
                                                    'pending' => 'warning',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger',
                                                    'no_show' => 'secondary',
                                                    default => 'secondary',
                                                };
                                                $statusLabel = match ($sc->status) {
                                                    'confirmed' => 'Confirmado',
                                                    'pending' => 'Pendente',
                                                    'completed' => 'Concluído',
                                                    'cancelled' => 'Cancelado',
                                                    'no_show' => 'No-show',
                                                    default => ucfirst($sc->status),
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $statusVariant }}-subtle text-{{ $statusVariant }} border-0 px-3">
                                                {{ $statusLabel }}
                                            </span>
                                        </x-resource.table-cell>
                                        <x-resource.table-cell align="center">
                                            <x-ui.button type="link" :href="route('provider.schedules.show', $sc->id)" variant="outline-primary" size="sm" icon="eye" title="Visualizar" />
                                        </x-resource.table-cell>
                                    </x-resource.table-row>
                                @endforeach
                            </x-resource.resource-table>
                        </x-slot:desktop>

                        <x-slot:mobile>
                            @foreach ($recent as $sc)
                                <x-resource.resource-mobile-item
                                    :href="route('provider.schedules.show', $sc->id)"
                                >
                                    <x-resource.resource-mobile-header
                                        :title="$sc->service?->customer?->commonData?->first_name ?? 'Cliente N/A'"
                                        :subtitle="\Carbon\Carbon::parse($sc->start_date_time)->format('d/m/Y H:i')"
                                    />
                                    <x-resource.resource-mobile-field
                                        label="Serviço"
                                        :value="$sc->service?->description ?? $sc->service?->code"
                                    />
                                    <x-resource.resource-mobile-field
                                        label="Status"
                                    >
                                        <span class="badge bg-{{ $statusVariant ?? 'secondary' }}-subtle text-{{ $statusVariant ?? 'secondary' }} border-0">
                                            {{ $statusLabel ?? $sc->status }}
                                        </span>
                                    </x-resource.resource-mobile-field>
                                </x-resource.resource-mobile-item>
                            @endforeach
                        </x-slot:mobile>
                    @else
                        <x-resource.empty-state
                            title="Nenhum agendamento"
                            description="Seus próximos compromissos aparecerão aqui."
                            icon="calendar2-event"
                        />
                    @endif
                </x-resource.resource-list-card>
            </x-layout.grid-col>

            <!-- Sidebar (4 colunas) -->
            <x-layout.grid-col size="col-lg-4">
                <x-layout.v-stack gap="4">
                    <!-- Insights -->
                    <x-resource.resource-list-card
                        title="Insights de Agenda"
                        icon="lightbulb"
                        padding="p-3"
                        gap="3"
                    >
                        @if ($pending > 0)
                            <x-dashboard.insight-item
                                icon="hourglass-split"
                                variant="warning"
                                :description="$pending . ' agendamento(s) pendente(s) de confirmação.'"
                            />
                        @endif
                        @if (floatval(str_replace(',', '.', $noShowRate)) > 10)
                            <x-dashboard.insight-item
                                icon="exclamation-triangle"
                                variant="danger"
                                description="Taxa de no-show elevada. Considere enviar lembretes automáticos."
                            />
                        @endif
                        <x-dashboard.insight-item
                            icon="trophy"
                            variant="success"
                            :description="'Sua taxa de conclusão está em ' . $completionRate . '%.'"
                        />
                    </x-resource.resource-list-card>

                    <!-- Atalhos -->
                    <x-resource.quick-actions
                        title="Atalhos da Agenda"
                        icon="lightning-charge"
                    >
                        <x-ui.button type="link" :href="route('provider.schedules.index')" variant="outline-primary" icon="list-ul" label="Listar Todos" />
                        <x-ui.button type="link" :href="route('provider.schedules.calendar')" variant="outline-primary" icon="calendar-week" label="Ver Calendário" />
                        <x-ui.button type="link" :href="route('provider.schedules.index', ['status' => 'pending'])" variant="outline-warning" icon="hourglass-split" label="Ver Pendentes" />
                        <x-ui.button type="link" :href="route('provider.schedules.index', ['status' => 'confirmed'])" variant="outline-info" icon="check-circle" label="Ver Confirmados" />
                    </x-resource.quick-actions>

                    <!-- Ações de Lote -->
                    <x-resource.resource-list-card
                        title="Ações Rápidas"
                        icon="rocket"
                        padding="p-3"
                    >
                        <x-layout.v-stack gap="2">
                            @if ($pending > 0)
                                <x-ui.button variant="primary" outline size="sm" icon="check-all" label="Confirmar Pendentes" onclick="confirmAllPending()" class="w-100" />
                            @endif
                            <x-ui.button variant="secondary" outline size="sm" icon="download" label="Exportar Agenda" onclick="exportSchedules()" class="w-100" />
                        </x-layout.v-stack>
                    </x-resource.resource-list-card>
                </x-layout.v-stack>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection
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
