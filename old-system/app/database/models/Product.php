<?php

namespace app\database\models;

use app\database\entities\ProductEntity;
use app\database\Model;
use core\dbal\Entity;
use Doctrine\DBAL\ParameterType;
use Exception;
use RuntimeException;

class Product extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'products';

    protected static function createEntity(array $data): Entity
    {
        return ProductEntity::create($data);
    }

    public function getProductById(int $id, int $tenant_id): ProductEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'id' => $id, 'tenant_id' => $tenant_id ]);

            return $entity;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o produto , tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getProductByCode(string $code, int $tenant_id): ProductEntity|Entity
    {
        try {
            $entity = $this->findBy([ 'code' => $code, 'tenant_id' => $tenant_id ]);

            return $entity;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o produto , tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getLastCode(int $tenant_id): ProductEntity|Entity
    {

        try {
            $entity = $this->findBy(criteria: [ 'tenant_id' => $tenant_id ], fields: [ 'id', 'tenant_id', 'code' ], orderBy: [ 'code' => 'DESC' ], limit: 1);

            return $entity;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar as funções do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

    }

    public function getAllProducts(int $tenant_id): array
    {

        try {
            $entities = $this->findBy([ 'tenant_id' => $tenant_id ]);

            return $entities;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar os produtos, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

    }

    public function getAllProductsActive(int $tenant_id): array
    {

        try {
            $entities = $this->findBy([ 'tenant_id' => $tenant_id, 'active' => true ]);

            return $entities;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar os produtos, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }

    }

    public function getProductsWhithInventoryByCode(string $code, int $tenant_id): array
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder()
                ->select(
                    'p.id',
                    'p.code',
                    'p.name',
                    'p.description',
                    'CAST(p.price AS DECIMAL(10,2)) as price',
                    'pi.quantity as stock_quantity',
                    'p.active',
                    'p.image',
                    'p.created_at',
                    'p.updated_at',
                )
                ->from($this->table, 'p')
                ->leftJoin('p', 'product_inventory', 'pi', 'p.id = pi.product_id and pi.tenant_id = :tenant_id')
                ->where('p.tenant_id = :tenant_id')
                ->where('p.code = :code')
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->setParameter('code', $code, ParameterType::STRING)
                ->executeQuery()
                ->fetchAssociative();

            // Executa a consulta e retorna os resultados
            return $queryBuilder;

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar os produtos, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getProductsByFilterReport(array $data, int $tenant_id): array
    {
        try {
            // Validação dos dados de entrada
            $data = $this->validateFilterData($data);

            // Inicializa o query builder
            $queryBuilder = $this->connection->createQueryBuilder();

            // Seleciona campos específicos para performance
            $queryBuilder
                ->select(
                    'p.id',
                    'p.code',
                    'p.name',
                    'p.description',
                    'CAST(p.price AS DECIMAL(10,2)) as price',
                    'pi.quantity as stock_quantity',
                    'p.active',
                    'p.image',
                    'p.created_at',
                    'p.updated_at',
                )
                ->from($this->table, 'p')
                ->leftJoin('p', 'product_inventory', 'pi', 'p.id = pi.product_id and pi.tenant_id = :tenant_id')
                ->where('p.tenant_id = :tenant_id')
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER);

            // Aplica os filtros de forma segura
            $this->applyFilters($queryBuilder, $data);

            // Ordenação e limite
            $queryBuilder
                ->orderBy('p.updated_at', 'DESC')
                ->setMaxResults(100);

            // Execute a query e retorne os resultados
            $result = $queryBuilder->executeQuery()->fetchAllAssociative();

            // Formata os resultados
            return $this->formatResults($result);

        } catch (Exception $e) {
            // Log do erro
            error_log("Erro ao buscar produtos: " . $e->getMessage());

            throw new RuntimeException(
                "Falha ao buscar os produtos. Por favor, tente novamente mais tarde.",
                0,
                $e,
            );
        }
    }

    /**
     * Valida e sanitiza os dados do filtro
     */
    private function validateFilterData(array $data): array
    {
        return [
            'code' => isset($data[ 'code' ]) ? trim($data[ 'code' ]) : null,
            'name' => isset($data[ 'name' ]) ? trim($data[ 'name' ]) : null,
            'min_price' => convertMoneyToFloat($data[ 'min_price' ] ?? null),
            'status' => isset($data[ 'status' ]) ? trim($data[ 'status' ]) : null,
        ];
    }

    /**
     * Aplica os filtros na query de forma segura
     */
    private function applyFilters($queryBuilder, array $data): void
    {
        // Filtro por código
        if (!empty($data[ 'code' ])) {
            $queryBuilder
                ->andWhere('p.code LIKE :code')
                ->setParameter('code', '%' . $data[ 'code' ] . '%');
        }

        // Filtro por nome
        if (!empty($data[ 'name' ])) {
            $queryBuilder
                ->andWhere('p.name LIKE :name')
                ->setParameter('name', '%' . $data[ 'name' ] . '%');
        }

        // Filtro por preço mínimo
        if (!empty($data[ 'min_price' ])) {
            $queryBuilder
                ->andWhere('p.price >= :min_price')
                ->setParameter('min_price', (float) $data[ 'min_price' ], ParameterType::STRING);
        }

        // Filtro por status (ativo/inativo)
        if (isset($data[ 'status' ]) && $data[ 'status' ] !== '') {
            $queryBuilder
                ->andWhere('p.active = :status')
                ->setParameter('status', $data[ 'status' ] === '1', ParameterType::BOOLEAN);
        }

    }

    /**
     * Formata os resultados para exibição
     */
    private function formatResults(array $results): array
    {
        return array_map(function ($row) {
            return [
                'id' => (int) $row[ 'id' ],
                'code' => $row[ 'code' ],
                'name' => $row[ 'name' ],
                'description' => $row[ 'description' ],
                'price' => number_format((float) $row[ 'price' ], 2, '.', ''),
                'stock_quantity' => (int) $row[ 'stock_quantity' ],
                'active' => (bool) $row[ 'active' ],
                'image' => $row[ 'image' ],
                'created_at' => $row[ 'created_at' ],
                'updated_at' => $row[ 'updated_at' ],
            ];
        }, $results);
    }

    /**
     * Verifica relacionamentos com outras tabelas, permitindo ignorar tabelas específicas
     *
     * @param int $tableId ID do registro na tabela
     * @param int $tenantId ID do tenant
     * @param array $ignoreTables Tabelas que devem ser ignoradas na verificação
     * @return array Informações sobre os relacionamentos encontrados
     */
    public function checkRelationships($tableId, $tenantId, array $ignoreTables = [])
    {
        try {
            // Consulta para verificar relacionamentos no INFORMATION_SCHEMA
            $queryBuilder = $this->connection->createQueryBuilder()
                ->select('TABLE_NAME', 'COLUMN_NAME', 'CONSTRAINT_NAME')
                ->from('INFORMATION_SCHEMA.KEY_COLUMN_USAGE')
                ->where('REFERENCED_TABLE_NAME = :table')
                ->andWhere('REFERENCED_TABLE_SCHEMA = :schema')
                ->setParameters([
                    'table' => $this->table,
                    'schema' => env('DB_NAME'),
                ]);

            // Adiciona condição para ignorar tabelas específicas
            if (!empty($ignoreTables)) {
                $placeholders = [];
                foreach ($ignoreTables as $i => $table) {
                    $paramName = 'ignoreTable' . $i;
                    $placeholders[] = ':' . $paramName;
                    $queryBuilder->setParameter($paramName, $table);
                }

                $queryBuilder->andWhere('TABLE_NAME NOT IN (' . implode(', ', $placeholders) . ')');
            }

            $schemaQuery = $queryBuilder->executeQuery()->fetchAllAssociative();

            // Se não encontrou relacionamentos, retorna false
            if (empty($schemaQuery)) {
                return [ 'hasRelationships' => false ];
            }

            // Verifica cada tabela relacionada
            foreach ($schemaQuery as $relation) {
                $tableName = $relation[ 'TABLE_NAME' ];
                $columnName = $relation[ 'COLUMN_NAME' ];

                // Consulta para contar registros relacionados
                $count = $this->connection->createQueryBuilder()
                    ->select('COUNT(*) as count')
                    ->from($tableName)
                    ->where("$columnName = :id")
                    ->andWhere('tenant_id = :tenant_id')
                    ->setParameters([
                        'id' => $tableId,
                        'tenant_id' => $tenantId,
                    ])
                    ->executeQuery()
                    ->fetchOne();

                if ($count > 0) {
                    // Obtém informações adicionais sobre os registros relacionados
                    $relatedRecords = $this->connection->createQueryBuilder()
                        ->select('*')
                        ->from($tableName)
                        ->where("$columnName = :id")
                        ->andWhere('tenant_id = :tenant_id')
                        ->setParameters([
                            'id' => $tableId,
                            'tenant_id' => $tenantId,
                        ])
                        ->executeQuery()
                        ->fetchAllAssociative();

                    // Retorna informações detalhadas sobre o relacionamento
                    return [
                        'hasRelationships' => true,
                        'table' => $this->getTableAlias($tableName),
                        'count' => $count,
                        'records' => $relatedRecords,
                        'constraint' => $relation[ 'CONSTRAINT_NAME' ],
                    ];
                }
            }

            return [ 'hasRelationships' => false ];

        } catch (Exception $e) {
            error_log("Erro ao verificar relacionamentos: " . $e->getMessage());

            return [
                'error' => true,
                'message' => 'Erro ao verificar relacionamentos',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retorna um nome amigável para a tabela
     */
    private function getTableAlias($tableName): string
    {
        $aliases = [
            'service_items' => 'itens de serviços',
            // Adicione mais aliases conforme necessário
        ];

        return $aliases[ $tableName ] ?? $tableName;
    }

}
