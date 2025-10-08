<?php

namespace app\database\models;

use app\database\entitiesORM\ServiceEntity;
use app\database\Model;
use core\dbal\Entity;
use core\dbal\EntityNotFound;
use Doctrine\DBAL\ParameterType;
use Exception;
use RuntimeException;

class Service extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'services';

    /**
     * Cria uma nova instância de ServiceEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de ServiceEntity.
     */
    protected static function createEntity(array $data): Entity
    {
        return ServiceEntity::create($data);
    }

    public function getServiceById(int $id, int $tenant_id): ServiceEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'id' => $id, 'tenant_id' => $tenant_id ]);

            return $entity;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o serviço, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getServiceByCode(string $code, int $tenant_id): ServiceEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'code' => $code, 'tenant_id' => $tenant_id ]);

            return $entity;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o serviço, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    /**
     * Busca serviços por ID do orçamento.
     *
     * @param int $budget_id ID do orçamento
     * @param int $tenant_id ID do tenant
     * @return ServiceEntity|Entity|array<int, Entity> Serviços encontrados
     */
    public function getServiceByBudgetId(int $budget_id, int $tenant_id): ServiceEntity|Entity|array
    {
        try {
            $entity = $this->findBy([ 'budget_id' => $budget_id, 'tenant_id' => $tenant_id ]);

            return $entity;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o serviço, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    /**
     * Busca o último código de serviço.
     *
     * @param int $budget_id ID do orçamento
     * @param int $tenant_id ID do tenant
     * @return string|null Último código ou null
     */
    public function getLastCode(int $budget_id, int $tenant_id): ?string
    {

        try {

            $result = $this->connection->createQueryBuilder()
                ->select('MAX(code) as last_code')
                ->from($this->table)
                ->where('tenant_id = :tenant_id')
                ->andWhere('budget_id = :budget_id')
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->setParameter('budget_id', $budget_id, ParameterType::INTEGER)
                ->executeQuery()
                ->fetchOne();

            return $result;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar as funções do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

    }

    /**
     * Busca um serviço completo por ID.
     *
     * @param int $id ID do serviço
     * @param int $tenant_id ID do tenant
     * @return object|EntityNotFound Objeto com dados do serviço ou EntityNotFound
     */
    public function getServiceFullById(int $id, int $tenant_id): object
    {
        try {

            $result = $this->connection->createQueryBuilder()
                ->select(
                    'serv.id AS id',
                    'serv.code',
                    'serv.description',
                    'serv.discount',
                    'serv.total',
                    'serv.due_date',
                    'serv.created_at',
                    'serv.updated_at',
                    'serv.pdf_verification_hash',
                    'cat.id AS category_id',
                    'cat.name AS category_name',
                    'stat.id AS status_id',
                    'stat.name AS status_name',
                    'stat.color AS status_color',
                    'stat.icon AS status_icon',
                    'stat.slug AS status_slug',
                    'bud.id AS budget_id',
                    'bud.code AS budget_code',
                    'cust.id AS customer_id',
                    'CONCAT(cdat.first_name, " ", cdat.last_name) AS customer_name',
                    'cdat.cpf AS customer_cpf',
                    'cdat.cnpj AS customer_cnpj',
                )
                ->from($this->table, 'serv')
                ->join('serv', 'categories', 'cat', 'serv.category_id = cat.id')
                ->join('serv', 'service_statuses', 'stat', 'serv.service_statuses_id = stat.id')
                ->join('serv', 'budgets', 'bud', 'serv.budget_id = bud.id AND serv.tenant_id = bud.tenant_id')
                ->join('bud', 'customers', 'cust', 'bud.customer_id = cust.id AND bud.tenant_id = cust.tenant_id')
                ->join('cust', 'common_datas', 'cdat', 'cust.common_data_id = cdat.id AND cust.tenant_id = cdat.tenant_id')
                ->where('serv.tenant_id = :tenant_id')
                ->andWhere('serv.id = :service_id')
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->setParameter('service_id', $id, ParameterType::INTEGER)
                ->executeQuery()
                ->fetchAssociative();

            if (!$result) {
                return new EntityNotFound();
            }

            return (object) $result;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o serviço, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getServiceFullByCode(string $code, int $tenant_id): object
    {
        try {

            $result = $this->connection->createQueryBuilder()
                ->select(
                    'serv.tenant_id',
                    'serv.id',
                    'serv.code',
                    'serv.description',
                    'serv.discount',
                    'serv.total',
                    'serv.due_date',
                    'serv.created_at',
                    'serv.updated_at',
                    'serv.pdf_verification_hash',
                    'cat.id AS category_id',
                    'cat.name AS category_name',
                    'stat.id AS service_statuses_id',
                    'stat.id AS status_id',
                    'stat.name AS status_name',
                    'stat.color AS status_color',
                    'stat.icon AS status_icon',
                    'stat.slug AS status_slug',
                    'stat.description AS status_description',
                    'bud.id AS budget_id',
                    'bud.code AS budget_code',
                    'cust.id AS customer_id',
                    'CONCAT(cdat.first_name, " ", cdat.last_name) AS customer_name',
                    'cdat.cpf AS customer_cpf',
                    'cdat.cnpj AS customer_cnpj',
                )
                ->from($this->table, 'serv')
                ->join('serv', 'categories', 'cat', 'serv.category_id = cat.id ')
                ->join('serv', 'service_statuses', 'stat', 'serv.service_statuses_id = stat.id')
                ->join('serv', 'budgets', 'bud', 'serv.budget_id = bud.id AND serv.tenant_id = bud.tenant_id')
                ->join('bud', 'customers', 'cust', 'bud.customer_id = cust.id AND bud.tenant_id = cust.tenant_id')
                ->join('cust', 'common_datas', 'cdat', 'cust.common_data_id = cdat.id AND cust.tenant_id = cdat.tenant_id')
                ->where('serv.tenant_id = :tenant_id')
                ->andWhere('serv.code = :service_code')
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->setParameter('service_code', $code, ParameterType::STRING)
                ->executeQuery()
                ->fetchAssociative();

            if (!$result) {
                return new EntityNotFound();
            }

            return (object) $result;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o serviço, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    /**
     * Busca todos os serviços completos por ID do orçamento.
     *
     * @param int $budget_id ID do orçamento
     * @param int $tenant_id ID do tenant
     * @return array<int, array<string, mixed>> Array com serviços completos
     */
    public function getAllServiceFullByIdBudget(int $budget_id, int $tenant_id): array
    {
        try {

            $result = $this->connection->createQueryBuilder()
                ->select(
                    'serv.id AS id',
                    'serv.tenant_id',
                    'serv.code',
                    'serv.description',
                    'serv.discount',
                    'serv.total',
                    'serv.due_date',
                    'serv.created_at',
                    'serv.updated_at',
                    'serv.pdf_verification_hash',
                    'cat.id AS category_id',
                    'cat.name AS category_name',
                    'stat.id AS status_id',
                    'stat.name AS status_name',
                    'stat.color AS status_color',
                    'stat.icon AS status_icon',
                    'stat.slug AS status_slug',
                    'bud.id AS budget_id',
                    'bud.code AS budget_code',
                    'cust.id AS customer_id',
                    'CONCAT(cdat.first_name, " ", cdat.last_name) AS customer_name',
                    'cdat.cpf AS customer_cpf',
                    'cdat.cnpj AS customer_cnpj',
                )
                ->from($this->table, 'serv')
                ->join('serv', 'categories', 'cat', 'serv.category_id = cat.id')
                ->join('serv', 'service_statuses', 'stat', 'serv.service_statuses_id = stat.id')
                ->join('serv', 'budgets', 'bud', 'serv.budget_id = bud.id AND serv.tenant_id = bud.tenant_id')
                ->join('bud', 'customers', 'cust', 'bud.customer_id = cust.id AND bud.tenant_id = cust.tenant_id')
                ->join('cust', 'common_datas', 'cdat', 'cust.common_data_id = cdat.id AND cust.tenant_id = cdat.tenant_id')
                ->where('serv.tenant_id = :tenant_id')
                ->andWhere('serv.budget_id = :budget_id')
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->setParameter('budget_id', $budget_id, ParameterType::INTEGER)
                ->executeQuery()
                ->fetchAllAssociative();

            return $result;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o serviço, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    /**
     * Busca todos os serviços.
     *
     * @param int $tenant_id ID do tenant
     * @return array<int, array<string, mixed>> Array com todos os serviços
     */
    public function getAllServices(int $tenant_id): array
    {
        try {

            $result = $this->connection->createQueryBuilder()
                ->select('
                 serv.id AS id,
                 serv.description AS service_description,
                 serv.code,serv.total,
                 serv.due_date,
                    serv.pdf_verification_hash,
                 cat.id AS category_id,
                 stat.id AS status_id,
                 stat.name ,
                 stat.color ,
                 bud.id AS budget_id')
                ->from($this->table, 'serv')
                ->join('serv', 'categories', 'cat', 'serv.category_id = cat.id and serv.tenant_id = cat.tenant_id')
                ->join('serv', 'service_statuses', 'stat', 'serv.service_statuses_id = stat.id ')
                ->join('serv', 'budgets', 'bud', 'serv.budget_id = bud.id and serv.tenant_id = bud.tenant_id')
                ->where('serv.tenant_id = :tenant_id')
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->executeQuery()
                ->fetchAllAssociative();

            return $result;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar os serviços, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

    }

    /**
     * Busca todos os serviços não concluídos.
     *
     * @param int $tenant_id ID do tenant
     * @return array<int, array<string, mixed>> Array com serviços não concluídos
     */
    public function getAllServicesNotCompleted(int $tenant_id): array
    {
        try {

            $result = $this->connection->createQueryBuilder()
                ->select('
                 serv.id AS id,
                 serv.description,
                 serv.code,
                 serv.discount,
                 serv.total,
                 serv.due_date,
                    serv.pdf_verification_hash,
                 cat.id AS category_id,
                 sstat.id AS service_statuses_id,
                 sstat.name ,
                 sstat.color ,
                 sstat.icon,
                 sstat.slug,
                 bud.id AS budget_id')
                ->from($this->table, 'serv')
                ->join('serv', 'categories', 'cat', 'serv.category_id = cat.id and serv.tenant_id = cat.tenant_id')
                ->join('serv', 'service_statuses', 'sstat', 'serv.service_statuses_id = sstat.id ')
                ->join('serv', 'budgets', 'bud', 'serv.budget_id = bud.id and serv.tenant_id = bud.tenant_id')
                ->where('sstat.slug != :slug')
                ->where('serv.tenant_id = :tenant_id')
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->setParameter('slug', 'COMPLETED', ParameterType::STRING)
                ->executeQuery()
                ->fetchAllAssociative();

            return $result;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar os serviços, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

    }

    /**
     * Busca serviços por filtros para relatório.
     *
     * @param array<string, mixed> $data Dados dos filtros
     * @param int $tenant_id ID do tenant
     * @return array<int, array<string, mixed>> Array com serviços filtrados
     */
    public function getServicesByFilterReport(array $data, int $tenant_id): array
    {
        try {
            // Validação dos dados de entrada
            $data = $this->validateFilterData($data);

            // Inicializa o query builder
            $queryBuilder = $this->connection->createQueryBuilder();

            // Seleciona campos específicos para performance
            $queryBuilder
                ->select(
                    's.id',
                    's.code',
                    's.due_date',
                    's.pdf_verification_hash',
                    'CAST(s.discount AS DECIMAL(10,2)) as discount',
                    'CAST(s.total AS DECIMAL(10,2)) as total',
                    's.description',
                    's.created_at',
                    'ss.name as status_name',
                    'ss.color as status_color',
                    'ss.icon as status_icon',
                    'ss.slug as status_slug',
                    'c.name as category_name',
                    'b.code as budget_code',
                    'cdat.first_name',
                    'cdat.last_name',
                    'cdat.cpf',
                    'cdat.cnpj',
                )
                ->from($this->table, 's')
                ->join('s', 'service_statuses', 'ss', 's.service_statuses_id = ss.id')
                ->join('s', 'categories', 'c', 's.category_id = c.id')
                ->join('s', 'budgets', 'b', 's.budget_id = b.id')
                ->join('b', 'customers', 'cust', 'b.customer_id = cust.id and cust.tenant_id = s.tenant_id')
                ->join('cust', 'common_datas', 'cdat', 'cust.common_data_id = cdat.id and cust.tenant_id = s.tenant_id')
                ->where('s.tenant_id = :tenant_id')
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER);

            // Aplica os filtros de forma segura
            $this->applyFilters($queryBuilder, $data);

            // Ordenação e limite
            $queryBuilder
                ->orderBy('s.due_date', 'ASC')
                ->setMaxResults(100);

            // Execute a query e retorne os resultados
            $result = $queryBuilder->executeQuery()->fetchAllAssociative();

            // Formata os resultados
            return $this->formatResults($result);

        } catch (Exception $e) {
            // Log do erro
            error_log("Erro ao buscar serviços: " . $e->getMessage());

            throw new RuntimeException(
                "Falha ao buscar os serviços. Por favor, tente novamente mais tarde.",
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
            'code' => isset($data[ 'code' ]) ? trim($data[ 'code' ]) : null,
            'start_date' => convertToDateTime($data[ 'start_date' ] ?? null),
            'end_date' => convertToDateTime($data[ 'end_date' ] ?? null),
            'customer_name' => isset($data[ 'customer_name' ]) ? trim($data[ 'customer_name' ]) : null,
            'total' => convertMoneyToFloat($data[ 'total' ] ?? null),
            'status' => isset($data[ 'status' ]) ? trim($data[ 'status' ]) : null,
            'category' => isset($data[ 'category' ]) ? trim($data[ 'category' ]) : null,
            'budget_code' => isset($data[ 'budget_code' ]) ? trim($data[ 'budget_code' ]) : null,
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
        // Filtro por código
        if (!empty($data[ 'code' ])) {
            $queryBuilder
                ->andWhere('s.code LIKE :code')
                ->setParameter('code', '%' . $data[ 'code' ] . '%');
        }

        // Filtro por período
        if (!empty($data[ 'start_date' ]) && !empty($data[ 'end_date' ])) {
            $queryBuilder
                ->andWhere('s.created_at BETWEEN :start_date AND :end_date')
                ->setParameter('start_date', $data[ 'start_date' ], ParameterType::STRING)
                ->setParameter('end_date', $data[ 'end_date' ], ParameterType::STRING);
        }

        // Filtro por cliente (nome, CPF ou CNPJ)
        if (!empty($data[ 'customer_name' ])) {
            $searchTerm = '%' . $data[ 'customer_name' ] . '%';

            $queryBuilder->andWhere('(
            cdat.first_name LIKE :search_term
            OR            cdat.last_name LIKE :search_term
            OR            cdat.cpf LIKE :search_term
            OR            cdat.cnpj LIKE :search_term
            OR            CONCAT(cdat.first_name, " ", cdat.last_name) LIKE :search_term
        )')->setParameter('search_term', $searchTerm);
        }

        // Filtro por valor total
        if (!empty($data[ 'total' ])) {
            $queryBuilder
                ->andWhere('s.total >= :total')
                ->setParameter('total', (float) $data[ 'total' ], ParameterType::STRING);
        }

        // Filtro por status
        if (!empty($data[ 'status' ])) {
            $queryBuilder
                ->andWhere('ss.slug = :status')
                ->setParameter('status', $data[ 'status' ]);
        }

        // Filtro por categoria
        if (!empty($data[ 'category' ])) {
            $queryBuilder
                ->andWhere('c.id = :category_id')
                ->setParameter('category_id', $data[ 'category' ], ParameterType::INTEGER);
        }

        // Filtro por código do orçamento
        if (!empty($data[ 'budget_code' ])) {
            $queryBuilder
                ->andWhere('b.code LIKE :budget_code')
                ->setParameter('budget_code', '%' . $data[ 'budget_code' ] . '%');
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
                'code' => $row[ 'code' ],
                'status_name' => $row[ 'status_name' ],
                'status_color' => $row[ 'status_color' ],
                'status_icon' => $row[ 'status_icon' ],
                'status_slug' => $row[ 'status_slug' ],
                'due_date' => $row[ 'due_date' ],
                'discount' => number_format((float) $row[ 'discount' ], 2, '.', ''),
                'total' => number_format((float) $row[ 'total' ], 2, '.', ''),
                'customer_name' => $row[ 'first_name' ] . ' ' . $row[ 'last_name' ],
                'customer_cpf' => $row[ 'cpf' ] ?? '',
                'customer_cnpj' => $row[ 'cnpj' ] ?? '',
                'description' => $row[ 'description' ],
                'category_name' => $row[ 'category_name' ],
                'budget_code' => $row[ 'budget_code' ],
                'created_at' => $row[ 'created_at' ],
            ];
        }, $results);
    }

}
