<?php

namespace app\database\models;

use app\database\entities\ProviderEntity;
use app\database\Model;
use core\dbal\Entity;
use Doctrine\DBAL\ParameterType;
use Exception;
use RuntimeException;

/**
 * Classe Provider
 *
 * Esta classe representa o modelo de dados para os prestadores de serviço no sistema.
 * Ela estende a classe Model e fornece métodos específicos para operações relacionadas aos prestadores.
 *
 * @package app\database\models
 */
class Provider extends Model
{
    /**
     * O nome da tabela associada ao modelo.
     *
     * @var string
     */
    protected string $table = 'providers';

    /**
     * Cria uma nova entidade Provider a partir de um array de dados.
     *
     * @param array $data Os dados para criar a entidade
     * @return Entity A entidade Provider criada
     */
    protected static function createEntity(array $data): Entity
    {
        return ProviderEntity::create($data);
    }

    /**
     * Obtém um prestador de serviço com base no ID do usuário e ID do tenant.
     *
     * @param int $user_id O ID do usuário
     * @param int $tenant_id O ID do tenant
     * @return array|Entity|ProviderEntity O prestador de serviço encontrado
     */
    public function getProviderByUserId(int $user_id, int $tenant_id): array|Entity|ProviderEntity
    {
        try {
            $entity = $this->findBy([ 'user_id' => $user_id, 'tenant_id' => $tenant_id ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar prestador, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

        return $entity;
    }

    /**
     * Obtém um prestador de serviço com base no ID  e ID do tenant.
     *
     * @param int $id O ID do prestador
     * @param int $tenant_id O ID do tenant
     * @return array|Entity|ProviderEntity O prestador de serviço encontrado
     */
    public function getProviderById(int $id, int $tenant_id): array|Entity|ProviderEntity
    {
        try {
            $entity = $this->findBy([ 'id' => $id, 'tenant_id' => $tenant_id ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar prestador, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

        return $entity;
    }

    /**
     * Obtém um prestador de serviço com base no ID do usuário e ID do tenant.
     *
     * @param int $user_id O ID do usuário
     * @param int $tenant_id O ID do tenant
     * @return object O prestador de serviço encontrado
     */
    public function getProviderFullByUserId(int $user_id, int $tenant_id): object
    {
        try {
            $result = $this->connection->createQueryBuilder()
                ->select('
                    p.id,
                    p.terms_accepted,
                    p.created_at,
                    p.updated_at,
                    t.id as tenant_id,
                    t.name,
                    u.id as user_id,
                    u.email as user_email,
                    u.is_active,
                    u.password,
                    u.logo,
                    cd.id as common_data_id,
                    cd.first_name,
                    cd.last_name,
                    cd.birth_date,
                    cd.cnpj,
                    cd.cpf,
                    cd.company_name,
                    cd.description,
                    aoa.id as area_of_activity_id,
                    aoa.slug as area_of_activity_slug,
                    aoa.name as area_of_activity_name,
                    prof.id as profession_id,
                    prof.slug as profession_slug,
                    prof.name as profession_name,
                    c.id as contact_id,
                    c.email,
                    c.phone,
                    c.email_business,
                    c.phone_business,
                    c.website,
                    a.id as address_id,
                    a.address,
                    a.address_number,
                    a.neighborhood,
                    a.city,
                    a.state,
                    a.cep')
                ->from($this->table, 'p')
                ->join('p', 'tenants', 't', 'p.tenant_id = t.id')
                ->join('p', 'users', 'u', 'p.user_id = u.id and p.tenant_id = u.tenant_id')
                ->join('p', 'common_datas', 'cd', 'p.common_data_id = cd.id and p.tenant_id = cd.tenant_id')
                ->leftjoin('cd', 'areas_of_activity', 'aoa', 'cd.area_of_activity_id = aoa.id ')
                ->leftjoin('cd', 'professions', 'prof', 'cd.profession_id = prof.id ')
                ->join('p', 'contacts', 'c', 'p.contact_id = c.id and p.tenant_id = c.tenant_id')
                ->join('p', 'addresses', 'a', 'p.address_id = a.id and p.tenant_id = a.tenant_id')
                ->where('p.user_id = :user_id')
                ->andWhere('p.tenant_id = :tenant_id')
                ->setParameter('user_id', $user_id, ParameterType::INTEGER)
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->executeQuery()
                ->fetchAssociative();

            if (!$result) {
                return new \core\dbal\EntityNotFound();
            }

            return (object) $result;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar prestador, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    /**
     * Retrieve a provider by their email address along with their password.
     *
     * @param string $email The email address of the provider.
     * @return object The user object containing user details and password.
     */
    public function getProviderFullByEmail(string $email): object
    {
        try {
            $result = $this->connection->createQueryBuilder()
                ->select('
                    p.id,
                    p.terms_accepted,
                    p.created_at,
                    p.updated_at,
                    t.id as tenant_id,
                    t.name,
                    u.id as user_id,
                    u.email as user_email,
                    u.is_active,
                    u.password,
                    u.logo,
                    cd.id as common_data_id,
                    cd.first_name,
                    cd.last_name,
                    cd.birth_date,
                    cd.cnpj,
                    cd.cpf,
                    cd.company_name,
                    cd.description,
                    aoa.id as area_of_activity_id,
                    aoa.slug as area_of_activity_slug,
                    aoa.name as area_of_activity_name,
                    prof.id as profession_id,
                    prof.slug as profession_slug,
                    prof.name as profession_name,
                    c.id as contact_id,
                    c.email,
                    c.phone,
                    c.email_business,
                    c.phone_business,
                    c.website,
                    a.id as address_id,
                    a.address,
                    a.address_number,
                    a.neighborhood,
                    a.city,
                    a.state,
                    a.cep')
                ->from($this->table, 'p')
                ->join('p', 'tenants', 't', 'p.tenant_id = t.id')
                ->join('p', 'users', 'u', 'p.user_id = u.id and p.tenant_id = u.tenant_id')
                ->join('p', 'common_datas', 'cd', 'p.common_data_id = cd.id and p.tenant_id = cd.tenant_id')
                ->leftjoin('cd', 'areas_of_activity', 'aoa', 'cd.area_of_activity_id = aoa.id ')
                ->leftjoin('cd', 'professions', 'prof', 'cd.profession_id = prof.id ')
                ->join('p', 'contacts', 'c', 'p.contact_id = c.id and p.tenant_id = c.tenant_id')
                ->join('p', 'addresses', 'a', 'p.address_id = a.id and p.tenant_id = a.tenant_id')
                ->where('u.email = :email')
                ->setParameter('email', $email, ParameterType::STRING)
                ->executeQuery()
                ->fetchAssociative();

            if (!$result) {
                return new \core\dbal\EntityNotFound();
            }

            return (object) $result;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar email, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    /**
     * Obtém os papéis (roles) de um prestador.
     *
     * @param int $user_id O ID do usuário
     * @param int $tenant_id O ID do tenant
     * @return array Um array com os nomes dos papéis do prestador
     */
    public function getProviderRoles(int $user_id, int $tenant_id): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $result = $queryBuilder
            ->select('r.name')
            ->from('user_roles', 'ur')
            ->join('ur', 'roles', 'r', 'ur.role_id = r.id')
            ->where('ur.user_id = :user_id')
            ->andWhere('ur.tenant_id = :tenant_id')
            ->setParameter('user_id', $user_id, ParameterType::INTEGER)
            ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_column($result, 'name');
    }

    /**
     * Obtém as permissões de um prestador.
     *
     * @param int $user_id O ID do usuário
     * @param int $tenant_id O ID do tenant
     * @return array Um array com os nomes das permissões do prestador
     */
    public function getProviderPermissions(int $user_id, int $tenant_id): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $result = $queryBuilder
            ->select('*')
            ->from('user_roles', 'ur')
            ->join('ur', 'role_permissions', 'rp', 'ur.role_id = rp.role_id')
            ->join('rp', 'permissions', 'p', 'rp.permission_id = p.id')
            ->where('ur.user_id = :user_id')
            ->andWhere('ur.tenant_id = :tenant_id')
            ->setParameter('user_id', $user_id, ParameterType::INTEGER)
            ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_column($result, 'name');
    }

}
