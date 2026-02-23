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
                    <x-dashboard.chart-doughnut
                        id="statusChart"
                        :data="$breakdown"
                        height="250"
                    />
                </x-resource.resource-list-card>
            </x-layout.grid-col>

            <x-layout.grid-col size="col-lg-6">
                <x-resource.resource-list-card
                    title="Atividade Recente"
                    icon="activity"
                    padding="p-4"
                >
                    <x-layout.grid-row class="text-center g-3 mb-0">
                        <x-dashboard.mini-stat-card
                            label="Hoje"
                            :value="$todaySchedules"
                            variant="primary"
                            col="col-12 col-md-3"
                        />
                        <x-dashboard.mini-stat-card
                            label="Esta Semana"
                            :value="$thisWeekSchedules"
                            variant="success"
                            col="col-12 col-md-3"
                        />
                        <x-dashboard.mini-stat-card
                            label="Pendentes"
                            :value="$pending"
                            variant="warning"
                            col="col-12 col-md-3"
                        />
                        <x-dashboard.mini-stat-card
                            label="Faltas (No-show)"
                            :value="$noShow"
                            variant="danger"
                            col="col-12 col-md-3"
                        />
                    </x-layout.grid-row>
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
                                        <x-resource.table-cell header>Cód.</x-resource.table-cell>
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
                                        <x-resource.table-cell>
                                            <small class="text-muted">{{ $sc->code }}</small>
                                        </x-resource.table-cell>
                                        <x-resource.table-cell class="fw-medium">
                                            {{ $sc->service?->customer?->commonData?->first_name ?? 'Cliente N/A' }}
                                        </x-resource.table-cell>
                                        <x-resource.table-cell>
                                            <x-resource.table-cell-truncate :text="$sc->service?->description ?? $sc->service?->code" />
                                        </x-resource.table-cell>
                                        <x-resource.table-cell>
                                            <x-ui.status-badge :item="$sc" />
                                        </x-resource.table-cell>
                                        <x-resource.table-cell align="center">
                                            <x-ui.button type="link" :href="route('provider.schedules.show', $sc->code)" variant="outline-primary" size="sm" icon="eye" title="Visualizar" feature="schedules" />
                                        </x-resource.table-cell>
                                    </x-resource.table-row>
                                @endforeach
                            </x-resource.resource-table>
                        </x-slot:desktop>

                        <x-slot:mobile>
                            @foreach ($recent as $sc)
                                <x-resource.resource-mobile-item
                                    :href="route('provider.schedules.show', $sc->code)"
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
                                        <x-ui.status-badge :item="$sc" />
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
                        <x-ui.button type="link" :href="route('provider.schedules.index')" variant="primary" icon="list-ul" label="Listar Todos" feature="schedules" />
                        <x-ui.button type="link" :href="route('provider.schedules.calendar')" variant="primary" icon="calendar-week" label="Ver Calendário" feature="schedules" />
                        <x-ui.button type="link" :href="route('provider.schedules.index', ['status' => 'pending'])" variant="warning" icon="hourglass-split" label="Ver Pendentes" feature="schedules" />
                        <x-ui.button type="link" :href="route('provider.schedules.index', ['status' => 'confirmed'])" variant="info" icon="check-circle" label="Ver Confirmados" feature="schedules" />
                    </x-resource.quick-actions>

                    <!-- Ações de Lote -->
                    <x-resource.resource-list-card
                        title="Ações Rápidas"
                        icon="rocket"
                        padding="p-3"
                    >
                        <x-layout.v-stack gap="2">
                            @if ($pending > 0)
                                <x-ui.button variant="primary" outline size="sm" icon="check-all" label="Confirmar Pendentes" onclick="confirmAllPending()" class="w-100" feature="schedules" />
                            @endif
                            <x-ui.button variant="secondary" outline size="sm" icon="download" label="Exportar Agenda" onclick="exportSchedules()" class="w-100" feature="schedules" />
                        </x-layout.v-stack>
                    </x-resource.resource-list-card>
                </x-layout.v-stack>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection

@push('scripts')
    <script>
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
