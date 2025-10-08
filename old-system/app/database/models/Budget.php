<?php

namespace app\database\models;

use app\database\entities\BudgetEntity;
use app\database\Model;
use core\dbal\Entity;
use core\functions\DateUtils;
use Doctrine\DBAL\ParameterType;
use Exception;
use RuntimeException;

class Budget extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'budgets';

    protected static function createEntity( array $data ): Entity
    {
        return BudgetEntity::create( $data );
    }

    public function getBudgetByEmail( string $email, int $tenant_id ): BudgetEntity|Entity
    {
        try {
            $entity = $this->findBy( [ 'email' => $email, 'tenant_id' => $tenant_id ] );

            return $entity;
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar email, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    public function getBudgetById( int $id, int $tenant_id ): BudgetEntity|Entity
    {
        try {
            $entity = $this->findBy( [ 'id' => $id, 'tenant_id' => $tenant_id ] );

            return $entity;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar o orcamento, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    public function getBudgetFullById( int $id, int $tenant_id )
    {
        try {
            $result = $this->connection->createQueryBuilder()
                ->select(
                    'b.id',
                    'b.tenant_id',
                    'b.user_confirmation_token_id',
                    'b.code',
                    'b.due_date',
                    'CAST(b.discount AS DECIMAL(10,2)) as discount',
                    'CAST(b.total AS DECIMAL(10,2)) as total',
                    'b.description',
                    'b.payment_terms',
                    'b.attachment',
                    'b.history',
                    'b.created_at',
                    'b.pdf_verification_hash',
                    'bs.name as status_name',
                    'bs.color as status_color',
                    'bs.icon as status_icon',
                    'bs.slug as status_slug',
                    'bs.id as budget_statuses_id',
                    'CONCAT(cdat.first_name, " ", cdat.last_name) as customer_name',
                    'cust.id as customer_id',
                )
                ->from( $this->table, 'b' )
                ->join( 'b', 'budget_statuses', 'bs', 'b.budget_statuses_id = bs.id' )
                ->join( 'b', 'customers', 'cust', 'b.customer_id = cust.id and cust.tenant_id = b.tenant_id' )
                ->join( 'cust', 'common_datas', 'cdat', 'cust.common_data_id = cdat.id and cust.tenant_id = b.tenant_id' )
                ->where( 'b.id = :id' )
                ->andWhere( 'b.tenant_id = :tenant_id' )
                ->setParameter( 'tenant_id', $tenant_id, ParameterType::INTEGER )
                ->setParameter( 'id', $id, ParameterType::STRING )
                ->executeQuery()
                ->fetchAssociative();

            if ( !$result ) {
                return new \core\dbal\EntityNotFound();
            }

            return (object) $result;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar o orcamento, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    public function getBudgetFullByCode( string $code, int $tenant_id )
    {
        try {
            $result = $this->connection->createQueryBuilder()
                ->select(
                    'b.id',
                    'b.tenant_id',
                    'b.user_confirmation_token_id',
                    'b.code',
                    'b.due_date',
                    'CAST(b.discount AS DECIMAL(10,2)) as discount',
                    'CAST(b.total AS DECIMAL(10,2)) as total',
                    'b.description',
                    'b.payment_terms',
                    'b.attachment',
                    'b.history',
                    'b.created_at',
                    'b.pdf_verification_hash',
                    'bs.name as status_name',
                    'bs.color as status_color',
                    'bs.icon as status_icon',
                    'bs.slug as status_slug',
                    'bs.id as status_id',
                    'bs.description as status_description',
                    'CONCAT(cdat.first_name, " ", cdat.last_name) as customer_name',
                    'cust.id as customer_id',
                )
                ->from( $this->table, 'b' )
                ->join( 'b', 'budget_statuses', 'bs', 'b.budget_statuses_id = bs.id' )
                ->join( 'b', 'customers', 'cust', 'b.customer_id = cust.id and cust.tenant_id = b.tenant_id' )
                ->join( 'cust', 'common_datas', 'cdat', 'cust.common_data_id = cdat.id and cust.tenant_id = b.tenant_id' )
                ->where( 'b.code = :code' )
                ->andWhere( 'b.tenant_id = :tenant_id' )
                ->setParameter( 'tenant_id', $tenant_id, ParameterType::INTEGER )
                ->setParameter( 'code', $code, ParameterType::STRING )
                ->executeQuery()
                ->fetchAssociative();

            if ( !$result ) {
                return new \core\dbal\EntityNotFound();
            }

            return (object) $result;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar o orcamento, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    public function getBudgetByIdWhithCustomerDatas( string $code, int $tenant_id )
    {

        try {
            $result = $this->connection->createQueryBuilder()
                ->select(
                    'b.id',
                    'b.tenant_id',
                    'b.user_confirmation_token_id',
                    'b.code',
                    'b.due_date',
                    'CAST(b.discount AS DECIMAL(10,2)) as discount',
                    'CAST(b.total AS DECIMAL(10,2)) as total',
                    'b.description',
                    'b.payment_terms',
                    'b.attachment',
                    'b.history',
                    'b.created_at',
                    'b.pdf_verification_hash',
                    'b.budget_statuses_id',
                    'bs.name as status_name',
                    'bs.color as status_color',
                    'bs.icon as status_icon',
                    'bs.slug as status_slug',
                    'bs.description as status_description',
                    'CONCAT(cdat.first_name, " ", cdat.last_name) as customer_name',
                    'cust.id as customer_id',
                    'cdat.cnpj',
                    'cdat.cpf',
                    'cont.email',
                    'cont.phone',
                )
                ->from( $this->table, 'b' )
                ->join( 'b', 'budget_statuses', 'bs', 'b.budget_statuses_id = bs.id' )
                ->join( 'b', 'customers', 'cust', 'b.customer_id = cust.id and cust.tenant_id = b.tenant_id' )
                ->join( 'cust', 'common_datas', 'cdat', 'cust.common_data_id = cdat.id and cust.tenant_id = b.tenant_id' )
                ->join( 'cust', 'contacts', 'cont', 'cust.contact_id = cont.id and cust.tenant_id = b.tenant_id' )
                ->where( 'b.code = :code' )
                ->andWhere( 'b.tenant_id = :tenant_id' )
                ->setParameter( 'tenant_id', $tenant_id, ParameterType::INTEGER )
                ->setParameter( 'code', $code, ParameterType::STRING )
                ->executeQuery()
                ->fetchAssociative();

            if ( !$result ) {
                return new \core\dbal\EntityNotFound();
            }

            return (object) $result;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar as funções do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }

    }

    public function getBudgetByCode( string $code, int $tenant_id ): BudgetEntity|Entity
    {
        try {
            $entity = $this->findBy( [ 'code' => $code, 'tenant_id' => $tenant_id ] );

            return $entity;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar o orcamento, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    public function getAllBudgetsNotCompleted( int $tenant_id ): array|BudgetEntity|Entity
    {

        try {
            $entityCustomers = $this->connection->createQueryBuilder()
                ->select(
                    'b.id',
                    'b.tenant_id',
                    'b.user_confirmation_token_id',
                    'b.code',
                    'bs.name as status_name',
                    'bs.color as status_color',
                    'bs.icon as status_icon',
                    'bs.slug as status_slug',
                    'bs.id as status_id',
                    'b.due_date',
                    'CAST(b.discount AS DECIMAL(10,2)) as discount',
                    'CAST(b.total AS DECIMAL(10,2)) as total',
                    'cdat.first_name',
                    'cdat.last_name',
                    'b.description',
                    'b.payment_terms',
                    'b.attachment',
                    'b.history',
                    'b.created_at',
                    'b.pdf_verification_hash',
                )
                ->from( $this->table, 'b' )
                ->join( 'b', 'budget_statuses', 'bs', 'b.budget_statuses_id = bs.id' )
                ->join( 'b', 'customers', 'cust', 'b.customer_id = cust.id and cust.tenant_id = b.tenant_id' )
                ->join( 'cust', 'common_datas', 'cdat', 'cust.common_data_id = cdat.id and cust.tenant_id = b.tenant_id' )
                ->where( 'bs.slug != :slug' )
                ->andWhere( 'b.tenant_id = :tenant_id' )
                ->setParameter( 'tenant_id', $tenant_id, ParameterType::INTEGER )
                ->setParameter( 'slug', 'COMPLETED', ParameterType::STRING )
                ->executeQuery()
                ->fetchAllAssociative();

            return $entityCustomers;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar as funções do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }

    }

    public function getRecentBudgets( int $tenant_id, int $weeks = 1 ): array|BudgetEntity|Entity
    {
        try {
            $startDate = DateUtils::getWeeksAgo( $weeks );
            $endDate   = DateUtils::now()->format( 'Y-m-d H:i:s' );

            $entityCustomers = $this->connection->createQueryBuilder()
                ->select(
                    'b.id',
                    'b.tenant_id',
                    'b.user_confirmation_token_id',
                    'b.code',
                    'b.due_date',
                    'CAST(b.discount AS DECIMAL(10,2)) as discount',
                    'CAST(b.total AS DECIMAL(10,2)) as total',
                    'cdat.first_name',
                    'cdat.last_name',
                    'b.description',
                    'b.updated_at',
                    'b.pdf_verification_hash',
                    'GROUP_CONCAT(serv.description) as service_descriptions',
                    'COUNT(serv.id) as service_count',
                    'SUM(CAST(serv.total AS DECIMAL(10,2))) as service_total',
                    'bs.name',
                    'bs.color',
                    'bs.icon',
                    'bs.slug',
                )
                ->from( $this->table, 'b' )
                ->join( 'b', 'budget_statuses', 'bs', 'b.budget_statuses_id = bs.id' )
                ->join( 'b', 'customers', 'cust', 'b.customer_id = cust.id and cust.tenant_id = b.tenant_id' )
                ->join( 'cust', 'common_datas', 'cdat', 'cust.common_data_id = cdat.id and cust.tenant_id = b.tenant_id' )
                ->leftJoin( 'b', 'services', 'serv', 'serv.budget_id = b.id  and  serv.tenant_id = b.tenant_id' )
                ->where( 'b.updated_at >= :start_date' )
                ->andWhere( 'b.updated_at <= :end_date' )
                ->andWhere( 'b.tenant_id = :tenant_id' )
                ->setParameter( 'tenant_id', $tenant_id, ParameterType::INTEGER )
                ->setParameter( 'start_date', $startDate, ParameterType::STRING )
                ->setParameter( 'end_date', $endDate, ParameterType::STRING )
                ->groupBy(
                    'b.id',
                )
                ->orderBy( 'b.updated_at', 'DESC' )
                ->executeQuery()
                ->fetchAllAssociative();

            return $entityCustomers;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar os orcamentos, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }

    }

    public function getAllBudgets( int $tenant_id ): array|BudgetEntity|Entity
    {

        try {
            $entityCustomers = $this->connection->createQueryBuilder()
                ->select( "*" )
                ->from( $this->table )
                ->andWhere( 'tenant_id = :tenant_id' )
                ->setParameter( 'tenant_id', $tenant_id, ParameterType::INTEGER )
                ->setMaxResults( 100 )
                ->executeQuery()
                ->fetchAllAssociative();

            return $entityCustomers;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar as funções do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }

    }

    public function getLastCode( int $tenant_id )
    {
        try {
            $result = $this->connection->createQueryBuilder()
                ->select( 'MAX(code) as last_code' )
                ->from( $this->table )
                ->where( 'tenant_id = :tenant_id' )
                ->setParameter( 'tenant_id', $tenant_id, ParameterType::INTEGER )
                ->executeQuery()
                ->fetchOne();

            return $result;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar as funções do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }

    }

    public function getBudgetsByFilterReport( array $data, int $tenant_id ): array
    {
        try {
            // Validação dos dados de entrada
            $validatedFilters = $this->validateFilterData( $data );

            // Inicializa o query builder
            $queryBuilder = $this->connection->createQueryBuilder();

            // Seleciona campos específicos para performance

            $queryBuilder
                ->select(
                    'b.id',
                    'b.tenant_id',
                    'b.user_confirmation_token_id',
                    'b.code',
                    'b.due_date',
                    'CAST(b.discount AS DECIMAL(10,2)) as discount',
                    'CAST(b.total AS DECIMAL(10,2)) as total',
                    'cdat.first_name',
                    'cdat.last_name',
                    'b.description',
                    'b.payment_terms',
                    'b.attachment',
                    'b.history',
                    'b.created_at',
                    'b.pdf_verification_hash',
                    'bs.name',
                    'bs.color',
                    'bs.icon',
                    'bs.slug',
                )
                ->from( $this->table, 'b' )
                ->join( 'b', 'budget_statuses', 'bs', 'b.budget_statuses_id = bs.id' )
                ->join( 'b', 'customers', 'cust', 'b.customer_id = cust.id and cust.tenant_id = b.tenant_id' )
                ->join( 'cust', 'common_datas', 'cdat', 'cust.common_data_id = cdat.id and cust.tenant_id = b.tenant_id' )
                ->where( 'b.tenant_id = :tenant_id' )
                ->setParameter( 'tenant_id', $tenant_id, ParameterType::INTEGER );

            // Aplica os filtros de forma segura
            $this->applyFilters( $queryBuilder, $validatedFilters );

            // Ordenação e limite
            $queryBuilder
                ->orderBy( 'b.due_date', 'DESC' )
                ->setMaxResults( 100 );

            // Execute a query e retorne os resultados
            $result = $queryBuilder->executeQuery()->fetchAllAssociative();

            // Formata os resultados
            return $this->formatResults( $result );

        } catch ( Exception $e ) {
            // Log do erro
            error_log( "Erro ao buscar orçamentos: " . $e->getMessage() );

            throw new RuntimeException(
                "Falha ao buscar os orçamentos. Por favor, tente novamente mais tarde.",
                0,
                $e,
            );
        }
    }

    /**
     * Valida e sanitiza os dados do filtro
     */
    private function validateFilterData( array $data ): array
    {
        return [ 
            'code'          => isset( $data[ 'code' ] ) ? trim( $data[ 'code' ] ) : null,
            'start_date'    => convertToDateTimeString( $data[ 'start_date' ] ?? null ),
            'end_date'      => convertToDateTimeString( $data[ 'end_date' ] ?? null ),
            'customer_name' => isset( $data[ 'customer_name' ] ) ? trim( $data[ 'customer_name' ] ) : null,
            'cpf'           => isset( $data[ 'customer_name' ] ) ? trim( $data[ 'customer_name' ] ) : null,
            'cnpj'          => isset( $data[ 'customer_name' ] ) ? trim( $data[ 'customer_name' ] ) : null,
            'total'         => convertMoneyToFloat( $data[ 'total' ] ?? null ),
            'status'        => isset( $data[ 'status' ] ) ? trim( $data[ 'status' ] ) : null,
        ];
    }

    /**
     * Aplica os filtros na query de forma segura
     */
    private function applyFilters( $queryBuilder, array $data ): void
    {
        // Filtro por código
        if ( !empty( $data[ 'code' ] ) ) {
            $queryBuilder
                ->andWhere( 'b.code LIKE :code' )
                ->setParameter( 'code', '%' . $data[ 'code' ] . '%' );
        }

        // Filtro por período
        if ( !empty( $data[ 'start_date' ] ) && !empty( $data[ 'end_date' ] ) ) {
            $queryBuilder
                ->andWhere( 'b.created_at BETWEEN :start_date AND :end_date' )
                ->setParameter( 'start_date', $data[ 'start_date' ], ParameterType::STRING )
                ->setParameter( 'end_date', $data[ 'end_date' ], ParameterType::STRING );
        }

        // Filtro por cliente (nome, CPF ou CNPJ)
        if ( !empty( $data[ 'customer_name' ] ) ) {
            $searchTerm = '%' . $data[ 'customer_name' ] . '%';

            $queryBuilder->andWhere( '(
        cdat.first_name LIKE :search_term OR
        cdat.last_name LIKE :search_term OR
        cdat.cpf LIKE :search_term OR
        cdat.cnpj LIKE :search_term OR
        CONCAT(cdat.first_name, " ", cdat.last_name) LIKE :search_term
    )' )->setParameter( 'search_term', $searchTerm );
        }

        // Filtro por valor total
        if ( !empty( $data[ 'total' ] ) ) {
            $queryBuilder
                ->andWhere( 'b.total >= :total' )
                ->setParameter( 'total', (float) $data[ 'total' ], ParameterType::STRING );
        }

        // Filtro por status
        if ( !empty( $data[ 'status' ] ) ) {
            $queryBuilder
                ->andWhere( 'bs.slug = :status' )
                ->setParameter( 'status', $data[ 'status' ] );
        }
    }

    /**
     * Formata os resultados para exibição
     */
    private function formatResults( array $results ): array
    {
        return array_map( function ($row) {
            return [ 
                'id'            => (int) $row[ 'id' ],
                'code'          => $row[ 'code' ],
                'name'          => $row[ 'name' ],
                'color'         => $row[ 'color' ],
                'icon'          => $row[ 'icon' ],
                'slug'          => $row[ 'slug' ],
                'due_date'      => $row[ 'due_date' ],
                'total'         => number_format( (float) $row[ 'total' ], 2, '.', '' ),
                'customer_name' => $row[ 'first_name' ] . ' ' . $row[ 'last_name' ],
                'description'   => $row[ 'description' ],
                'payment_terms' => $row[ 'payment_terms' ],
                'attachment'    => $row[ 'attachment' ],
                'history'       => $row[ 'history' ],
                'created_at'    => $row[ 'created_at' ],
            ];
        }, $results );
    }

    /**
     * Verifica relacionamentos com outras tabelas, permitindo ignorar tabelas específicas
     *
     * @param int $tableId ID do registro na tabela
     * @param int $tenantId ID do tenant
     * @param array $ignoreTables Tabelas que devem ser ignoradas na verificação
     * @return array Informações sobre os relacionamentos encontrados
     */
    public function checkRelationships( $tableId, $tenantId, array $ignoreTables = [] )
    {
        try {
            $result             = false;
            $tables             = '';
            $countRelationships = 0;
            $records            = 0;
            $constraint         = [];

            // Consulta para verificar relacionamentos no INFORMATION_SCHEMA
            $queryBuilder = $this->connection->createQueryBuilder()
                ->select( 'TABLE_NAME', 'COLUMN_NAME', 'CONSTRAINT_NAME' )
                ->from( 'INFORMATION_SCHEMA.KEY_COLUMN_USAGE' )
                ->where( 'REFERENCED_TABLE_NAME = :table' )
                ->andWhere( 'REFERENCED_TABLE_SCHEMA = :schema' )
                ->setParameters( [ 
                    'table'  => $this->table,
                    'schema' => env( 'DB_NAME' ),
                ] );

            // Adiciona condição para ignorar tabelas específicas
            if ( !empty( $ignoreTables ) ) {
                $placeholders = [];
                foreach ( $ignoreTables as $i => $table ) {
                    $paramName      = 'ignoreTable' . $i;
                    $placeholders[] = ':' . $paramName;
                    $queryBuilder->setParameter( $paramName, $table );
                }

                $queryBuilder->andWhere( 'TABLE_NAME NOT IN (' . implode( ', ', $placeholders ) . ')' );
            }

            $schemaQuery = $queryBuilder->executeQuery()->fetchAllAssociative();
            // Verifica cada tabela relacionada
            foreach ( $schemaQuery as $relation ) {
                $tableName  = $relation[ 'TABLE_NAME' ];
                $columnName = $relation[ 'COLUMN_NAME' ];

                // Consulta para contar registros relacionados
                $count = $this->connection->createQueryBuilder()
                    ->select( 'COUNT(*) as count' )
                    ->from( $tableName )
                    ->where( "$columnName = :id" )
                    ->andWhere( 'tenant_id = :tenant_id' )
                    ->setParameters( [ 
                        'id'        => $tableId,
                        'tenant_id' => $tenantId,
                    ] )
                    ->executeQuery()
                    ->fetchOne();

                if ( $count > 0 ) {
                    // Obtém informações adicionais sobre os registros relacionados
                    $relatedRecords = $this->connection->createQueryBuilder()
                        ->select( '*' )
                        ->from( $tableName )
                        ->where( "$columnName = :id" )
                        ->andWhere( 'tenant_id = :tenant_id' )
                        ->setParameters( [ 
                            'id'        => $tableId,
                            'tenant_id' => $tenantId,
                        ] )
                        ->executeQuery()
                        ->fetchAllAssociative();

                    // Retorna informações detalhadas sobre o relacionamento
                    $tables             = $this->getTableAlias( $tableName );
                    $countRelationships = $count;
                    $records            = $relatedRecords;
                    $constraint         = $relation[ 'CONSTRAINT_NAME' ];
                    $result             = true;
                }
            }

            return [ 
                'status'  => $result === true ? 'success' : 'error',
                'message' => $result === true ? 'Relacionamentos encontrados.' : 'Nenhum relacionamento encontrado.',
                'data'    => [ 
                    'hasRelationships'   => $result,
                    'tables'             => $tables,
                    'countRelationships' => $countRelationships,
                    'records'            => $records,
                    'constraint'         => $constraint,
                ],
            ];

        } catch ( Exception $e ) {
            error_log( "Erro ao verificar relacionamentos: " . $e->getMessage() );

            return [ 
                'status'  => 'error',
                'message' => 'Erro ao verificar relacionamentos',
                'data'    => $e->getMessage(),
            ];
        }
    }

    /**
     * Retorna um nome amigável para a tabela
     */
    private function getTableAlias( $tableName ): string
    {
        $aliases = [ 
            'services' => 'serviços',
            // Adicione mais aliases conforme necessário
        ];

        return $aliases[ $tableName ] ?? $tableName;
    }

}
