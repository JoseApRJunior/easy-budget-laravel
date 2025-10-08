<?php

namespace app\database\models;

use app\database\entities\InvoiceStatusesEntity;
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

    public function getAllStatuses(): array
    {
        try {
            return $this->findAll();
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar os status da fatura, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

}
