<?php

namespace app\database\models;

use app\database\entitiesORM\ReportEntity;
use app\database\Model;
use core\dbal\Entity;
use Doctrine\DBAL\ParameterType;
use Exception;
use RuntimeException;

class Report extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'reports';

    /**
     * Cria uma nova instância de ReportEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de ReportEntity.
     */
    protected static function createEntity(array $data): Entity
    {
        return ReportEntity::create($data);
    }

    /**
     * Retrieve an report by its ID and tenant ID.
     *
     * @param int $id The ID of the report.
     * @param int $tenant_id The ID of the tenant.
     * @return ReportEntity|Entity The report entity or a generic entity.
     */
    public function getReportById(int $id, int $tenant_id): ReportEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'id' => $id, 'tenant_id' => $tenant_id ]);

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar dados do relatório, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

        return $entity;
    }

    /**
     * Busca relatórios expirados.
     *
     * @param int $tenant_id ID do tenant
     * @return array<int, array<string, mixed>> Array com relatórios expirados
     */
    public function getExpiredReports(int $tenant_id): array
    {
        try {
            $entityCustomers = $this->connection->createQueryBuilder()
                ->select('*')
                ->from($this->table)
                ->where('expires_at < :expires_at')
                ->andWhere('tenant_id = :tenant_id')
                ->setParameter('expires_at', date('Y-m-d H:i:s'), ParameterType::STRING)
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->executeQuery()
                ->fetchAllAssociative();

            return $entityCustomers;
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar dados do relatório, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    /**
     * Busca relatório por hash.
     *
     * @param string $hash Hash do relatório
     * @param int $tenant_id ID do tenant
     * @return array<string, mixed>|false Dados do relatório ou false se não encontrado
     */
    public function findByHash(string $hash, int $tenant_id): array|false
    {
        try {
            $entityCustomers = $this->connection->createQueryBuilder()
                ->select('*')
                ->from($this->table)
                ->where('hash = :hash')
                ->andWhere('expires_at < :expires_at')
                ->andWhere('tenant_id = :tenant_id')
                ->setParameter('expires_at', date('Y-m-d H:i:s'), ParameterType::STRING)
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->setParameter('hash', $hash, ParameterType::STRING)
                ->executeQuery()
                ->fetchAssociative();

            return $entityCustomers;
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar dados do relatório, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

}
