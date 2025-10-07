<?php

namespace app\database\services;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class FinancialSummary
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function getMonthlySummary(int $tenantId): array
    {
        $currentMonth = date('Y-m');

        // Faturamento Mensal (Orçamentos em Andamento e Concluídos)
        $monthlyRevenue = $this->connection->createQueryBuilder()
            ->select('COALESCE(SUM(total), 0) as total')
            ->from('budgets', 'b')
            ->join('b', 'budget_statuses', 'bs', 'b.budget_statuses_id = bs.id')
            ->where('tenant_id = :tenant_id')
            ->andWhere("bs.slug IN ('IN_PROGRESS', 'COMPLETED')")
            ->andWhere('DATE_FORMAT(b.updated_at, "%Y-%m") = :current_month')
            ->setParameter('tenant_id', $tenantId, ParameterType::INTEGER)
            ->setParameter('current_month', $currentMonth, ParameterType::STRING)
            ->executeQuery()
            ->fetchOne();

        // Orçamentos Pendentes
        $pendingBudgets = $this->connection->createQueryBuilder()
            ->select('COALESCE(SUM(total), 0) as total, COUNT(b.id) as count')
            ->from('budgets', 'b')
            ->join('b', 'budget_statuses', 'bs', 'b.budget_statuses_id = bs.id')
            ->where('tenant_id = :tenant_id')
            ->andWhere('bs.slug IN (:status1, :status2)')
            ->setParameter('tenant_id', $tenantId, ParameterType::INTEGER)
            ->setParameter('status1', 'DRAFT', ParameterType::STRING)
            ->setParameter('status2', 'PENDING', ParameterType::STRING)
            ->executeQuery()
            ->fetchAssociative();

        // Pagamentos Atrasados
        $overduePayments = $this->connection->createQueryBuilder()
            ->select('COALESCE(SUM(total), 0) as total, COUNT(b.id) as count')
            ->from('budgets', 'b')
            ->join('b', 'budget_statuses', 'bs', 'b.budget_statuses_id = bs.id')
            ->where('tenant_id = :tenant_id')
            ->andWhere('bs.slug = :status')
            ->andWhere('due_date < CURRENT_DATE()')
            ->setParameter('tenant_id', $tenantId, ParameterType::INTEGER)
            ->setParameter('status', 'PENDING', ParameterType::STRING)
            ->executeQuery()
            ->fetchAssociative();

        // Projeção para o próximo mês
        $nextMonthProjection = $this->connection->createQueryBuilder()
            ->select('COALESCE(SUM(total), 0) as total')
            ->from('budgets', 'b')
            ->join('b', 'budget_statuses', 'bs', 'b.budget_statuses_id = bs.id')
            ->where('tenant_id = :tenant_id')
            ->andWhere('bs.slug IN (:status1, :status2, :status3, :status4)')
            ->andWhere('DATE_FORMAT(due_date, "%Y-%m") = :next_month')
            ->setParameter('tenant_id', $tenantId, ParameterType::INTEGER)
            ->setParameter('next_month', $currentMonth, ParameterType::STRING)
            ->setParameter('status1', 'DRAFT', ParameterType::STRING)
            ->setParameter('status2', 'PENDING', ParameterType::STRING)
            ->setParameter('status3', 'APPROVED', ParameterType::STRING)
            ->setParameter('status4', 'IN_PROGRESS', ParameterType::STRING)
            ->executeQuery()
            ->fetchOne();

        return [
            'monthly_revenue' => $monthlyRevenue,
            'pending_budgets' => $pendingBudgets,
            'overdue_payments' => $overduePayments,
            'next_month_projection' => $nextMonthProjection,
        ];
    }

}
