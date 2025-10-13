<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Budget;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BudgetRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Budget();
    }

    /**
     * Lista budgets por status dentro do tenant atual.
     *
     * @param array<string> $statuses Lista de status
     * @param array<string, string>|null $orderBy Ordenação
     * @param int|null $limit Limite de registros
     * @return \Illuminate\Database\Eloquent\Collection<int, Budget> Budgets encontrados
     */
    public function listByStatuses( array $statuses, ?array $orderBy = null, ?int $limit = null ): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getAllByTenant(
            [ 'status' => $statuses ],
            $orderBy,
            $limit,
        );
    }

    /**
     * Conta budgets por status dentro do tenant atual.
     *
     * @param string $status Status dos budgets
     * @return int Número de budgets
     */
    public function countByStatus( string $status ): int
    {
        return $this->countByTenant( [ 'status' => $status ] );
    }

    /**
     * Lista budgets por cliente dentro do tenant atual.
     *
     * @param int $customerId ID do cliente
     * @param array<string, string>|null $orderBy Ordenação
     * @return \Illuminate\Database\Eloquent\Collection<int, Budget> Budgets do cliente
     */
    public function listByCustomerId( int $customerId, ?array $orderBy = null ): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getAllByTenant(
            [ 'customer_id' => $customerId ],
            $orderBy,
        );
    }

    /**
     * Lista budgets com filtros (compatibilidade com service).
     *
     * @param array<string, mixed> $filters Filtros a aplicar
     * @param array<string, string>|null $orderBy Ordenação
     * @param int|null $limit Limite de registros
     * @param int|null $offset Offset para paginação
     * @return \Illuminate\Database\Eloquent\Collection<int, Budget> Budgets filtrados
     */
    public function listByFilters( array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getAllByTenant( $filters, $orderBy, $limit, $offset );
    }

    // ========== MÉTODOS BASEADOS NO SISTEMA ANTIGO ==========

    /**
     * Busca último orçamento por mês para geração de código dentro do tenant atual.
     *
     * @param string $year Ano (YYYY)
     * @param string $month Mês (MM)
     * @return Budget|null Último orçamento do período
     */
    public function getLastBudgetByMonth( string $year, string $month ): ?Budget
    {
        return $this->model->whereYear( 'created_at', $year )
            ->whereMonth( 'created_at', $month )
            ->orderBy( 'code', 'desc' )
            ->first();
    }

    /**
     * Busca orçamento com todos os detalhes (relacionamentos) dentro do tenant atual.
     *
     * @param int $budgetId ID do orçamento
     * @return Budget|null Orçamento com relacionamentos carregados
     */
    public function getBudgetWithFullDetails( int $budgetId ): ?Budget
    {
        return $this->model->with( [
            'customer:id,name,email,phone,document',
            'category:id,name,color',
            'items:id,budget_id,description,quantity,price,total',
            'user:id,name,email',
            'activities' => function ( $query ) {
                $query->orderBy( 'created_at', 'desc' )->limit( 10 );
            },
            'payments:id,budget_id,amount,payment_date,status',
            'invoices:id,budget_id,invoice_number,amount,status'
        ] )
            ->find( $budgetId );
    }

    /**
     * Lista orçamentos com filtros avançados dentro do tenant atual.
     *
     * @param array<string, mixed> $filters Filtros a aplicar
     * @return \Illuminate\Database\Eloquent\Builder Query builder com filtros aplicados
     */
    public function getFilteredBudgets( array $filters = [] ): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->model->with( [ 'customer:id,name', 'category:id,name,color', 'user:id,name' ] );

        // Usa o método applyFilters do trait para aplicar filtros
        $this->applyFilters( $query, $filters );

        // Ordenação
        $sortBy    = $filters[ 'sort_by' ] ?? 'created_at';
        $sortOrder = $filters[ 'sort_order' ] ?? 'desc';
        $query->orderBy( $sortBy, $sortOrder );

        return $query;
    }

    /**
     * Busca orçamento por slug dentro do tenant atual.
     *
     * @param string $slug Slug do orçamento
     * @return Budget|null Orçamento encontrado
     */
    public function findBySlug( string $slug ): ?Budget
    {
        return $this->model->with( [ 'customer', 'category', 'items', 'user' ] )
            ->where( 'slug', $slug )
            ->first();
    }

    /**
     * Lista orçamentos ativos dentro do tenant atual.
     *
     * @param int $limit Limite de registros
     * @return \Illuminate\Database\Eloquent\Collection<int, Budget> Orçamentos ativos
     */
    public function getActiveBudgets( int $limit = 10 ): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->with( [ 'customer:id,name', 'category:id,name,color' ] )
            ->whereIn( 'status', [ 'pending', 'approved' ] )
            ->orderBy( 'created_at', 'desc' )
            ->limit( $limit )
            ->get();
    }

    /**
     * Busca orçamentos próximos ao vencimento dentro do tenant atual.
     *
     * @param int $days Dias para considerar como próximo ao vencimento
     * @return \Illuminate\Database\Eloquent\Collection<int, Budget> Orçamentos próximos ao vencimento
     */
    public function getBudgetsNearExpiration( int $days = 7 ): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->with( [ 'customer:id,name,email', 'user:id,name' ] )
            ->where( 'status', 'approved' )
            ->whereNotNull( 'expires_at' )
            ->whereBetween( 'expires_at', [ now(), now()->addDays( $days ) ] )
            ->orderBy( 'expires_at', 'asc' )
            ->get();
    }

    /**
     * Relatório de orçamentos por período.
     */
    public function getBudgetReportByPeriod( int $tenantId, string $startDate, string $endDate ): array
    {
        $budgets = $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->select( [
                'status',
                DB::raw( 'COUNT(*) as count' ),
                DB::raw( 'SUM(total) as total_amount' ),
                DB::raw( 'AVG(total) as avg_amount' )
            ] )
            ->whereBetween( 'created_at', [ $startDate, $endDate ] )
            ->groupBy( 'status' )
            ->get();

        $summary = [
            'total_budgets' => $budgets->sum( 'count' ),
            'total_amount'  => $budgets->sum( 'total_amount' ),
            'avg_amount'    => $budgets->avg( 'avg_amount' ),
            'by_status'     => $budgets->keyBy( 'status' )->toArray()
        ];

        return $summary;
    }

    /**
     * Busca orçamentos duplicados (mesmo cliente, valor similar).
     */
    public function findPotentialDuplicates( int $tenantId, int $customerId, float $amount, int $excludeId = null ): Collection
    {
        $query = $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->with( [ 'customer:id,name' ] )
            ->where( 'customer_id', $customerId )
            ->whereBetween( 'total', [ $amount * 0.95, $amount * 1.05 ] ) // 5% de tolerância
            ->where( 'created_at', '>=', now()->subDays( 30 ) ); // Últimos 30 dias

        if ( $excludeId ) {
            $query->where( 'id', '!=', $excludeId );
        }

        return $query->get();
    }

    /**
     * Atualiza status em lote.
     */
    public function bulkUpdateStatus( array $budgetIds, string $status, int $tenantId, int $userId ): int
    {
        return $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->whereIn( 'id', $budgetIds )
            ->update( [
                'status'            => $status,
                'status_updated_at' => now(),
                'status_updated_by' => $userId,
                'updated_at'        => now()
            ] );
    }

    /**
     * Busca orçamentos por código.
     */
    public function findByCode( string $code, int $tenantId ): ?Budget
    {
        return $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->with( [ 'customer', 'category', 'items' ] )
            ->where( 'code', $code )
            ->first();
    }

    /**
     * Lista orçamentos com paginação customizada.
     */
    public function getPaginatedBudgets( int $tenantId, array $filters = [], int $perPage = 15 ): LengthAwarePaginator
    {
        $query = $this->getFilteredBudgets( $tenantId, $filters );

        return $query->paginate( $perPage, [
            'id', 'code', 'title', 'status', 'total', 'created_at',
            'customer_id', 'category_id', 'user_id'
        ] );
    }

    /**
     * Estatísticas de conversão de orçamentos.
     */
    public function getConversionStats( int $tenantId, string $period = '30 days' ): array
    {
        $startDate = now()->sub( $period );

        $stats = $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->select( [
                DB::raw( 'COUNT(*) as total' ),
                DB::raw( 'SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved' ),
                DB::raw( 'SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed' ),
                DB::raw( 'SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected' ),
                DB::raw( 'AVG(total) as avg_value' ),
                DB::raw( 'SUM(total) as total_value' )
            ] )
            ->where( 'created_at', '>=', $startDate )
            ->first();

        $conversionRate = $stats->total > 0 ? ( $stats->approved / $stats->total ) * 100 : 0;
        $completionRate = $stats->approved > 0 ? ( $stats->completed / $stats->approved ) * 100 : 0;

        return [
            'total_budgets'     => $stats->total,
            'approved_budgets'  => $stats->approved,
            'completed_budgets' => $stats->completed,
            'rejected_budgets'  => $stats->rejected,
            'conversion_rate'   => round( $conversionRate, 2 ),
            'completion_rate'   => round( $completionRate, 2 ),
            'average_value'     => $stats->avg_value,
            'total_value'       => $stats->total_value,
            'period'            => $period
        ];
    }

}
