<?php

namespace app\database\models;

use app\database\entitiesORM\CustomerEntity;
use app\database\entitiesORM\UserEntity;
use app\database\Model;
use core\dbal\Entity;
use Doctrine\DBAL\ParameterType;
use Exception;
use RuntimeException;

class Customer extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'customers';

    /**
     * Cria uma nova instância de CustomerEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de CustomerEntity.
     */
    protected static function createEntity(array $data): Entity
    {
        return CustomerEntity::create($data);
    }

    public function getUserByEmail(string $email): CustomerEntity|Entity
    {
        try {
            $fields = array_keys(get_class_vars(UserEntity::class));
            $fields = array_diff($fields, [ 'password' ]);
            $entity = $this->findBy(fields: $fields, criteria: [ 'email' => $email ]);

            return $entity;
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar email, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getUserByEmailWithPassword(string $email): UserEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'email' => $email ]);

            return $entity;
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar email, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    /**
     * Busca um cliente completo por ID.
     *
     * @param int $id ID do cliente
     * @param int $tenant_id ID do tenant
     * @return object|\core\dbal\EntityNotFound Objeto com dados do cliente ou EntityNotFound
     */
    public function getCustomerFullById(int $id, int $tenant_id): object
    {
        try {
            $result = $this->connection->createQueryBuilder()
                ->select(
                    '
                cust.id AS id,
                cust.status AS status,
                cdat.id AS common_data_id,
                cdat.first_name AS first_name,
                cdat.last_name AS last_name,
                cdat.birth_date AS birth_date,
                cdat.cnpj AS cnpj,
                cdat.cpf AS cpf,
                cdat.company_name AS company_name,
                cdat.description AS description,
                aoat.id AS area_of_activity_id,
                aoat.name AS area_of_activity_name,
                aoat.slug AS area_of_activity_slug,
                prof.id AS profession_id,
                prof.name AS profession_name,
                prof.slug AS profession_slug,
                cont.id AS contact_id,
                cont.email AS email,
                cont.email_business AS email_business,
                cont.phone AS phone,
                cont.phone_business AS phone_business,
                cont.website AS website,
                addr.id AS address_id,
                addr.address AS address,
                addr.address_number AS address_number,
                addr.neighborhood AS neighborhood,
                addr.city AS city,
                addr.state AS state,
                addr.cep AS cep',
                )
                ->from($this->table, 'cust')
                ->join('cust', 'common_datas', 'cdat', 'cust.common_data_id = cdat.id and cust.tenant_id = cdat.tenant_id')
                ->leftjoin('cdat', 'areas_of_activity', 'aoat', 'cdat.area_of_activity_id = aoat.id')
                ->leftjoin('cdat', 'professions', 'prof', 'cdat.profession_id = prof.id')
                ->join('cust', 'contacts', 'cont', 'cust.contact_id = cont.id and cust.tenant_id = cont.tenant_id')
                ->join('cust', 'addresses', 'addr', 'cust.address_id = addr.id and cust.tenant_id = addr.tenant_id')
                ->where('cust.tenant_id = :tenant_id')
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->where('cust.id = :id')
                ->setParameter('id', $id, ParameterType::INTEGER)
                ->executeQuery()
                ->fetchAssociative();

            if (!$result) {
                return new \core\dbal\EntityNotFound();
            }

            return (object) $result;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar as funções do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    /**
     * Busca um cliente completo por código de serviço.
     *
     * @param string $code Código do serviço
     * @param int $tenant_id ID do tenant
     * @return object|\core\dbal\EntityNotFound Objeto com dados do cliente ou EntityNotFound
     */
    public function getCustomerFullByServiceCode(string $code, int $tenant_id): object
    {
        try {
            $result = $this->connection->createQueryBuilder()
                ->select(
                    '
                cust.id AS id,
                cust.status AS status,
                cdat.id AS common_data_id,
                cdat.first_name AS first_name,
                cdat.last_name AS last_name,
                cdat.birth_date AS birth_date,
                cdat.cnpj AS cnpj,
                cdat.cpf AS cpf,
                cdat.company_name AS company_name,
                cdat.description AS description,
                aoat.id AS area_of_activity_id,
                aoat.name AS area_of_activity_name,
                aoat.slug AS area_of_activity_slug,
                prof.id AS profession_id,
                prof.name AS profession_name,
                prof.slug AS profession_slug,
                cont.id AS contact_id,
                cont.email AS email,
                cont.email_business AS email_business,
                cont.phone AS phone,
                cont.phone_business AS phone_business,
                cont.website AS website,
                addr.id AS address_id,
                addr.address AS address,
                addr.address_number AS address_number,
                addr.neighborhood AS neighborhood,
                addr.city AS city,
                addr.state AS state,
                addr.cep AS cep',
                )
                ->from($this->table, 'cust')
                ->join('cust', 'common_datas', 'cdat', 'cust.common_data_id = cdat.id and cust.tenant_id = cdat.tenant_id')
                ->leftjoin('cdat', 'areas_of_activity', 'aoat', 'cdat.area_of_activity_id = aoat.id')
                ->leftjoin('cdat', 'professions', 'prof', 'cdat.profession_id = prof.id')
                ->join('cust', 'contacts', 'cont', 'cust.contact_id = cont.id and cust.tenant_id = cont.tenant_id')
                ->join('cust', 'addresses', 'addr', 'cust.address_id = addr.id and cust.tenant_id = addr.tenant_id')
                ->join('cust', 'budgets', 'bud', 'cust.id = bud.customer_id and cust.tenant_id = bud.tenant_id')
                ->join('bud', 'services', 'serv', 'bud.id = serv.budget_id and bud.tenant_id = serv.tenant_id')
                ->where('cust.tenant_id = :tenant_id')
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->where('serv.code = :code')
                ->setParameter('code', $code, ParameterType::STRING)
                ->executeQuery()
                ->fetchAssociative();

            if (!$result) {
                return new \core\dbal\EntityNotFound();
            }

            return (object) $result;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar as funções do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getCustomerById(int $id, int $tenant_id): CustomerEntity|Entity
    {
        try {
            $result = $this->findBy([ 'id' => $id, 'tenant_id' => $tenant_id ]);

            return $result;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar as funções do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    /**
     * Busca todos os clientes.
     *
     * @param int $tenant_id ID do tenant
     * @return array<int, array<string, mixed>> Array com todos os clientes
     */
    public function getAllCustomers(int $tenant_id): array
    {

        try {
            $entityCustomers = $this->connection->createQueryBuilder()
                ->select('cust.id', 'cdat.first_name', 'cdat.last_name', 'cont.email', 'cont.phone', 'cdat.cpf', 'cdat.cnpj')
                ->from($this->table, 'cust')
                ->join('cust', 'common_datas', 'cdat', 'cust.common_data_id = cdat.id and cust.tenant_id = cdat.tenant_id')
                ->join('cust', 'contacts', 'cont', 'cust.contact_id = cont.id and cust.tenant_id = cont.tenant_id')
                ->where('cust.tenant_id = :tenant_id')
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->executeQuery()
                ->fetchAllAssociative();

            return $entityCustomers;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar as funções do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

    }

    /**
     * Busca clientes por filtros.
     *
     * @param array<string, mixed> $data Dados dos filtros
     * @param int $tenant_id ID do tenant
     * @return array<int, array<string, mixed>> Array com clientes filtrados
     */
    public function getCustomersByFilter(array $data, int $tenant_id): array
    {
        try {
            // Validação dos dados de entrada
            $data = $this->validateFilterData($data);

            // Inicializa o query builder
            $queryBuilder = $this->connection->createQueryBuilder();

            // Seleciona campos específicos para performance

            $queryBuilder
                ->select(
                    'cust.id',
                    'cust.created_at',
                    'cdat.first_name',
                    'cdat.last_name',
                    'cdat.cnpj',
                    'cdat.cpf',
                    'cont.email',
                    'cont.email_business',
                    'cont.phone',
                    'cont.phone_business',
                )
                ->from($this->table, 'cust')
                ->join('cust', 'contacts', 'cont', 'cust.contact_id = cont.id and cust.tenant_id = cont.tenant_id')
                ->join('cust', 'common_datas', 'cdat', 'cust.common_data_id = cdat.id and cust.tenant_id = cdat.tenant_id')
                ->where('cust.tenant_id = :tenant_id')
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER);

            // Aplica os filtros de forma segura
            $this->applyFilters($queryBuilder, $data);

            // Ordenação e limite
            $queryBuilder
                ->orderBy('cust.created_at', 'DESC')
                ->setMaxResults(100);

            // Execute a query e retorne os resultados
            $result = $queryBuilder->executeQuery()->fetchAllAssociative();

            // Formata os resultados
            return $this->formatResults($result);

        } catch (Exception $e) {
            // Log do erro
            error_log("Erro ao buscar orçamentos: " . $e->getMessage());

            throw new RuntimeException(
                "Falha ao buscar os orçamentos. Por favor, tente novamente mais tarde.",
                0,
                $e,
            );
        }
    }

    /**
     * Valida e sanitiza os dados do filtro.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @return array<string, mixed> Dados validados
     */
    private function validateFilterData(array $data): array
    {
        return [
            'search' => isset($data[ 'search' ]) ? trim($data[ 'search' ]) : null,
        ];
    }

    /**
     * Aplica os filtros na query de forma segura.
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $queryBuilder Query builder
     * @param array<string, mixed> $data Dados dos filtros
     * @return void
     */
    private function applyFilters($queryBuilder, array $data): void
    {
        // Filtro por cliente (nome,email,email_business, CPF ou CNPJ)
        if (!empty($data[ 'search' ])) {
            $searchTerm = '%' . $data[ 'search' ] . '%';
            $queryBuilder->andWhere('(
        cdat.first_name LIKE :search_term OR
        cdat.last_name LIKE :search_term OR
        cdat.cpf LIKE :search_term OR
        cdat.cnpj LIKE :search_term OR
        CONCAT(cdat.first_name, " ", cdat.last_name) LIKE :search_term OR
        cont.email LIKE :search_term OR
        cont.email_business LIKE :search_term
    )')->setParameter('search_term', $searchTerm);
        }
    }

    /**
     * Formata os resultados para exibição.
     *
     * @param array<int, array<string, mixed>> $results Resultados brutos
     * @return array<int, array<string, mixed>> Resultados formatados
     */
    private function formatResults(array $results): array
    {
        return array_map(function ($row) {
            return [
                'id' => (int) $row[ 'id' ],
                'customer_name' => $row[ 'first_name' ] . ' ' . $row[ 'last_name' ],
                'cpf' => $row[ 'cpf' ],
                'cnpj' => $row[ 'cnpj' ],
                'email' => $row[ 'email' ],
                'email_business' => $row[ 'email_business' ],
                'phone' => $row[ 'phone' ],
                'phone_business' => $row[ 'phone_business' ],
                'created_at' => $row[ 'created_at' ],
            ];
        }, $results);
    }

}
