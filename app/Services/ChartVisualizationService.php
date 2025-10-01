<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;

/**
 * Serviço avançado de visualização de gráficos
 * Suporta Chart.js, D3.js e gráficos personalizados para relatórios
 */
class ChartVisualizationService
{
    private array $chartJsColors = [
        'primary'   => '#3B82F6',
        'secondary' => '#10B981',
        'warning'   => '#F59E0B',
        'danger'    => '#EF4444',
        'info'      => '#06B6D4',
        'success'   => '#10B981',
        'purple'    => '#8B5CF6',
        'pink'      => '#EC4899',
        'orange'    => '#F97316',
        'teal'      => '#14B8A6'
    ];

    private array $chartTypes = [
        'line'     => 'Linha',
        'bar'      => 'Barras',
        'pie'      => 'Pizza',
        'doughnut' => 'Rosca',
        'radar'    => 'Radar',
        'polar'    => 'Polar',
        'bubble'   => 'Bolhas',
        'scatter'  => 'Dispersão',
        'area'     => 'Área',
        'mixed'    => 'Misto'
    ];

    /**
     * Gera configuração Chart.js para relatório
     */
    public function generateChartJsConfig( string $type, Collection $data, array $options = [] ): array
    {
        $config = [
            'type'    => $type,
            'data'    => [
                'labels'   => $this->extractLabels( $data, $options ),
                'datasets' => $this->generateDatasets( $data, $options )
            ],
            'options' => $this->generateChartOptions( $type, $options )
        ];

        return $config;
    }

    /**
     * Gera gráfico de linha para tendências
     */
    public function generateLineChart( Collection $data, array $options = [] ): array
    {
        $defaultOptions = [
            'title'       => 'Tendência',
            'x_field'     => 'date',
            'y_field'     => 'value',
            'color'       => 'primary',
            'fill'        => false,
            'tension'     => 0.1,
            'show_legend' => true,
            'show_grid'   => true
        ];

        $options = array_merge( $defaultOptions, $options );

        return $this->generateChartJsConfig( 'line', $data, $options );
    }

    /**
     * Gera gráfico de barras para comparações
     */
    public function generateBarChart( Collection $data, array $options = [] ): array
    {
        $defaultOptions = [
            'title'       => 'Comparação',
            'x_field'     => 'label',
            'y_field'     => 'value',
            'horizontal'  => false,
            'stacked'     => false,
            'show_legend' => true,
            'show_grid'   => true,
            'max_bars'    => 20
        ];

        $options = array_merge( $defaultOptions, $options );

        return $this->generateChartJsConfig( 'bar', $data, $options );
    }

    /**
     * Gera gráfico de pizza para distribuição
     */
    public function generatePieChart( Collection $data, array $options = [] ): array
    {
        $defaultOptions = [
            'title'            => 'Distribuição',
            'label_field'      => 'label',
            'value_field'      => 'value',
            'show_legend'      => true,
            'show_percentages' => true,
            'max_slices'       => 10
        ];

        $options = array_merge( $defaultOptions, $options );

        return $this->generateChartJsConfig( 'pie', $data, $options );
    }

    /**
     * Gera gráfico de área para volumes
     */
    public function generateAreaChart( Collection $data, array $options = [] ): array
    {
        $defaultOptions = [
            'title'       => 'Volume',
            'x_field'     => 'date',
            'y_field'     => 'value',
            'fill'        => true,
            'tension'     => 0.4,
            'show_points' => false,
            'show_grid'   => true
        ];

        $options = array_merge( $defaultOptions, $options );

        return $this->generateChartJsConfig( 'line', $data, $options );
    }

    /**
     * Gera gráfico KPI para métricas principais
     */
    public function generateKpiChart( Collection $data, array $options = [] ): array
    {
        $defaultOptions = [
            'title'          => 'KPI',
            'main_value'     => 0,
            'previous_value' => 0,
            'format'         => 'number',
            'show_trend'     => true,
            'trend_period'   => 'vs mês anterior',
            'icon'           => 'trending_up',
            'color'          => 'primary'
        ];

        $options = array_merge( $defaultOptions, $options );

        return [
            'type'   => 'kpi',
            'data'   => $data,
            'config' => $options
        ];
    }

    /**
     * Gera gráfico de dispersão para correlações
     */
    public function generateScatterChart( Collection $data, array $options = [] ): array
    {
        $defaultOptions = [
            'title'           => 'Correlação',
            'x_field'         => 'x',
            'y_field'         => 'y',
            'point_size'      => 5,
            'show_regression' => false,
            'show_grid'       => true
        ];

        $options = array_merge( $defaultOptions, $options );

        return $this->generateChartJsConfig( 'scatter', $data, $options );
    }

    /**
     * Gera configuração D3.js para gráficos avançados
     */
    public function generateD3Config( string $type, Collection $data, array $options = [] ): array
    {
        $config = [
            'type'       => $type,
            'data'       => $data->toArray(),
            'options'    => $options,
            'dimensions' => $options[ 'dimensions' ] ?? [
                'width'  => 800,
                'height' => 400,
                'margin' => [ 'top' => 20, 'right' => 30, 'bottom' => 40, 'left' => 50 ]
            ]
        ];

        return $config;
    }

    /**
     * Gera gráfico de rede (Network Graph) com D3.js
     */
    public function generateNetworkGraph( Collection $nodes, Collection $links, array $options = [] ): array
    {
        return $this->generateD3Config( 'network', collect( [
            'nodes' => $nodes,
            'links' => $links
        ] ), array_merge( [
                'node_size'       => 5,
                'link_distance'   => 50,
                'charge_strength' => -300,
                'show_labels'     => true
            ], $options ) );
    }

    /**
     * Gera gráfico de heatmap com D3.js
     */
    public function generateHeatmap( Collection $data, array $options = [] ): array
    {
        return $this->generateD3Config( 'heatmap', $data, array_merge( [
            'x_field'      => 'x',
            'y_field'      => 'y',
            'value_field'  => 'value',
            'color_scheme' => 'interpolateViridis',
            'show_values'  => false
        ], $options ) );
    }

    /**
     * Gera gráfico de árvore hierárquica com D3.js
     */
    public function generateTreeChart( Collection $data, array $options = [] ): array
    {
        return $this->generateD3Config( 'tree', $data, array_merge( [
            'orientation'        => 'horizontal',
            'node_size'          => 10,
            'show_labels'        => true,
            'animation_duration' => 750
        ], $options ) );
    }

    /**
     * Renderiza gráfico como HTML
     */
    public function renderChart( string $type, Collection $data, array $options = [] ): string
    {
        $chartId = 'chart_' . uniqid();

        switch ( $type ) {
            case 'line':
                $config = $this->generateLineChart( $data, $options );
                break;
            case 'bar':
                $config = $this->generateBarChart( $data, $options );
                break;
            case 'pie':
                $config = $this->generatePieChart( $data, $options );
                break;
            case 'area':
                $config = $this->generateAreaChart( $data, $options );
                break;
            case 'kpi':
                $config = $this->generateKpiChart( $data, $options );
                break;
            case 'scatter':
                $config = $this->generateScatterChart( $data, $options );
                break;
            default:
                $config = $this->generateBarChart( $data, $options );
        }

        return View::make( 'reports.charts.chartjs', [
            'chartId' => $chartId,
            'type'    => $type,
            'config'  => $config,
            'height'  => $options[ 'height' ] ?? 400,
            'width'   => $options[ 'width' ] ?? '100%'
        ] )->render();
    }

    /**
     * Extrai labels dos dados
     */
    private function extractLabels( Collection $data, array $options ): array
    {
        $field = $options[ 'x_field' ] ?? 'label';

        return $data->pluck( $field )->map( function ( $value ) {
            return $this->formatLabel( $value );
        } )->toArray();
    }

    /**
     * Gera datasets para Chart.js
     */
    private function generateDatasets( Collection $data, array $options ): array
    {
        $datasets = [];

        if ( isset( $options[ 'multiple_datasets' ] ) && $options[ 'multiple_datasets' ] ) {
            // Múltiplos datasets
            $groupedData = $data->groupBy( $options[ 'group_field' ] ?? 'group' );

            foreach ( $groupedData as $group => $items ) {
                $datasets[] = [
                    'label'           => $this->formatLabel( $group ),
                    'data'            => $items->pluck( $options[ 'y_field' ] ?? 'value' )->toArray(),
                    'borderColor'     => $this->getNextColor(),
                    'backgroundColor' => $this->getNextColor( 0.1 ),
                    'fill'            => $options[ 'fill' ] ?? false,
                    'tension'         => $options[ 'tension' ] ?? 0.1
                ];
            }
        } else {
            // Dataset único
            $datasets[] = [
                'label'           => $options[ 'title' ] ?? 'Dados',
                'data'            => $data->pluck( $options[ 'y_field' ] ?? 'value' )->toArray(),
                'borderColor'     => $this->chartJsColors[ $options[ 'color' ] ?? 'primary' ],
                'backgroundColor' => $this->getColorWithOpacity( $options[ 'color' ] ?? 'primary', 0.1 ),
                'fill'            => $options[ 'fill' ] ?? false,
                'tension'         => $options[ 'tension' ] ?? 0.1
            ];
        }

        return $datasets;
    }

    /**
     * Gera opções do gráfico
     */
    private function generateChartOptions( string $type, array $options ): array
    {
        $defaultOptions = [
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'plugins'             => [
                'title'  => [
                    'display' => isset( $options[ 'title' ] ),
                    'text'    => $options[ 'title' ] ?? '',
                    'font'    => [
                        'size'   => 16,
                        'weight' => 'bold'
                    ]
                ],
                'legend' => [
                    'display'  => $options[ 'show_legend' ] ?? true,
                    'position' => 'top'
                ]
            ],
            'scales'              => $this->generateScales( $type, $options )
        ];

        // Opções específicas por tipo
        switch ( $type ) {
            case 'pie':
            case 'doughnut':
                $defaultOptions[ 'plugins' ][ 'tooltip' ] = [
                    'callbacks' => [
                        'label' => 'function(context) { return context.label + ": " + context.parsed + "%"; }'
                    ]
                ];
                break;

            case 'line':
                $defaultOptions[ 'interaction' ] = [
                    'intersect' => false,
                    'mode'      => 'index'
                ];
                break;
        }

        return $defaultOptions;
    }

    /**
     * Gera configuração de escalas
     */
    private function generateScales( string $type, array $options ): array
    {
        $scales = [];

        if ( in_array( $type, [ 'line', 'bar', 'scatter' ] ) ) {
            $scales[ 'x' ] = [
                'display' => true,
                'title'   => [
                    'display' => isset( $options[ 'x_title' ] ),
                    'text'    => $options[ 'x_title' ] ?? ''
                ],
                'grid'    => [
                    'display' => $options[ 'show_grid' ] ?? true
                ]
            ];

            $scales[ 'y' ] = [
                'display'     => true,
                'title'       => [
                    'display' => isset( $options[ 'y_title' ] ),
                    'text'    => $options[ 'y_title' ] ?? ''
                ],
                'grid'        => [
                    'display' => $options[ 'show_grid' ] ?? true
                ],
                'beginAtZero' => $options[ 'begin_at_zero' ] ?? true
            ];
        }

        return $scales;
    }

    /**
     * Formata label para exibição
     */
    private function formatLabel( $value ): string
    {
        if ( $value instanceof Carbon ) {
            return $value->format( 'd/m/Y' );
        }

        if ( is_numeric( $value ) ) {
            return number_format( $value, 0, ',', '.' );
        }

        return (string) $value;
    }

    /**
     * Obtém próxima cor da paleta
     */
    private function getNextColor( float $opacity = 1 ): string
    {
        static $colorIndex = 0;
        $colors = array_values( $this->chartJsColors );
        $color  = $colors[ $colorIndex % count( $colors ) ];

        $colorIndex++;

        if ( $opacity < 1 ) {
            return $this->addOpacityToColor( $color, $opacity );
        }

        return $color;
    }

    /**
     * Obtém cor com opacidade
     */
    private function getColorWithOpacity( string $colorName, float $opacity ): string
    {
        $color = $this->chartJsColors[ $colorName ] ?? $this->chartJsColors[ 'primary' ];
        return $this->addOpacityToColor( $color, $opacity );
    }

    /**
     * Adiciona opacidade à cor hexadecimal
     */
    private function addOpacityToColor( string $hexColor, float $opacity ): string
    {
        // Converter hex para RGB
        $hex = ltrim( $hexColor, '#' );
        $r   = hexdec( substr( $hex, 0, 2 ) );
        $g   = hexdec( substr( $hex, 2, 2 ) );
        $b   = hexdec( substr( $hex, 4, 2 ) );

        return "rgba({$r}, {$g}, {$b}, {$opacity})";
    }

    /**
     * Gera dados para gráfico de exemplo
     */
    public function generateSampleData( string $type, int $count = 10 ): Collection
    {
        $data = collect();

        switch ( $type ) {
            case 'line':
            case 'area':
                for ( $i = 0; $i < $count; $i++ ) {
                    $data->push( [
                        'date'  => now()->subDays( $count - $i )->format( 'Y-m-d' ),
                        'value' => rand( 100, 1000 )
                    ] );
                }
                break;

            case 'bar':
                $labels = [ 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez' ];
                foreach ( array_slice( $labels, 0, $count ) as $label ) {
                    $data->push( [
                        'label' => $label,
                        'value' => rand( 100, 1000 )
                    ] );
                }
                break;

            case 'pie':
                $categories = [ 'Categoria A', 'Categoria B', 'Categoria C', 'Categoria D', 'Categoria E' ];
                foreach ( array_slice( $categories, 0, $count ) as $category ) {
                    $data->push( [
                        'label' => $category,
                        'value' => rand( 10, 100 )
                    ] );
                }
                break;

            case 'scatter':
                for ( $i = 0; $i < $count; $i++ ) {
                    $data->push( [
                        'x' => rand( 0, 100 ),
                        'y' => rand( 0, 100 )
                    ] );
                }
                break;
        }

        return $data;
    }

    /**
     * Obtém tipos de gráfico disponíveis
     */
    public function getAvailableChartTypes(): array
    {
        return $this->chartTypes;
    }

    /**
     * Valida configuração de gráfico
     */
    public function validateChartConfig( string $type, array $options ): array
    {
        $errors = [];

        if ( !isset( $this->chartTypes[ $type ] ) ) {
            $errors[] = "Tipo de gráfico '{$type}' não é suportado";
        }

        // Validações específicas por tipo
        switch ( $type ) {
            case 'line':
            case 'area':
                if ( !isset( $options[ 'x_field' ] ) ) {
                    $errors[] = 'Campo X é obrigatório para gráficos de linha/área';
                }
                if ( !isset( $options[ 'y_field' ] ) ) {
                    $errors[] = 'Campo Y é obrigatório para gráficos de linha/área';
                }
                break;

            case 'bar':
                if ( !isset( $options[ 'x_field' ] ) ) {
                    $errors[] = 'Campo X é obrigatório para gráficos de barras';
                }
                if ( !isset( $options[ 'y_field' ] ) ) {
                    $errors[] = 'Campo Y é obrigatório para gráficos de barras';
                }
                break;

            case 'pie':
                if ( !isset( $options[ 'label_field' ] ) ) {
                    $errors[] = 'Campo de label é obrigatório para gráficos de pizza';
                }
                if ( !isset( $options[ 'value_field' ] ) ) {
                    $errors[] = 'Campo de valor é obrigatório para gráficos de pizza';
                }
                break;
        }

        return $errors;
    }

    /**
     * Gera código JavaScript para gráfico interativo
     */
    public function generateInteractiveScript( string $chartId, array $config ): string
    {
        return View::make( 'reports.charts.interactive_script', [
            'chartId' => $chartId,
            'config'  => $config
        ] )->render();
    }

    /**
     * Gera gráfico responsivo
     */
    public function generateResponsiveChart( string $type, Collection $data, array $options = [] ): string
    {
        $options[ 'responsive' ] = true;
        return $this->renderChart( $type, $data, $options );
    }

    /**
     * Gera gráfico para dashboard executivo
     */
    public function generateExecutiveDashboard( Collection $kpiData ): array
    {
        $charts = [];

        // Gráfico de KPIs principais
        $charts[ 'kpis' ] = $this->generateKpiChart( $kpiData );

        // Gráfico de tendência mensal
        $charts[ 'monthly_trend' ] = $this->generateAreaChart( $kpiData, [
            'title'   => 'Tendência Mensal',
            'x_field' => 'month',
            'y_field' => 'value',
            'fill'    => true
        ] );

        // Gráfico de distribuição por categoria
        $charts[ 'category_distribution' ] = $this->generatePieChart( $kpiData, [
            'title'       => 'Distribuição por Categoria',
            'label_field' => 'category',
            'value_field' => 'total'
        ] );

        return $charts;
    }

    /**
     * Gera gráfico de comparação período a período
     */
    public function generatePeriodComparison( Collection $currentData, Collection $previousData, array $options = [] ): array
    {
        $datasets = [
            [
                'label'           => $options[ 'current_label' ] ?? 'Período Atual',
                'data'            => $currentData->pluck( $options[ 'y_field' ] ?? 'value' )->toArray(),
                'borderColor'     => $this->chartJsColors[ 'primary' ],
                'backgroundColor' => $this->getColorWithOpacity( 'primary', 0.1 ),
                'fill'            => false
            ],
            [
                'label'           => $options[ 'previous_label' ] ?? 'Período Anterior',
                'data'            => $previousData->pluck( $options[ 'y_field' ] ?? 'value' )->toArray(),
                'borderColor'     => $this->chartJsColors[ 'secondary' ],
                'backgroundColor' => $this->getColorWithOpacity( 'secondary', 0.1 ),
                'fill'            => false,
                'borderDash'      => [ 5, 5 ]
            ]
        ];

        return [
            'type'    => 'line',
            'data'    => [
                'labels'   => $this->extractLabels( $currentData, $options ),
                'datasets' => $datasets
            ],
            'options' => $this->generateChartOptions( 'line', $options )
        ];
    }

    /**
     * Gera gráfico de funil de vendas
     */
    public function generateFunnelChart( Collection $data, array $options = [] ): array
    {
        return [
            'type'    => 'bar',
            'data'    => [
                'labels'   => $data->pluck( 'stage' )->toArray(),
                'datasets' => [ [
                    'label'           => 'Quantidade',
                    'data'            => $data->pluck( 'count' )->toArray(),
                    'backgroundColor' => [
                        $this->chartJsColors[ 'primary' ],
                        $this->chartJsColors[ 'secondary' ],
                        $this->chartJsColors[ 'warning' ],
                        $this->chartJsColors[ 'danger' ],
                        $this->chartJsColors[ 'info' ]
                    ]
                ] ]
            ],
            'options' => array_merge( $this->generateChartOptions( 'bar', $options ), [
                'indexAxis' => 'y',
                'plugins'   => [
                    'legend' => [ 'display' => false ]
                ]
            ] )
        ];
    }

    /**
     * Gera gráfico de mapa de calor
     */
    public function generateHeatmapChart( Collection $data, array $options = [] ): array
    {
        return $this->generateD3Config( 'heatmap', $data, array_merge( [
            'x_field'      => 'x',
            'y_field'      => 'y',
            'value_field'  => 'value',
            'color_scheme' => 'interpolateBlues',
            'dimensions'   => [
                'width'  => 600,
                'height' => 400,
                'margin' => [ 'top' => 20, 'right' => 20, 'bottom' => 60, 'left' => 60 ]
            ]
        ], $options ) );
    }

    /**
     * Gera gráfico de gauge/meter
     */
    public function generateGaugeChart( float $value, float $max = 100, array $options = [] ): array
    {
        $percentage = ( $value / $max ) * 100;

        return [
            'type'        => 'doughnut',
            'data'        => [
                'labels'   => [ 'Valor Atual', 'Restante' ],
                'datasets' => [ [
                    'data'            => [ $value, $max - $value ],
                    'backgroundColor' => [
                        $this->getGaugeColor( $percentage ),
                        '#E5E7EB'
                    ],
                    'borderWidth'     => 0
                ] ]
            ],
            'options'     => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'cutout'              => '70%',
                'plugins'             => [
                    'legend'  => [ 'display' => false ],
                    'tooltip' => [ 'enabled' => false ]
                ]
            ],
            'center_text' => [
                'value'      => $value,
                'max'        => $max,
                'percentage' => $percentage,
                'label'      => $options[ 'label' ] ?? ''
            ]
        ];
    }

    /**
     * Obtém cor baseada no valor do gauge
     */
    private function getGaugeColor( float $percentage ): string
    {
        if ( $percentage >= 80 ) return $this->chartJsColors[ 'success' ];
        if ( $percentage >= 60 ) return $this->chartJsColors[ 'primary' ];
        if ( $percentage >= 40 ) return $this->chartJsColors[ 'warning' ];
        return $this->chartJsColors[ 'danger' ];
    }

    /**
     * Gera gráfico de combinação (barras + linha)
     */
    public function generateComboChart( Collection $data, array $barOptions = [], array $lineOptions = [] ): array
    {
        $barData  = $data->pluck( $barOptions[ 'y_field' ] ?? 'bar_value' )->toArray();
        $lineData = $data->pluck( $lineOptions[ 'y_field' ] ?? 'line_value' )->toArray();

        return [
            'type'    => 'bar',
            'data'    => [
                'labels'   => $this->extractLabels( $data, $barOptions ),
                'datasets' => [
                    [
                        'type'            => 'bar',
                        'label'           => $barOptions[ 'label' ] ?? 'Barras',
                        'data'            => $barData,
                        'backgroundColor' => $this->chartJsColors[ $barOptions[ 'color' ] ?? 'primary' ]
                    ],
                    [
                        'type'            => 'line',
                        'label'           => $lineOptions[ 'label' ] ?? 'Linha',
                        'data'            => $lineData,
                        'borderColor'     => $this->chartJsColors[ $lineOptions[ 'color' ] ?? 'secondary' ],
                        'backgroundColor' => 'transparent',
                        'yAxisID'         => 'y1'
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'scales'     => [
                    'y'  => [
                        'type'     => 'linear',
                        'display'  => true,
                        'position' => 'left'
                    ],
                    'y1' => [
                        'type'     => 'linear',
                        'display'  => true,
                        'position' => 'right',
                        'grid'     => [ 'drawOnChartArea' => false ]
                    ]
                ]
            ]
        ];
    }

}
