<?php

declare(strict_types=1);

namespace app\database\servicesORM;

use app\database\entitiesORM\BudgetEntity;
use app\database\entitiesORM\UserEntity;
use app\interfaces\ServiceCustomInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * Serviço para detecção de anomalias em dados de IA
 */
class AnomalyDetectionService implements ServiceCustomInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Obtém alertas ativos de anomalias
     *
     * Detecta anomalias em budgets e users, como valores extremos ou padrões incomuns.
     */
    public function getActiveAlerts(): array
    {
        try {
            $alerts = [];

            // Anomalias em budgets (ex: valores muito altos ou baixos)
            $budgets = $this->entityManager->getRepository( BudgetEntity::class)->findAll();
            foreach ( $budgets as $budget ) {
                $total = $budget->getTotal() ?? 0;
                if ( $total > 100000 || $total < 0 ) { // Thresholds simples
                    $alerts[] = [ 
                        'id'         => $budget->getId(),
                        'type'       => 'budget_anomaly',
                        'message'    => "Orçamento com valor anômalo: R$ " . number_format( $total, 2 ),
                        'severity'   => 'WARNING',
                        'entity'     => 'Budget',
                        'created_at' => $budget->getCreatedAt()->format( 'Y-m-d H:i:s' )
                    ];
                }
            }

            // Anomalias em users (ex: atividade suspeita, mas simples por agora)
            $users = $this->entityManager->getRepository( UserEntity::class)->findAll();
            foreach ( $users as $user ) {
                // Exemplo: user sem budgets mas ativo há muito tempo
                // Implementar lógica real conforme necessário
            }

            return $alerts;

        } catch ( Exception $e ) {
            error_log( "Erro na detecção de anomalias: " . $e->getMessage() );
            return [];
        }
    }

    /**
     * Detecta anomalias específicas em um budget
     */
    public function detectBudgetAnomalies( BudgetEntity $budget ): array
    {
        $anomalies = [];
        $total     = $budget->getTotal() ?? 0;

        if ( $total > 50000 ) {
            $anomalies[] = 'Valor total excessivamente alto';
        }

        if ( $total === 0 ) {
            $anomalies[] = 'Orçamento sem valor definido';
        }

        return $anomalies;
    }

    /**
     * Resolve uma anomalia (marcar como resolvida)
     */
    public function resolveAnomaly( int $anomalyId ): bool
    {
        // Implementar persistência se necessário (ex: tabela de anomalias)
        // Por agora, log e return true
        error_log( "Anomalia ID {$anomalyId} resolvida" );
        return true;
    }

}
