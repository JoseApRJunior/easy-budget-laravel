@extends('layouts.app')

@section('title', 'Dashboard de Relatórios')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Dashboard de Relatórios"
            icon="bar-chart-line"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Relatórios' => '#'
            ]"
            description="Visão geral dos relatórios gerados e métricas de uso do sistema."
        />

        @php
            $totalReports = $stats['total_reports'] ?? 0;
            $recentReports = $stats['recent_reports'] ?? collect();
            $reportsByType = $stats['reports_by_type'] ?? collect();
            $mostUsedReport = $stats['most_used_report'] ?? null;
        @endphp

        <!-- Cards de Métricas -->
        <x-layout.grid-row>
            <x-dashboard.stat-card
                title="Total de Relatórios"
                :value="$totalReports"
                description="Relatórios gerados no sistema."
                icon="file-earmark-pdf"
                variant="primary"
            />

            <x-dashboard.stat-card
                title="Mais Utilizado"
                :value="$mostUsedReport ? ucfirst($mostUsedReport) : 'N/A'"
                description="Tipo de relatório mais acessado."
                icon="graph-up-arrow"
                variant="success"
            />

            <x-dashboard.stat-card
                title="Recentes"
                :value="$recentReports->count()"
                description="Gerados nos últimos 30 dias."
                icon="calendar-event"
                variant="info"
            />

            <x-dashboard.stat-card
                title="Tipos Disponíveis"
                :value="$reportsByType->count()"
                description="Variedade de relatórios oferecidos."
                icon="bar-chart-line"
                variant="warning"
            />
        </x-layout.grid-row>

        <!-- Conteúdo Principal -->
        <x-layout.grid-row>
            <!-- Relatórios Recentes (8 colunas) -->
            <x-layout.grid-col size="col-lg-8">
                <x-resource.resource-list-card
                    title="Relatórios Recentes"
                    icon="clock-history"
                    :total="$recentReports->count()"
                >
                    @if ($recentReports && $recentReports->isNotEmpty())
                        <x-slot:desktop>
                            <x-resource.resource-table>
                                <x-slot:thead>
                                    <x-resource.table-row>
                                        <x-resource.table-cell header>Tipo</x-resource.table-cell>
                                        <x-resource.table-cell header>Período</x-resource.table-cell>
                                        <x-resource.table-cell header>Gerado em</x-resource.table-cell>
                                        <x-resource.table-cell header>Status</x-resource.table-cell>
                                        <x-resource.table-cell header align="center">Ações</x-resource.table-cell>
                                    </x-resource.table-row>
                                </x-slot:thead>

                                @foreach ($recentReports as $report)
                                    <x-resource.table-row>
                                        <x-resource.table-cell>
                                            <x-resource.resource-info
                                                :title="ucfirst($report->type ?? 'N/A')"
                                                :subtitle="$report->period ?? 'Período não informado'"
                                                titleClass="fw-bold text-dark"
                                            />
                                        </x-resource.table-cell>
                                        <x-resource.table-cell class="text-muted small">
                                            @if ($report->date)
                                                {{ \Carbon\Carbon::parse($report->date)->format('d/m/Y H:i') }}
                                            @else
                                                {{ $report->generated_at ? \Carbon\Carbon::parse($report->generated_at)->format('d/m/Y H:i') : 'N/A' }}
                                            @endif
                                        </x-resource.table-cell>
                                        <x-resource.table-cell>
                                            <x-ui.badge variant="success" label="Concluído" />
                                        </x-resource.table-cell>
                                        <x-resource.table-actions>
                                            @if (isset($report->download_url))
                                                <x-ui.button type="link" :href="$report->download_url" variant="outline-primary" size="sm" icon="download" title="Download" target="_blank" />
                                            @endif
                                            @if (isset($report->view_url))
                                                <x-ui.button type="link" :href="$report->view_url" variant="outline-info" size="sm" icon="eye" title="Visualizar" target="_blank" />
                                            @endif
                                        </x-resource.table-actions>
                                    </x-resource.table-row>
                                @endforeach
                            </x-resource.resource-table>
                        </x-slot:desktop>

                        <x-slot:mobile>
                            @foreach ($recentReports as $report)
                                <x-resource.resource-mobile-item
                                    :href="isset($report->view_url) ? $report->view_url : '#'"
                                >
                                    <x-resource.resource-mobile-header
                                        :title="ucfirst($report->type ?? 'N/A')"
                                        :subtitle="$report->period ?? 'N/A'"
                                    />
                                    <x-resource.resource-mobile-field
                                        label="Gerado em"
                                        :value="$report->date ? \Carbon\Carbon::parse($report->date)->format('d/m/Y H:i') : ($report->generated_at ? \Carbon\Carbon::parse($report->generated_at)->format('d/m/Y H:i') : 'N/A')"
                                    />
                                    <x-resource.resource-mobile-field
                                        label="Status"
                                    >
                                        <x-ui.badge variant="success" label="Concluído" />
                                    </x-resource.resource-mobile-field>
                                </x-resource.resource-mobile-item>
                            @endforeach
                        </x-slot:mobile>
                    @else
                        <x-resource.empty-state
                            title="Nenhum relatório gerado"
                            description="Seus relatórios recentes aparecerão aqui conforme forem solicitados."
                            :icon="null"
                        />
                    @endif
                </x-resource.resource-list-card>
            </x-layout.grid-col>

            <!-- Sidebar (4 colunas) -->
            <x-layout.grid-col size="col-lg-4">
                <x-layout.v-stack gap="4">
                    <!-- Insights -->
                    <x-resource.resource-list-card
                        title="Insights Estratégicos"
                        icon="lightbulb"
                        padding="p-3"
                        gap="3"
                    >
                        <x-dashboard.insight-item
                            icon="graph-up-arrow"
                            variant="primary"
                            description="Relatórios financeiros ajudam a acompanhar a saúde do seu negócio."
                        />
                        <x-dashboard.insight-item
                            icon="people-fill"
                            variant="success"
                            description="A análise de clientes revela oportunidades de fidelização."
                        />
                        <x-dashboard.insight-item
                            icon="box-seam"
                            variant="info"
                            description="O controle de produtos otimiza sua gestão de inventário."
                        />
                    </x-resource.resource-list-card>

                    <!-- Atalhos -->
                    <x-resource.quick-actions
                        title="Gerar Relatórios"
                        icon="lightning-charge"
                    >
                        <x-ui.button type="link" :href="route('provider.reports.financial')" variant="outline-primary" icon="currency-dollar" label="Financeiro" />
                        <x-ui.button type="link" :href="route('provider.reports.customers')" variant="outline-primary" icon="people" label="Clientes" />
                        <x-ui.button type="link" :href="route('provider.reports.products')" variant="outline-primary" icon="box-seam" label="Produtos" />
                        <x-ui.button type="link" :href="route('provider.reports.index')" variant="outline-secondary" icon="list-ul" label="Ver Todos" />
                    </x-resource.quick-actions>
                </x-layout.v-stack>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection
