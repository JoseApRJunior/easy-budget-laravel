<?php

/* The PlanSubscription class extends the Model class and provides methods for managing plan subscriptions, including
checking provider plans and retrieving active subscriptions by provider and tenant. */

namespace app\database\models;

use app\database\entities\PlanSubscriptionEntity;
use app\database\entities\PlansWithPlanSubscriptionEntity;
use app\database\Model;
use core\dbal\Entity;
use core\dbal\EntityNotFound;
use Doctrine\DBAL\ParameterType;
use Exception;
use RuntimeException;

/**
 * The PlanSubscription class extends the Model class and provides methods for managing plan subscriptions.
 *
 * The `checkProviderPlan` method retrieves the active plan subscription for a given provider and tenant, including the plan details.
 *
 * The `getActiveByProviderAndPlan` method retrieves all active plan subscriptions for a given provider and tenant, excluding cancelled subscriptions.
 */
class PlanSubscription extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'plan_subscriptions';

    /**
     * Creates a new PlanSubscriptionEntity instance from the provided data array.
     *
     * @param array $data The data to use for creating the entity.
     * @return Entity The created PlanSubscriptionEntity instance.
     */
    protected static function createEntity(array $data): Entity
    {
        if (isset($data[ 'slug' ])) {
            return PlansWithPlanSubscriptionEntity::create($data);
        }

        return PlanSubscriptionEntity::create($data);
    }

    /**
     * Summary of getProviderPlan
     * @param int $provider_id
     * @param int $tenant_id
     * @throws \RuntimeException
     * @return PlansWithPlanSubscriptionEntity|Entity
     */
    public function getProviderPlan(int $provider_id, int $tenant_id, string $status = 'active'): PlansWithPlanSubscriptionEntity|Entity
    {
        try {
            $entityProviderPlan = $this->findByJoins(
                [
                    'provider_id' => $provider_id,
                    'tenant_id' => $tenant_id,
                    'main.status' => $status,
                ],
                [
                    [
                        'type' => 'innerJoin',
                        'table' => 'plans',
                        'alias' => 'p',
                        'condition' => 'main.plan_id = p.id',
                    ],
                ],
                [ 'main.start_date' => 'DESC' ],
                1,
                null,
                [
                    'main.id',
                    'main.tenant_id',
                    'main.provider_id',
                    'main.plan_id',
                    'main.status',
                    'main.transaction_amount',
                    'main.end_date',
                    'p.name',
                    'p.slug',
                ],
            );

            return $entityProviderPlan;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o plano do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getPlanSubscriptionId(int $provider_id, int $tenant_id, int $id): PlanSubscriptionEntity|Entity
    {
        try {
            return $this->findBy([
                'provider_id' => $provider_id,
                'tenant_id' => $tenant_id,
                'id' => $id,
            ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o plano do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    /**
     * Busca todas as assinaturas com detalhes das tabelas relacionadas (planos, provedores, usuários).
     *
     * @param string|null $status Filtro opcional por status.
     * @return array Um array de objetos de assinatura com informações detalhadas.
     */
    public function findAllWithDetails(?string $status = null): array
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();

            $queryBuilder
                ->select(
                    'ps.id',
                    'ps.status',
                    'ps.start_date',
                    'ps.end_date',
                    'ps.transaction_amount',
                    'ps.provider_id',
                    'ps.tenant_id',
                    'p.name AS plan_name',
                    'p.slug',
                    "CONCAT(cd.first_name, ' ', cd.last_name) AS provider_name",
                )
                ->from($this->table, 'ps')
                ->join('ps', 'plans', 'p', 'ps.plan_id = p.id')
                ->join('ps', 'providers', 'pr', 'ps.provider_id = pr.id')
                ->join('pr', 'common_datas', 'cd', 'pr.common_data_id = cd.id')
                ->orderBy('ps.created_at', 'DESC');

            if ($status) {
                $queryBuilder
                    ->where('ps.status = :status')
                    ->setParameter('status', $status, ParameterType::STRING);
            }

            return $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar os detalhes das assinaturas.", 0, $e);
        }
    }

    /**
     * Busca uma única assinatura pelo seu ID com detalhes das tabelas relacionadas.
     *
     * @param int $subscriptionId O ID da assinatura a ser encontrada.
     * @param string|null $status Filtro opcional por status.
     * @return object O objeto da assinatura ou EntityNotFound se não encontrada.
     */
    public function findSubscriptionWithDetailsById(int $subscriptionId, ?string $status = null): object
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();

            $queryBuilder
                ->select(
                    'ps.id',
                    'ps.status',
                    'ps.start_date',
                    'ps.end_date',
                    'ps.transaction_amount',
                    'ps.provider_id',
                    'ps.tenant_id',
                    'p.name AS plan_name',
                    'p.slug',
                    "CONCAT(cd.first_name, ' ', cd.last_name) AS provider_name",
                )
                ->from($this->table, 'ps')
                ->join('ps', 'plans', 'p', 'ps.plan_id = p.id')
                ->join('ps', 'providers', 'pr', 'ps.provider_id = pr.id')
                ->join('pr', 'common_datas', 'cd', 'pr.common_data_id = cd.id')
                ->where('ps.id = :subscriptionId')
                ->setParameter('subscriptionId', $subscriptionId, ParameterType::INTEGER);

            if ($status) {
                $queryBuilder
                    ->andWhere('ps.status = :status')
                    ->setParameter('status', $status, ParameterType::STRING);
            }

            $result = $queryBuilder->executeQuery()->fetchAssociative();

            if (!$result) {
                return EntityNotFound::create([]);
            }

            return (object) $result;
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar os detalhes da assinatura.", 0, $e);
        }
    }

    /**
     * Finds all subscriptions for a specific provider, with details.
     *
     * @param int $providerId The ID of the provider.
     * @return array An array of subscription objects.
     */
    public function findAllWithDetailsByProvider(int $providerId): array
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();

            $queryBuilder
                ->select(
                    'ps.id',
                    'ps.status',
                    'ps.start_date',
                    'ps.end_date',
                    'ps.transaction_amount',
                    'ps.provider_id',
                    'ps.tenant_id',
                    'p.name AS plan_name',
                    'p.slug',
                    "CONCAT(cd.first_name, ' ', cd.last_name) AS provider_name",
                )
                ->from($this->table, 'ps')
                ->join('ps', 'plans', 'p', 'ps.plan_id = p.id')
                ->join('ps', 'providers', 'pr', 'ps.provider_id = pr.id')
                ->join('pr', 'common_datas', 'cd', 'pr.common_data_id = cd.id')
                ->where('ps.provider_id = :providerId')
                ->setParameter('providerId', $providerId, ParameterType::INTEGER)
                ->orderBy('ps.created_at', 'DESC');

            return $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o histórico de assinaturas do provedor.", 0, $e);
        }
    }

}
