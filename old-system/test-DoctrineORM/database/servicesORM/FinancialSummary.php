<?php

namespace app\database\servicesORM;

use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Exception;

/**
 * Serviço para cálculo de resumos financeiros.
 */
class FinancialSummary implements ServiceNoTenantInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /**
     * Busca um resumo financeiro pelo ID.
     *
     * @param int $id ID do resumo
     * @return ServiceResult Resultado da operação
     */
    public function getById( int $id ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'Método getById não aplicável para FinancialSummary.' );
    }

    /**
     * Lista resumos financeiros.
     *
     * @param array<string, mixed> $filters Filtros opcionais
     * @return ServiceResult Resultado da operação
     */
    public function list( array $filters = [] ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'Método list não aplicável para FinancialSummary.' );
    }

    /**
     * Cria um novo resumo financeiro.
     *
     * @param array<string, mixed> $data Dados para criação
     * @return ServiceResult Resultado da operação
     */
    public function create( array $data ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'Método create não aplicável para FinancialSummary.' );
    }

    /**
     * Atualiza um resumo financeiro.
     *
     * @param int $id ID do resumo
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function update( int $id, array $data ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'Método update não aplicável para FinancialSummary.' );
    }

    /**
     * Remove um resumo financeiro.
     *
     * @param int $id ID do resumo
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'Método delete não aplicável para FinancialSummary.' );
    }

    /**
     * Valida os dados de entrada.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'Método validate não aplicável para FinancialSummary.' );
    }

    /**
     * Obtém o resumo financeiro mensal.
     *
     * @param int $tenantId ID do tenant
     * @return ServiceResult Resumo financeiro mensal
     */
    public function getMonthlySummary( int $tenantId ): ServiceResult
    {
        try {
            $currentMonth = date( 'Y-m' );

            // Faturamento Mensal (Orçamentos em Andamento e Concluídos)
            $monthlyRevenueResult = $this->connection->createQueryBuilder()
                ->select( 'COALESCE(SUM(total), 0) as total' )
                ->from( 'budgets', 'b' )
                ->join( 'b', 'budget_statuses', 'bs', 'b.budget_statuses_id = bs.id' )
                ->where( 'tenant_id = :tenant_id' )
                ->andWhere( "bs.slug IN ('IN_PROGRESS', 'COMPLETED')" )
                ->andWhere( 'DATE_FORMAT(b.updated_at, "%Y-%m") = :current_month' )
                ->setParameter( 'tenant_id', $tenantId, ParameterType::INTEGER )
                ->setParameter( 'current_month', $currentMonth, ParameterType::STRING )
                ->executeQuery()
                ->fetchOne();

            // Verificar se o resultado é null
            $monthlyRevenue = $monthlyRevenueResult !== null ? $monthlyRevenueResult : 0;

            // Orçamentos Pendentes
            $pendingBudgetsResult = $this->connection->createQueryBuilder()
                ->select( 'COALESCE(SUM(total), 0) as total, COUNT(b.id) as count' )
                ->from( 'budgets', 'b' )
                ->join( 'b', 'budget_statuses', 'bs', 'b.budget_statuses_id = bs.id' )
                ->where( 'tenant_id = :tenant_id' )
                ->andWhere( 'bs.slug IN (:status1, :status2)' )
                ->setParameter( 'tenant_id', $tenantId, ParameterType::INTEGER )
                ->setParameter( 'status1', 'DRAFT', ParameterType::STRING )
                ->setParameter( 'status2', 'PENDING', ParameterType::STRING )
                ->executeQuery()
                ->fetchAssociative();

            // Verificar se o resultado é null
            $pendingBudgets = $pendingBudgetsResult !== null ? $pendingBudgetsResult : [ 'total' => 0, 'count' => 0 ];

            // Pagamentos Atrasados
            $overduePaymentsResult = $this->connection->createQueryBuilder()
                ->select( 'COALESCE(SUM(total), 0) as total, COUNT(b.id) as count' )
                ->from( 'budgets', 'b' )
                ->join( 'b', 'budget_statuses', 'bs', 'b.budget_statuses_id = bs.id' )
                ->where( 'tenant_id = :tenant_id' )
                ->andWhere( 'bs.slug = :status' )
                ->andWhere( 'due_date < CURRENT_DATE()' )
                ->setParameter( 'tenant_id', $tenantId, ParameterType::INTEGER )
                ->setParameter( 'status', 'PENDING', ParameterType::STRING )
                ->executeQuery()
                ->fetchAssociative();

            // Verificar se o resultado é null
            $overduePayments = $overduePaymentsResult !== null ? $overduePaymentsResult : [ 'total' => 0, 'count' => 0 ];

            // Projeção para o próximo mês
            $nextMonthProjectionResult = $this->connection->createQueryBuilder()
                ->select( 'COALESCE(SUM(total), 0) as total' )
                ->from( 'budgets', 'b' )
                ->join( 'b', 'budget_statuses', 'bs', 'b.budget_statuses_id = bs.id' )
                ->where( 'tenant_id = :tenant_id' )
                ->andWhere( 'bs.slug IN (:status1, :status2, :status3, :status4)' )
                ->andWhere( 'DATE_FORMAT(due_date, "%Y-%m") = :next_month' )
                ->setParameter( 'tenant_id', $tenantId, ParameterType::INTEGER )
                ->setParameter( 'next_month', $currentMonth, ParameterType::STRING )
                ->setParameter( 'status1', 'DRAFT', ParameterType::STRING )
                ->setParameter( 'status2', 'PENDING', ParameterType::STRING )
                ->setParameter( 'status3', 'APPROVED', ParameterType::STRING )
                ->setParameter( 'status4', 'IN_PROGRESS', ParameterType::STRING )
                ->executeQuery()
                ->fetchOne();

            // Verificar se o resultado é null
            $nextMonthProjection = $nextMonthProjectionResult !== null ? $nextMonthProjectionResult : 0;

            $summary = [ 
                'monthly_revenue'       => $monthlyRevenue,
                'pending_budgets'       => $pendingBudgets,
                'overdue_payments'      => $overduePayments,
                'next_month_projection' => $nextMonthProjection,
            ];

            return ServiceResult::success( $summary, 'Resumo financeiro obtido com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao obter resumo financeiro: ' . $e->getMessage() );
        }
    }

}
