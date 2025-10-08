<?php

namespace app\database\models;

use app\database\entities\ReportEntity;
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

    public function findByHash(string $hash, int $tenant_id): array
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
