<?php

namespace app\database\models;

use app\database\entitiesORM\InvoiceStatusesEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class InvoiceStatuses extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'invoice_statuses';

    /**
     * Cria uma nova instância de InvoiceStatusesEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de InvoiceStatusesEntity.
     */
    protected static function createEntity(array $data): Entity
    {
        return InvoiceStatusesEntity::create($data);
    }

    public function getStatusBySlug(string $slug): InvoiceStatusesEntity|Entity
    {
        try {
            return $this->findBy([ 'slug' => $slug ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o status da fatura, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    /**
     * Busca todos os status de fatura.
     *
     * @return array<int, Entity>|Entity Array com todos os status ou uma única entidade
     */
    public function getAllStatuses(): array|Entity
    {
        try {
            return $this->findAllByTenant();
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar os status da fatura, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

}
