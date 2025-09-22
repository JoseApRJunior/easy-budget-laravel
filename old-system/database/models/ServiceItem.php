<?php

namespace app\database\models;

use app\database\entitiesORM\ServiceItemEntity;
use app\database\Model;
use core\dbal\Entity;
use Doctrine\DBAL\ParameterType;
use Exception;
use RuntimeException;

class ServiceItem extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'service_items';

    /**
     * Cria uma nova instância de ServiceItemEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de ServiceItemEntity.
     */
    protected static function createEntity(array $data): Entity
    {
        return ServiceItemEntity::create($data);
    }

    public function getServiceItemById(int $id, int $tenant_id): ServiceItemEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'id' => $id, 'tenant_id' => $tenant_id ]);

            return $entity;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o item do serviço, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    /**
     * Busca todos os itens de serviço por ID do serviço.
     *
     * @param int $service_id ID do serviço
     * @param int $tenant_id ID do tenant
     * @return array<int, array<string, mixed>> Array com itens do serviço
     */
    public function getAllServiceItemsByIdService(int $service_id, int $tenant_id): array
    {
        try {
            $result = $this->connection->createQueryBuilder()
                ->select('*,serv_i.id AS id, prod.id AS product_id, (serv_i.unit_value * serv_i.quantity) AS total')
                ->from($this->table, 'serv_i')
                ->join('serv_i', 'products', 'prod', 'serv_i.product_id = prod.id and serv_i.tenant_id = prod.tenant_id')
                ->where('serv_i.tenant_id = :tenant_id')
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->where('serv_i.service_id = :service_id')
                ->setParameter('service_id', $service_id, ParameterType::INTEGER)
                ->executeQuery()
                ->fetchAllAssociative();

            return $result;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o item do serviço, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    /**
     * Busca todos os itens de serviço por ID do serviço com código do produto.
     *
     * @param int $service_id ID do serviço
     * @param int $tenant_id ID do tenant
     * @return array<int, array<string, mixed>> Array com itens do serviço e código do produto
     */
    public function getAllServiceItemsByIdServiceCodProd(int $service_id, int $tenant_id): array
    {
        try {
            $result = $this->connection->createQueryBuilder()
                ->select('*,serv_i.id AS id, prod.id AS product_id, (serv_i.unit_value * serv_i.quantity) AS total')
                ->from($this->table, 'serv_i')
                ->join('serv_i', 'products', 'prod', 'serv_i.product_id = prod.id and serv_i.tenant_id = prod.tenant_id')
                ->where('serv_i.tenant_id = :tenant_id')
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->where('serv_i.service_id = :service_id')
                ->setParameter('service_id', $service_id, ParameterType::INTEGER)
                ->executeQuery()
                ->fetchAllAssociative();

            return $result;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o item do serviço, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    /**
     * Busca todos os itens de serviço.
     *
     * @param int $tenant_id ID do tenant
     * @return array<int, Entity>|ServiceItemEntity|Entity Array com itens de serviço ou uma única entidade
     */
    public function getAllServiceItems(int $tenant_id): array|ServiceItemEntity|Entity
    {

        try {
            $entity = $this->findBy([ 'tenant_id' => $tenant_id ]);

            return $entity;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar os items do serviço, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

    }

}
