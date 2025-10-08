<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportDefinition;
use App\Models\ReportExecution;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Serviço avançado para geração de relatórios
 * Utiliza o AdvancedQueryBuilder para construir consultas complexas
 */
class ReportGenerationService
{
    private AdvancedQueryBuilder $queryBuilder;
    private ReportCacheService   $cacheService;
    private array                $availableMetrics = [];

    public function __construct( AdvancedQueryBuilder $queryBuilder, ReportCacheService $cacheService )
    {
        $this->queryBuilder = $queryBuilder;
        $this->cacheService = $cacheService;
        $this->initializeAvailableMetrics();
    }

    /**
     * Gera relatório baseado na definição
     */
    public function generateReport( ReportDefinition $definition, array $filters = [], array $options = [] ): array
    {
        $executionId = 'exec_' . uniqid() . '_' . time();

        try {
            // Registrar início da execução
            $execution = $this->createExecutionRecord( $definition, $executionId, $filters );

            // Construir query baseada na definição
            $queryBuilder = $this->buildQueryFromDefinition( $definition, $filters );

            // Executar consulta com cache inteligente
            $data = $this->executeQueryWithCache( $definition, $queryBuilder, $filters );

            // Processar dados conforme configuração
            $processedData = $this->processData( $data, $definition->config );

            // Aplicar transformações e cálculos
            $finalData = $this->applyTransformations( $processedData, $definition->config );

            // Registrar conclusão da execução
            $this->completeExecution( $execution, $finalData );

            return [
                'success'      => true,
                'execution_id' => $executionId,
                'data'         => $finalData,
                'metadata'     => [
                    'total_records'  => count( $finalData ),
                    'execution_time' => microtime( true ) - $execution->executed_at->timestamp,
                    'generated_at'   => now()->toISOString()
                ]
            ];

        } catch ( Exception $e ) {
            $this->failExecution( $executionId, $e->getMessage() );
            throw $e;
        }
    }

    /**
     * Gera relatório financeiro de receitas por período
     */
    public function generateRevenueReport( array $filters = [] ): array
    {
        $cacheKey = 'revenue_report_' . md5( serialize( $filters ) );

        return Cache::remember( $cacheKey, 3600, function () use ($filters) {
            $query = $this->queryBuilder->reset()
                ->from( 'budgets' )
                ->select( 'DATE(budgets.created_at) as date' )
                ->selectRaw( 'SUM(budgets.total_value) as total_revenue' )
                ->selectRaw( 'COUNT(budgets.id) as budget_count' )
                ->where( 'budgets.status', '=', 'approved' )
                ->where( 'budgets.total_value', '>', 0 );

            // Aplicar filtros de período
            if ( isset( $filters[ 'start_date' ] ) ) {
                $query->where( 'budgets.created_at', '>=', $filters[ 'start_date' ] );
            }
            if ( isset( $filters[ 'end_date' ] ) ) {
                $query->where( 'budgets.created_at', '<=', $filters[ 'end_date' ] );
            }

            $query->groupBy( 'DATE(budgets.created_at)' )
                ->orderBy( 'date', 'DESC' );

            $data = $query->get();

            return [
                'type'    => 'revenue',
                'data'    => $data,
                'summary' => [
                    'total_revenue'   => $data->sum( 'total_revenue' ),
                    'total_budgets'   => $data->sum( 'budget_count' ),
                    'average_revenue' => $data->avg( 'total_revenue' )
                ]
            ];
        } );
    }

    /**
     * Gera relatório financeiro de despesas por categoria
     */
    public function generateExpensesByCategoryReport( array $filters = [] ): array
    {
        $cacheKey = 'expenses_category_report_' . md5( serialize( $filters ) );

        return Cache::remember( $cacheKey, 3600, function () use ($filters) {
            $query = $this->queryBuilder->reset()
                ->from( 'budget_items' )
                ->select( 'budget_item_categories.name as category' )
                ->selectRaw( 'SUM(budget_items.quantity * budget_items.unit_price) as total_expenses' )
                ->selectRaw( 'COUNT(budget_items.id) as item_count' )
                ->leftJoin( 'budget_item_categories', 'budget_items.category_id', '=', 'budget_item_categories.id' )
                ->leftJoin( 'budgets', 'budget_items.budget_id', '=', 'budgets.id' );

            // Aplicar filtros
            if ( isset( $filters[ 'start_date' ] ) ) {
                $query->where( 'budgets.created_at', '>=', $filters[ 'start_date' ] );
            }
            if ( isset( $filters[ 'end_date' ] ) ) {
                $query->where( 'budgets.created_at', '<=', $filters[ 'end_date' ] );
            }

            $query->groupBy( 'budget_item_categories.name' )
                ->orderBy( 'total_expenses', 'DESC' );

            $data = $query->get();

            return [
                'type'    => 'expenses_by_category',
                'data'    => $data,
                'summary' => [
                    'total_expenses'   => $data->sum( 'total_expenses' ),
                    'total_items'      => $data->sum( 'item_count' ),
                    'categories_count' => $data->count()
                ]
            ];
        } );
    }

    /**
     * Gera relatório de lucro líquido
     */
    public function generateProfitReport( array $filters = [] ): array
    {
        $cacheKey = 'profit_report_' . md5( serialize( $filters ) );

        return Cache::remember( $cacheKey, 3600, function () use ($filters) {
            // Receitas
            $revenueQuery = DB::table( 'budgets' )
                ->selectRaw( 'DATE(created_at) as date, SUM(total_value) as revenue' )
                ->where( 'status', 'approved' )
                ->where( 'total_value', '>', 0 )
                ->groupBy( 'DATE(created_at)' );

            // Despesas (simplificado - ajustar conforme estrutura real)
            $expensesQuery = DB::table( 'budget_items' )
                ->selectRaw( 'DATE(budgets.created_at) as date, SUM(budget_items.quantity * budget_items.unit_price) as expenses' )
                ->leftJoin( 'budgets', 'budget_items.budget_id', '=', 'budgets.id' )
                ->where( 'budgets.status', 'approved' )
                ->groupBy( 'DATE(budgets.created_at)' );

            // Aplicar filtros de período
            if ( isset( $filters[ 'start_date' ] ) ) {
                $revenueQuery->where( 'budgets.created_at', '>=', $filters[ 'start_date' ] );
                $expensesQuery->where( 'budgets.created_at', '>=', $filters[ 'start_date' ] );
            }
            if ( isset( $filters[ 'end_date' ] ) ) {
                $revenueQuery->where( 'budgets.created_at', '<=', $filters[ 'end_date' ] );
                $expensesQuery->where( 'budgets.created_at', '<=', $filters[ 'end_date' ] );
            }

            $revenues = $revenueQuery->get();
            $expenses = $expensesQuery->get();

            // Combinar dados por data
            $profitData = collect();

            foreach ( $revenues as $revenue ) {
                $expense = $expenses->where( 'date', $revenue->date )->first();
                $profitData->push( [
                    'date'     => $revenue->date,
                    'revenue'  => $revenue->revenue,
                    'expenses' => $expense->expenses ?? 0,
                    'profit'   => ( $revenue->revenue ) - ( $expense->expenses ?? 0 )
                ] );
            }

            return [
                'type'    => 'profit',
                'data'    => $profitData->sortByDesc( 'date' )->values(),
                'summary' => [
                    'total_revenue'  => $profitData->sum( 'revenue' ),
                    'total_expenses' => $profitData->sum( 'expenses' ),
                    'total_profit'   => $profitData->sum( 'profit' ),
                    'profit_margin'  => $profitData->sum( 'revenue' ) > 0 ?
                        ( $profitData->sum( 'profit' ) / $profitData->sum( 'revenue' ) ) * 100 : 0
                ]
            ];
        } );
    }

    /**
     * Gera relatório de segmentação de clientes
     */
    public function generateCustomerSegmentationReport( array $filters = [] ): array
    {
        $cacheKey = 'customer_segmentation_report_' . md5( serialize( $filters ) );

        return Cache::remember( $cacheKey, 3600, function () use ($filters) {
            $query = $this->queryBuilder->reset()
                ->from( 'customers' )
                ->select( 'customers.type as segment' )
                ->selectRaw( 'COUNT(customers.id) as customer_count' )
                ->selectRaw( 'AVG(TIMESTAMPDIFF(YEAR, customers.created_at, NOW())) as avg_age_years' );

            // Aplicar filtros
            if ( isset( $filters[ 'customer_type' ] ) ) {
                $query->where( 'customers.type', '=', $filters[ 'customer_type' ] );
            }

            $query->groupBy( 'customers.type' )
                ->orderBy( 'customer_count', 'DESC' );

            $data = $query->get();

            return [
                'type'    => 'customer_segmentation',
                'data'    => $data,
                'summary' => [
                    'total_customers' => $data->sum( 'customer_count' ),
                    'segments_count'  => $data->count(),
                    'largest_segment' => $data->sortByDesc( 'customer_count' )->first()
                ]
            ];
        } );
    }

    /**
     * Gera relatório de análise de interações com clientes
     */
    public function generateCustomerInteractionsReport( array $filters = [] ): array
    {
        $cacheKey = 'customer_interactions_report_' . md5( serialize( $filters ) );

        return Cache::remember( $cacheKey, 3600, function () use ($filters) {
            $query = $this->queryBuilder->reset()
                ->from( 'customer_interactions' )
                ->select( 'DATE(customer_interactions.created_at) as date' )
                ->selectRaw( 'COUNT(customer_interactions.id) as interaction_count' )
                ->selectRaw( 'COUNT(DISTINCT customer_interactions.customer_id) as unique_customers' )
                ->leftJoin( 'customers', 'customer_interactions.customer_id', '=', 'customers.id' );

            // Aplicar filtros de período
            if ( isset( $filters[ 'start_date' ] ) ) {
                $query->where( 'customer_interactions.created_at', '>=', $filters[ 'start_date' ] );
            }
            if ( isset( $filters[ 'end_date' ] ) ) {
                $query->where( 'customer_interactions.created_at', '<=', $filters[ 'end_date' ] );
            }

            $query->groupBy( 'DATE(customer_interactions.created_at)' )
                ->orderBy( 'date', 'DESC' );

            $data = $query->get();

            return [
                'type'    => 'customer_interactions',
                'data'    => $data,
                'summary' => [
                    'total_interactions'                => $data->sum( 'interaction_count' ),
                    'total_unique_customers'            => $data->sum( 'unique_customers' ),
                    'average_interactions_per_customer' => $data->sum( 'unique_customers' ) > 0 ?
                        $data->sum( 'interaction_count' ) / $data->sum( 'unique_customers' ) : 0
                ]
            ];
        } );
    }

    /**
     * Gera relatório de status dos orçamentos
     */
    public function generateBudgetStatusReport( array $filters = [] ): array
    {
        $cacheKey = 'budget_status_report_' . md5( serialize( $filters ) );

        return Cache::remember( $cacheKey, 3600, function () use ($filters) {
            $query = $this->queryBuilder->reset()
                ->from( 'budgets' )
                ->select( 'budgets.status' )
                ->selectRaw( 'COUNT(budgets.id) as budget_count' )
                ->selectRaw( 'SUM(budgets.total_value) as total_value' )
                ->selectRaw( 'AVG(budgets.total_value) as average_value' );

            // Aplicar filtros
            if ( isset( $filters[ 'status' ] ) ) {
                $query->where( 'budgets.status', '=', $filters[ 'status' ] );
            }

            $query->groupBy( 'budgets.status' )
                ->orderBy( 'budget_count', 'DESC' );

            $data = $query->get();

            return [
                'type'    => 'budget_status',
                'data'    => $data,
                'summary' => [
                    'total_budgets' => $data->sum( 'budget_count' ),
                    'total_value'   => $data->sum( 'total_value' ),
                    'average_value' => $data->avg( 'average_value' )
                ]
            ];
        } );
    }

    /**
     * Gera relatório de performance de conversão de orçamentos
     */
    public function generateBudgetConversionReport( array $filters = [] ): array
    {
        $cacheKey = 'budget_conversion_report_' . md5( serialize( $filters ) );

        return Cache::remember( $cacheKey, 3600, function () use ($filters) {
            $query = $this->queryBuilder->reset()
                ->from( 'budgets' )
                ->selectRaw( 'DATE_FORMAT(budgets.created_at, "%Y-%m") as month' )
                ->selectRaw( 'COUNT(budgets.id) as total_budgets' )
                ->selectRaw( 'SUM(CASE WHEN budgets.status = "approved" THEN 1 ELSE 0 END) as approved_budgets' )
                ->selectRaw( 'SUM(CASE WHEN budgets.status = "rejected" THEN 1 ELSE 0 END) as rejected_budgets' )
                ->selectRaw( 'SUM(budgets.total_value) as total_value' )
                ->selectRaw( 'SUM(CASE WHEN budgets.status = "approved" THEN budgets.total_value ELSE 0 END) as approved_value' );

            // Aplicar filtros de período
            if ( isset( $filters[ 'start_date' ] ) ) {
                $query->where( 'budgets.created_at', '>=', $filters[ 'start_date' ] );
            }
            if ( isset( $filters[ 'end_date' ] ) ) {
                $query->where( 'budgets.created_at', '<=', $filters[ 'end_date' ] );
            }

            $query->groupBy( 'DATE_FORMAT(budgets.created_at, "%Y-%m")' )
                ->orderBy( 'month', 'DESC' );

            $data = $query->get();

            // Calcular taxas de conversão
            $data = $data->map( function ( $item ) {
                $item->conversion_rate = $item->total_budgets > 0 ?
                    ( $item->approved_budgets / $item->total_budgets ) * 100 : 0;
                $item->rejection_rate = $item->total_budgets > 0 ?
                    ( $item->rejected_budgets / $item->total_budgets ) * 100 : 0;
                return $item;
            } );

            return [
                'type'    => 'budget_conversion',
                'data'    => $data,
                'summary' => [
                    'total_budgets'           => $data->sum( 'total_budgets' ),
                    'total_approved'          => $data->sum( 'approved_budgets' ),
                    'total_rejected'          => $data->sum( 'rejected_budgets' ),
                    'overall_conversion_rate' => $data->sum( 'total_budgets' ) > 0 ?
                        ( $data->sum( 'approved_budgets' ) / $data->sum( 'total_budgets' ) ) * 100 : 0,
                    'total_value'             => $data->sum( 'total_value' ),
                    'approved_value'          => $data->sum( 'approved_value' )
                ]
            ];
        } );
    }

    /**
     * Gera dashboard executivo com KPIs principais
     */
    public function generateExecutiveKpisReport( array $filters = [] ): array
    {
        $cacheKey = 'executive_kpis_report_' . md5( serialize( $filters ) );

        return Cache::remember( $cacheKey, 1800, function () use ($filters) {
            $now          = now();
            $startOfMonth = $now->startOfMonth()->toDateString();
            $endOfMonth   = $now->endOfMonth()->toDateString();

            // KPIs financeiros
            $financialKpis = DB::select( "
                SELECT
                    SUM(CASE WHEN status = 'approved' THEN total_value ELSE 0 END) as monthly_revenue,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_budgets,
                    AVG(CASE WHEN status = 'approved' THEN total_value END) as avg_budget_value
                FROM budgets
                WHERE created_at BETWEEN ? AND ?
            ", [ $startOfMonth, $endOfMonth ] );

            // KPIs de clientes
            $customerKpis = DB::select( "
                SELECT
                    COUNT(*) as total_customers,
                    COUNT(CASE WHEN created_at >= ? THEN 1 END) as new_customers_this_month,
                    COUNT(CASE WHEN type = 'vip' THEN 1 END) as vip_customers
                FROM customers
            ", [ $startOfMonth ] );

            // KPIs de orçamentos
            $budgetKpis = DB::select( "
                SELECT
                    COUNT(*) as total_budgets,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_budgets,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_budgets,
                    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_budgets
                FROM budgets
                WHERE created_at BETWEEN ? AND ?
            ", [ $startOfMonth, $endOfMonth ] );

            return [
                'type'    => 'executive_kpis',
                'data'    => [
                    'financial' => $financialKpis[ 0 ] ?? [],
                    'customers' => $customerKpis[ 0 ] ?? [],
                    'budgets'   => $budgetKpis[ 0 ] ?? []
                ],
                'summary' => [
                    'period'       => $now->format( 'F Y' ),
                    'generated_at' => $now->toISOString()
                ]
            ];
        } );
    }

    /**
     * Cria registro de execução
     */
    private function createExecutionRecord( ReportDefinition $definition, string $executionId, array $filters ): ReportExecution
    {
        return ReportExecution::create( [
            'tenant_id'       => $definition->tenant_id,
            'definition_id'   => $definition->id,
            'user_id'         => auth()->id(),
            'execution_id'    => $executionId,
            'status'          => 'running',
            'parameters'      => request()->all(),
            'filters_applied' => $filters,
            'executed_at'     => now()
        ] );
    }

    /**
     * Constrói query baseada na definição
     */
    private function buildQueryFromDefinition( ReportDefinition $definition, array $filters ): AdvancedQueryBuilder
    {
        $queryBuilder = clone $this->queryBuilder;
        $queryBuilder->reset();

        $config = $definition->query_builder;

        // Aplicar configuração básica
        if ( isset( $config[ 'table' ] ) ) {
            $queryBuilder->from( $config[ 'table' ] );
        }

        // Aplicar selects
        if ( isset( $config[ 'selects' ] ) ) {
            foreach ( $config[ 'selects' ] as $select ) {
                $queryBuilder->select( $select[ 'field' ], $select[ 'alias' ] ?? null );
            }
        }

        // Aplicar agregações
        if ( isset( $config[ 'aggregations' ] ) ) {
            foreach ( $config[ 'aggregations' ] as $aggregation ) {
                $queryBuilder->aggregate(
                    $aggregation[ 'function' ],
                    $aggregation[ 'column' ],
                    $aggregation[ 'alias' ] ?? null
                );
            }
        }

        // Aplicar joins
        if ( isset( $config[ 'joins' ] ) ) {
            foreach ( $config[ 'joins' ] as $join ) {
                $queryBuilder->join(
                    $join[ 'table' ],
                    $join[ 'first' ],
                    $join[ 'operator' ],
                    $join[ 'second' ],
                    $join[ 'type' ] ?? 'INNER'
                );
            }
        }

        // Aplicar filtros da definição
        if ( isset( $config[ 'filters' ] ) ) {
            foreach ( $config[ 'filters' ] as $filter ) {
                $queryBuilder->where( $filter[ 'column' ], $filter[ 'operator' ], $filter[ 'value' ] );
            }
        }

        // Aplicar filtros dinâmicos
        foreach ( $filters as $key => $value ) {
            if ( !empty( $value ) ) {
                $queryBuilder->where( $key, '=', $value );
            }
        }

        // Aplicar agrupamento
        if ( isset( $config[ 'group_by' ] ) ) {
            foreach ( $config[ 'group_by' ] as $column ) {
                $queryBuilder->groupBy( $column );
            }
        }

        // Aplicar ordenação
        if ( isset( $config[ 'order_by' ] ) ) {
            foreach ( $config[ 'order_by' ] as $order ) {
                $queryBuilder->orderBy( $order[ 'column' ], $order[ 'direction' ] ?? 'ASC' );
            }
        }

        return $queryBuilder;
    }

    /**
     * Executa query com cache inteligente
     */
    private function executeQueryWithCache( ReportDefinition $definition, AdvancedQueryBuilder $queryBuilder, array $filters ): Collection
    {
        $cacheKey = $this->generateCacheKey( $definition, $filters );

        return $this->cacheService->getReportData( $cacheKey, function () use ($queryBuilder) {
            return $queryBuilder->get();
        } );
    }

    /**
     * Processa dados conforme configuração
     */
    private function processData( Collection $data, array $config ): Collection
    {
        // Aplicar formatação de dados
        if ( isset( $config[ 'formatters' ] ) ) {
            foreach ( $config[ 'formatters' ] as $formatter ) {
                $data = $data->map( function ( $item ) use ( $formatter ) {
                    $field = $formatter[ 'field' ];
                    $type = $formatter[ 'type' ];

                    if ( isset( $item->$field ) ) {
                        $item->$field = $this->formatValue( $item->$field, $type, $formatter[ 'options' ] ?? [] );
                    }

                    return $item;
                } );
            }
        }

        return $data;
    }

    /**
     * Aplica transformações aos dados
     */
    private function applyTransformations( Collection $data, array $config ): Collection
    {
        // Aplicar cálculos adicionais
        if ( isset( $config[ 'calculations' ] ) ) {
            $data = $this->applyCalculations( $data, $config[ 'calculations' ] );
        }

        // Aplicar filtros pós-processamento
        if ( isset( $config[ 'post_filters' ] ) ) {
            $data = $this->applyPostFilters( $data, $config[ 'post_filters' ] );
        }

        return $data;
    }

    /**
     * Formata valor conforme tipo
     */
    private function formatValue( $value, string $type, array $options = [] )
    {
        return match ( $type ) {
            'currency'   => 'R$ ' . number_format( $value, 2, ',', '.' ),
            'percentage' => number_format( $value, 2 ) . '%',
            'date'       => Carbon::parse( $value )->format( $options[ 'format' ] ?? 'd/m/Y' ),
            'datetime'   => Carbon::parse( $value )->format( $options[ 'format' ] ?? 'd/m/Y H:i:s' ),
            'number'     => number_format( $value, $options[ 'decimals' ] ?? 0, ',', '.' ),
            default      => $value
        };
    }

    /**
     * Aplica cálculos adicionais
     */
    private function applyCalculations( Collection $data, array $calculations ): Collection
    {
        return $data->map( function ( $item ) use ( $calculations ) {
            foreach ( $calculations as $calculation ) {
                $fieldName = $calculation[ 'field' ];
                $formula   = $calculation[ 'formula' ];

                // Substituir campos na fórmula
                $formula = preg_replace_callback( '/\{(\w+)\}/', function ( $matches ) use ( $item ) {
                    $field = $matches[ 1 ];
                    return isset( $item->$field ) ? $item->$field : 0;
                }, $formula );

                // Avaliar fórmula (atenção: usar com cuidado em produção)
                try {
                    $item->$fieldName = eval ( "return $formula;" );
                } catch ( Exception $e ) {
                    $item->$fieldName = 0;
                }
            }

            return $item;
        } );
    }

    /**
     * Aplica filtros pós-processamento
     */
    private function applyPostFilters( Collection $data, array $filters ): Collection
    {
        foreach ( $filters as $filter ) {
            $field    = $filter[ 'field' ];
            $operator = $filter[ 'operator' ];
            $value    = $filter[ 'value' ];

            $data = $data->filter( function ( $item ) use ( $field, $operator, $value ) {
                $itemValue = $item->$field ?? 0;

                return match ( $operator ) {
                    '>'     => $itemValue > $value,
                    '>='    => $itemValue >= $value,
                    '<'     => $itemValue < $value,
                    '<='    => $itemValue <= $value,
                    '='     => $itemValue == $value,
                    '!='    => $itemValue != $value,
                    default => true
                };
            } );
        }

        return $data;
    }

    /**
     * Gera chave de cache para o relatório
     */
    private function generateCacheKey( ReportDefinition $definition, array $filters ): string
    {
        $key = "report_{$definition->id}_" . md5( serialize( $filters ) );
        return $key;
    }

    /**
     * Completa execução com sucesso
     */
    private function completeExecution( ReportExecution $execution, Collection $data ): void
    {
        $execution->update( [
            'status'       => 'completed',
            'data_count'   => $data->count(),
            'completed_at' => now()
        ] );
    }

    /**
     * Marca execução como falhada
     */
    private function failExecution( string $executionId, string $errorMessage ): void
    {
        ReportExecution::where( 'execution_id', $executionId )
            ->update( [
                'status'        => 'failed',
                'error_message' => $errorMessage,
                'completed_at'  => now()
            ] );
    }

    /**
     * Inicializa métricas disponíveis
     */
    private function initializeAvailableMetrics(): void
    {
        $this->availableMetrics = [
            'revenue'         => 'Receitas',
            'expenses'        => 'Despesas',
            'profit'          => 'Lucro',
            'customers'       => 'Clientes',
            'budgets'         => 'Orçamentos',
            'conversion_rate' => 'Taxa de Conversão'
        ];
    }

    /**
     * Retorna métricas disponíveis
     */
    public function getAvailableMetrics(): array
    {
        return $this->availableMetrics;
    }

}
