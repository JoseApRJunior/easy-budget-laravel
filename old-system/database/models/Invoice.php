<?php

namespace app\database\models;

use app\database\entitiesORM\InvoiceEntity;
use app\database\Model;
use core\dbal\Entity;
use core\dbal\EntityNotFound;
use Doctrine\DBAL\ParameterType;
use Exception;
use RuntimeException;

class Invoice extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'invoices';

    /**
     * Cria uma nova instância de InvoiceEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de InvoiceEntity.
     */
    protected static function createEntity( array $data ): Entity
    {
        return InvoiceEntity::create( $data );
    }

    public function getInvoiceFullByCode( string $code, int $tenant_id ): object
    {
        try {
            $result = $this->connection->createQueryBuilder()
                ->select(
                    'i.*',
                    'ist.name as status_name',
                    'ist.slug as status_slug',
                    'ist.color as status_color',
                    'ist.icon as status_icon',
                    'CONCAT(cd.first_name, " ", cd.last_name) as customer_name',
                    'cd.cpf',
                    'cd.cnpj',
                    'ct.email_business as customer_email_business',
                    'ct.email as customer_email',
                    'ct.phone_business as customer_phone_business',
                    'ct.phone as customer_phone',
                    's.code as service_code',
                    's.description as service_description',
                    'p.id as provider_id',
                    'p.user_id as user_id',
                    'pcd.company_name as provider_company_name',
                    'CONCAT(pcd.first_name, " ", pcd.last_name) as provider_name',
                    'pcd.cnpj as provider_cnpj',
                    'pcd.cpf as provider_cpf',
                    'pct.email_business as provider_email_business',
                    'pct.email as provider_email',
                    'pct.phone_business as provider_phone_business',
                    'pct.phone as provider_phone',
                )
                ->from( $this->table, 'i' )
                ->join( 'i', 'invoice_statuses', 'ist', 'i.invoice_statuses_id = ist.id' )
                ->leftJoin( 'i', 'customers', 'c', 'i.customer_id = c.id' )
                ->leftJoin( 'c', 'common_datas', 'cd', 'c.common_data_id = cd.id' )
                ->leftJoin( 'c', 'contacts', 'ct', 'c.contact_id = ct.id' )
                ->leftJoin( 'i', 'services', 's', 'i.service_id = s.id' )
                ->leftJoin( 'i', 'tenants', 't', 'i.tenant_id = t.id' )
                ->leftJoin( 't', 'providers', 'p', 'p.tenant_id = t.id' )
                ->leftJoin( 'p', 'common_datas', 'pcd', 'p.common_data_id = pcd.id' )
                ->leftJoin( 'p', 'contacts', 'pct', 'p.contact_id = pct.id' )
                ->where( 'i.code = :code' )
                ->andWhere( 'i.tenant_id = :tenant_id' )
                ->setParameter( 'code', $code, ParameterType::STRING )
                ->setParameter( 'tenant_id', $tenant_id, ParameterType::INTEGER )
                ->executeQuery()
                ->fetchAssociative();

            if ( !$result ) {
                return new EntityNotFound();
            }

            return (object) $result;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar detalhes da fatura.", 0, $e );
        }
    }

    /**
     * Busca uma fatura completa por hash público.
     *
     * @param string $public_hash Hash público da fatura
     * @return object|EntityNotFound Objeto com dados da fatura ou EntityNotFound
     */
    public function getInvoiceFullByHash( string $public_hash ): object
    {
        try {
            $result = $this->connection->createQueryBuilder()
                ->select(
                    'i.*',
                    'ist.name as status_name',
                    'ist.slug as status_slug',
                    'ist.color as status_color',
                    'ist.icon as status_icon',
                    'CONCAT(cd.first_name, " ", cd.last_name) as customer_name',
                    'cd.cpf',
                    'cd.cnpj',
                    'ct.email_business as customer_email_business',
                    'ct.email as customer_email',
                    'ct.phone_business as customer_phone_business',
                    'ct.phone as customer_phone',
                    's.code as service_code',
                    's.description as service_description',
                    'p.id as provider_id',
                    'p.user_id as user_id',
                    'pcd.company_name as provider_company_name',
                    'CONCAT(pcd.first_name, " ", pcd.last_name) as provider_name',
                    'pcd.cnpj as provider_cnpj',
                    'pcd.cpf as provider_cpf',
                    'pct.email_business as provider_email_business',
                    'pct.email as provider_email',
                    'pct.phone_business as provider_phone_business',
                    'pct.phone as provider_phone',
                )
                ->from( $this->table, 'i' )
                ->join( 'i', 'invoice_statuses', 'ist', 'i.invoice_statuses_id = ist.id' )
                ->leftJoin( 'i', 'customers', 'c', 'i.customer_id = c.id' )
                ->leftJoin( 'c', 'common_datas', 'cd', 'c.common_data_id = cd.id' )
                ->leftJoin( 'c', 'contacts', 'ct', 'c.contact_id = ct.id' )
                ->leftJoin( 'i', 'services', 's', 'i.service_id = s.id' )
                ->leftJoin( 'i', 'tenants', 't', 'i.tenant_id = t.id' )
                ->leftJoin( 't', 'providers', 'p', 'p.tenant_id = t.id' )
                ->leftJoin( 'p', 'common_datas', 'pcd', 'p.common_data_id = pcd.id' )
                ->leftJoin( 'p', 'contacts', 'pct', 'p.contact_id = pct.id' )
                ->where( 'i.public_hash = :public_hash' )
                ->setParameter( 'public_hash', $public_hash, ParameterType::STRING )
                ->executeQuery()
                ->fetchAssociative();

            if ( !$result ) {
                return new EntityNotFound();
            }

            return (object) $result;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar detalhes da fatura.", 0, $e );
        }
    }

    public function getLastCode( int $tenant_id ): ?string
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
            throw new RuntimeException( "Falha ao buscar o último código da fatura.", 0, $e );
        }
    }

    /**
     * Busca todas as faturas com dados do cliente.
     *
     * @param int $tenant_id ID do tenant
     * @return array<int, array<string, mixed>> Array com faturas e dados do cliente
     */
    public function getAllInvoicesWithCustomer( int $tenant_id ): array
    {
        try {
            $result = $this->connection->createQueryBuilder()
                ->select(
                    'i.id',
                    'i.code',
                    'i.status',
                    'i.total',
                    'i.due_date',
                    'i.created_at',
                    's.code as service_code',
                    'CONCAT(cd.first_name, " ", cd.last_name) as customer_name',
                )
                ->from( $this->table, 'i' )
                ->join( 'i', 'services', 's', 'i.service_id = s.id' )
                ->join( 'i', 'customers', 'c', 'i.customer_id = c.id' )
                ->join( 'c', 'common_datas', 'cd', 'c.common_data_id = cd.id' )
                ->where( 'i.tenant_id = :tenant_id' )
                ->setParameter( 'tenant_id', $tenant_id, ParameterType::INTEGER )
                ->orderBy( 'i.created_at', 'DESC' )
                ->executeQuery()
                ->fetchAllAssociative();

            return $result;
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar as faturas.", 0, $e );
        }
    }

    /**
     * Busca faturas por filtros.
     *
     * @param array<string, mixed> $filters Dados dos filtros
     * @param int $tenant_id ID do tenant
     * @return array<int, array<string, mixed>> Array com faturas filtradas
     */
    public function getInvoicesByFilter( array $filters, int $tenant_id ): array
    {
        try {
            $validatedFilters = $this->validateFilterData( $filters );

            $queryBuilder = $this->connection->createQueryBuilder();

            $queryBuilder
                ->select(
                    'i.id',
                    'i.code',
                    'i.total',
                    'i.due_date',
                    'i.created_at',
                    'ist.name as status_name',
                    'ist.slug as status_slug',
                    'ist.color as status_color',
                    'ist.icon as status_icon',
                    'CONCAT(cd.first_name, " ", cd.last_name) as customer_name',
                )
                ->from( $this->table, 'i' )
                ->join( 'i', 'invoice_statuses', 'ist', 'i.invoice_statuses_id = ist.id' )
                ->leftJoin( 'i', 'services', 's', 'i.service_id = s.id' )
                ->leftJoin( 'i', 'customers', 'c', 'i.customer_id = c.id' )
                ->join( 'c', 'common_datas', 'cd', 'c.common_data_id = cd.id' )
                ->where( 'i.tenant_id = :tenant_id' )
                ->setParameter( 'tenant_id', $tenant_id, ParameterType::INTEGER );

            $this->applyFilters( $queryBuilder, $validatedFilters );

            $queryBuilder->orderBy( 'i.created_at', 'DESC' )->setMaxResults( 100 );

            $results = $queryBuilder->executeQuery()->fetchAllAssociative();

            return $this->formatResults( $results );
        } catch ( Exception $e ) {
            error_log( "Erro ao filtrar faturas: " . $e->getMessage() );

            throw new RuntimeException( "Falha ao filtrar faturas. Por favor, tente novamente mais tarde.", 0, $e );
        }
    }

    /**
     * Valida e sanitiza os dados do filtro.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @return array<string, mixed> Dados validados
     */
    private function validateFilterData( array $data ): array
    {
        return [ 
            'code'          => isset( $data[ 'code' ] ) ? trim( $data[ 'code' ] ) : null,
            'start_date'    => convertToDateTimeString( $data[ 'start_date' ] ?? null ),
            'end_date'      => convertToDateTimeString( $data[ 'end_date' ] ?? null ),
            'customer_name' => isset( $data[ 'customer_name' ] ) ? trim( $data[ 'customer_name' ] ) : null,
            'total'         => convertMoneyToFloat( $data[ 'total' ] ?? null ),
            'status'        => isset( $data[ 'status' ] ) ? trim( $data[ 'status' ] ) : null,
        ];
    }

    /**
     * Aplica os filtros na query de forma segura.
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $queryBuilder Query builder
     * @param array<string, mixed> $data Dados dos filtros
     * @return void
     */
    private function applyFilters( $queryBuilder, array $data ): void
    {
        if ( !empty( $data[ 'code' ] ) ) {
            $queryBuilder->andWhere( 'i.code LIKE :code' )->setParameter( 'code', '%' . $data[ 'code' ] . '%' );
        }

        if ( !empty( $data[ 'start_date' ] ) && !empty( $data[ 'end_date' ] ) ) {
            $queryBuilder
                ->andWhere( 'i.created_at BETWEEN :start_date AND :end_date' )
                ->setParameter( 'start_date', $data[ 'start_date' ], ParameterType::STRING )
                ->setParameter( 'end_date', $data[ 'end_date' ], ParameterType::STRING );
        }

        if ( !empty( $data[ 'customer_name' ] ) ) {
            $searchTerm = '%' . $data[ 'customer_name' ] . '%';
            $queryBuilder->andWhere( 'CONCAT(cd.first_name, " ", cd.last_name) LIKE :search_term' )->setParameter( 'search_term', $searchTerm );
        }

        if ( !empty( $data[ 'total' ] ) ) {
            $queryBuilder->andWhere( 'i.total >= :total' )->setParameter( 'total', (float) $data[ 'total' ], ParameterType::STRING );
        }

        if ( !empty( $data[ 'status' ] ) ) {
            $queryBuilder->andWhere( 'ist.slug = :status' )->setParameter( 'status', $data[ 'status' ] );
        }
    }

    /**
     * Formata os resultados para exibição.
     *
     * @param array<int, array<string, mixed>> $results Resultados brutos
     * @return array<int, array<string, mixed>> Resultados formatados
     */
    private function formatResults( array $results ): array
    {
        return array_map( function ($row) {
            return [ 
                'code'          => $row[ 'code' ],
                'customer_name' => $row[ 'customer_name' ],
                'status_name'   => $row[ 'status_name' ],
                'status_slug'   => $row[ 'status_slug' ],
                'status_color'  => $row[ 'status_color' ],
                'status_icon'   => $row[ 'status_icon' ],
                'total'         => number_format( (float) $row[ 'total' ], 2, '.', '' ),
                'due_date'      => $row[ 'due_date' ],
                'created_at'    => $row[ 'created_at' ],
            ];

        }, $results );
    }

}
