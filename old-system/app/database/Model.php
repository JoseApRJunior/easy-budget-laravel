<?php

namespace app\database;

use core\dbal\Entity;
use core\dbal\EntityNotFound;
use core\dbal\ModelInterface;
use core\library\Session;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Exception;
use PDOException;
use ReflectionClass;
use Throwable;

/**
 * Abstract base class for database models. Provides common functionality for creating and handling database entities.
 */
abstract class Model implements ModelInterface
{
    /**
     * The name of the database table associated with this model.
     */
    protected string $table;
    protected $authenticated;

    /**
     * Constructs a new instance of the Model class, which provides common functionality for creating and handling database entities.
     *
     * @param Connection $connection The database connection to use for database operations.
     * @throws PDOException If the `$table` property is not set in the child class.
     */
    public function __construct(
        protected Connection $connection,
    ) {
        if (!isset($this->table)) {
            throw new PDOException("Property $this->table must be set in child class");
        }
        if (Session::has('auth')) {
            $this->authenticated = Session::get('auth');
        }
    }

    /**
     * Create an entity from an array of data
     * This method should be implemented in each child class
     *
     * @param array <string,mixed> $data
     * @return Entity
     */
    abstract protected static function createEntity(array $data): Entity;

    /**
     * Lida com o resultado de uma consulta ao banco de dados.
     *
     * Esta função processa o array de resultado bruto de uma consulta ao banco de dados e
     * transforma-o em um array de entidades ou em uma única entidade,
     * dependendo da estrutura do resultado.
     *
     * @param array $result O array de resultado bruto da consulta ao banco de dados.
     *
     * @return array|Entity Retorna um array de entidades ou uma única entidade.
     */
    public function handleQueryResult(array $result): array|Entity|EntityNotFound
    {
        if (count($result) === 1) {
            return $this->createEntity($result[ 0 ]);
        }

        if (count($result) === 0) {
            return new EntityNotFound();
        }

        return array_map([ static::class, 'createEntity' ], $result);
    }

    public function handleQueryResultClass(array $result, $class): array|Entity
    {
        if (count($result) === 1) {
            return $class::create($result[ 0 ]);
        }

        if (count($result) === 0) {
            return new EntityNotFound();
        }

        return array_map([ $class, 'createEntity' ], $result);
    }

    public function handleQueryResultObject(array $result): object
    {
        if (count($result) === 0) {
            return new EntityNotFound();
        }

        return (object) array_map(fn ($item) => (object) $item, $result);
    }

    /**
     * Cria um novo registro no banco de dados a partir de uma entidade.
     *
     * Este método encapsula a lógica de inserção em uma transação para garantir
     * a consistência dos dados. Ele converte a entidade fornecida em um array,
     * insere no banco de dados e retorna um array estruturado com o resultado
     * da operação.
     *
     * @param Entity $entity A entidade a ser persistida no banco de dados.
     * @return array{status: string, message: string, data: array}
     *               Um array associativo contendo:
     *               - 'status': 'success' ou 'error'.
     *               - 'message': Uma mensagem descritiva do resultado.
     *               - 'data': Um array com os dados do registro criado, incluindo o ID.
     * @throws PDOException Lançada se ocorrer um erro na comunicação com o banco de dados durante a inserção.
     */

    public function create(Entity $entity): array
    {
        try {
            // Executa a inserção dentro de uma transação
            return $this->connection->transactional(function () use ($entity) {
                $result = 0;

                // Converte o objeto Entity para um array
                $data = $entity->toArray();

                // Define os tipos de dados com base na classe Entity
                $types = $this->getEntityTypes($entity);

                // Insere os dados no banco de dados com os tipos especificados

                // Remove o campo 'id', 'created_at' e 'updated_at' do array
                unset($data[ 'id' ]);
                unset($data[ 'created_at' ]);
                unset($data[ 'updated_at' ]);

                // Tenta obter o último ID inserido
                // Verifica se a tabela possui uma chave primária composta
                $schemaManager = $this->connection->createSchemaManager();
                $primaryKeys = $schemaManager->listTableIndexes($this->table);

                $hasCompositeKey = false;
                foreach ($primaryKeys as $index) {
                    if ($index->isPrimary() && count($index->getColumns()) > 1) {
                        $hasCompositeKey = true;

                        break;
                    }
                }
                $this->connection->insert($this->table, $data, $types);

                if ($hasCompositeKey) {
                    // Caso a tabela tenha chave composta, não é possível obter um único ID
                    $result = $data; // Ou outra lógica para lidar com chaves compostas
                } else {
                    // Obtém o último ID inserido para tabelas com chave primária simples
                    $result = $this->connection->lastInsertId();
                    $data[ 'id' ] = $result;
                }

                return [
                    'status' => (int) $result > 0 ? 'success' : 'error',
                    'message' => (int) $result > 0 ? 'Registro criado com sucesso.' : 'Nenhum registro foi criado.',
                    'data' => $data,

                ];
            });
        } catch (Throwable $e) {
            throw new PDOException("Erro ao criar, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }
    }

    /**
     * Atualiza um registro existente no banco de dados a partir de uma entidade.
     *
     * Este método opera dentro de uma transação. Ele utiliza o 'id' da entidade
     * (e 'tenant_id' se existir) como identificador para a cláusula WHERE.
     * Retorna um array com o status da operação.
     *
     * @param Entity $entity A entidade com os dados atualizados. O 'id' deve estar presente.
     * @return array{status: string, message: string, data: array}
     *               Um array associativo contendo:
     *               - 'status': 'success' ou 'error'.
     *               - 'message': Uma mensagem descritiva do resultado.
     *               - 'data': Um array com os dados do registro criado, incluindo o ID.
     * @throws PDOException Lançada se ocorrer um erro na comunicação com o banco de dados.
     */
    public function update(Entity $entity): array
    {
        try {
            return $this->connection->transactional(function () use ($entity) {
                $result = 0;
                $data = $entity->toArray();
                $types = $this->getEntityTypes($entity);
                $identifier = isset($data[ 'tenant_id' ]) ? [
                    'id' => $data[ 'id' ],
                    'tenant_id' => $data[ 'tenant_id' ],
                ] : [ 'id' => $data[ 'id' ] ];

                // Remove o campo 'id', 'created_at' e 'updated_at' do array
                unset($data[ 'id' ]);
                unset($data[ 'created_at' ]);
                unset($data[ 'updated_at' ]);

                $result = $this->connection->update($this->table, $data, $identifier, $types);
                $data[ 'id' ] = $identifier[ 'id' ];

                return [
                    'status' => $result > 0 ? 'success' : 'error',
                    'message' => $result > 0 ? 'Registro atualizado com sucesso.' : 'Nenhum registro foi atualizado.',
                    'data' => $data,
                ];
            });
        } catch (Throwable $e) {
            throw new PDOException("Erro ao atualizar, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }
    }

    /**
     * Exclui um registro do banco de dados.
     *
     * A exclusão é feita com base no 'id' do registro e no 'tenant_id' para
     * garantir a segurança em um ambiente multitenant. A operação é executada
     * dentro de uma transação.
     *
     * @param int $id O ID do registro a ser excluído.
     * @param int $tenant_id O ID do tenant ao qual o registro pertence.
     * @return array{status: string, message: string, data: array} Um array associativo com o resultado da operação.
     * @throws PDOException Lançada se ocorrer um erro na comunicação com o banco de dados.
     */
    public function delete(int $id, int $tenant_id): array
    {
        try {
            return $this->connection->transactional(function () use ($id, $tenant_id) {
                $result = $this->connection->delete($this->table, [ 'id' => $id, 'tenant_id' => $tenant_id ]);

                return [
                    'status' => $result > 0 ? 'success' : 'error',
                    'message' => $result > 0 ? 'Registro deletado com sucesso.' : 'Nenhum registro foi deletado.',
                    'data' => [
                        'id' => $id,
                    ],
                ];
            });

        } catch (Throwable $e) {
            throw new PDOException("Erro ao excluir, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }
    }

    /**
     * Busca todos os registros da tabela associada ao modelo.
     *
     * @return array|Entity|EntityNotFound Retorna um array de entidades, uma única entidade se houver apenas um resultado, ou EntityNotFound se a tabela estiver vazia.
     * @throws PDOException Lançada se ocorrer um erro na comunicação com o banco de dados.
     */
    public function findAll(): array|Entity
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $result = $queryBuilder
                ->select('*')
                ->from($this->table)
                ->executeQuery()
                ->fetchAllAssociative();

            return $this->handleQueryResult($result);

        } catch (Throwable $e) {
            throw new PDOException("Erro ao buscar, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }
    }

    /**
     * Busca uma ou mais entidades com base em critérios de filtragem.
     *
     * Este método flexível permite construir consultas SELECT dinâmicas,
     * com suporte para ordenação, limitação (paginação) e seleção de campos específicos.
     *
     * @param array<string, mixed> $criteria Um array associativo de `['coluna' => 'valor']` para a cláusula WHERE.
     * @param array<string, string>|null $orderBy Um array associativo para a ordenação, ex: `['nome' => 'ASC']`.
     * @param int|null $limit O número máximo de registros a serem retornados (para paginação).
     * @param int|null $offset O número de registros a serem pulados (para paginação).
     * @param string[] $fields Um array de colunas a serem selecionadas. O padrão é `['*']`.
     * @return array|Entity|EntityNotFound Retorna uma única entidade, um array de entidades ou EntityNotFound se nada for encontrado.
     * @throws PDOException Lançada se ocorrer um erro na comunicação com o banco de dados.
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, array $fields = [ '*' ]): array|Entity|EntityNotFound
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder->select(...$fields)->from($this->table);

            foreach ($criteria as $column => $value) {
                $queryBuilder->andWhere($queryBuilder->expr()->eq($column, ":$column"))
                    ->setParameter($column, $value);
            }

            if ($orderBy) {
                foreach ($orderBy as $column => $direction) {
                    $queryBuilder->addOrderBy($column, $direction);
                }
            }

            if ($limit) {
                $queryBuilder->setMaxResults($limit);
            }

            if ($offset) {
                $queryBuilder->setFirstResult($offset);
            }

            $result = $queryBuilder->executeQuery()->fetchAllAssociative();

            return $this->handleQueryResult($result);

        } catch (Throwable $e) {
            throw new PDOException("Erro ao buscar, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }
    }

    /**
     * Busca entidades com base em critérios, utilizando JOINs com outras tabelas.
     *
     * Este método é útil para consultas complexas que necessitam de dados de tabelas relacionadas.
     *
     * @param array $criteria Condições de busca no formato `['alias.coluna' => 'valor']`.
     * @param array $joins Configurações de JOIN. Cada join é um array com 'type', 'table', 'alias', e 'condition'.
     *                     Exemplo: `[['type' => 'innerJoin', 'table' => 'users', 'alias' => 'u', 'condition' => 'main.user_id = u.id']]`
     * @param array|null $orderBy Ordenação dos resultados no formato `['alias.coluna' => 'ASC|DESC']`.
     * @param int|null $limit Limite de resultados a serem retornados.
     * @param int|null $offset Offset para paginação.
     * @param array $fields Campos a serem selecionados. Ex: `['main.id', 'u.name']`.
     * @return array|Entity|EntityNotFound Retorna um array de entidades, uma única entidade, ou EntityNotFound.
     * @throws PDOException Lançada se ocorrer um erro na comunicação com o banco de dados.
     */
    public function findByJoins(
        array $criteria,
        array $joins,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
        array $fields = [ '*' ],
    ): array|Entity {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder->select(...$fields)->from($this->table, 'main');

            foreach ($joins as $join) {
                $joinType = $join[ 'type' ] ?? 'innerJoin';
                $queryBuilder->$joinType(
                    'main',
                    $join[ 'table' ],
                    $join[ 'alias' ],
                    $join[ 'condition' ],
                );
            }

            foreach ($criteria as $column => $value) {
                // Remova o prefixo 'main.' se estiver presente
                $paramName = str_replace('main.', '', $column);
                $queryBuilder->andWhere($queryBuilder->expr()->eq($column, ":$paramName"))
                    ->setParameter($paramName, $value);
            }

            if ($orderBy) {
                foreach ($orderBy as $column => $direction) {
                    $queryBuilder->addOrderBy($column, $direction);
                }
            }

            if ($limit) {
                $queryBuilder->setMaxResults($limit);
            }
            if ($offset) {
                $queryBuilder->setFirstResult($offset);
            }

            $result = $queryBuilder->executeQuery()->fetchAllAssociative();

            return $this->handleQueryResult($result);

        } catch (Throwable $e) {
            throw new PDOException("Erro ao buscar com joins, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }
    }

    /**
     * Obtém os tipos de dados Doctrine a partir das propriedades de uma entidade.
     *
     * Este método utiliza Reflection para inspecionar as propriedades públicas de uma
     * entidade e mapear seus tipos PHP para os tipos correspondentes do Doctrine DBAL.
     *
     * @param Entity $entity A entidade a ser inspecionada.
     * @return array Um array associativo `['nome_da_propriedade' => 'tipo_doctrine']`.
     */
    protected function getEntityTypes(Entity $entity): array
    {
        try {
            $types = [];
            $reflection = new ReflectionClass($entity);
            $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {
                $type = $property->getType();
                if ($type instanceof \ReflectionNamedType) {
                    $types[ $property->getName()] = $this->mapPhpTypeToDoctrineType($type->getName());
                }
            }

            return $types;
        } catch (Throwable $e) {
            throw new Exception("Erro ao buscar, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }

    }

    /**
     * Mapeia um tipo PHP para seu tipo correspondente no Doctrine DBAL.
     *
     * @param string $phpType O nome do tipo PHP (ex: 'string', 'int', 'DateTime').
     * @return string O tipo correspondente do Doctrine DBAL (ex: Types::STRING).
     */
    protected function mapPhpTypeToDoctrineType(string $phpType): string
    {
        return match ($phpType) {
            'string' => Types::STRING,
            'int' => Types::INTEGER,
            'float' => Types::FLOAT,
            'bool' => Types::BOOLEAN,
            'DateTime' => Types::DATETIME_MUTABLE,
            'DateTimeImmutable' => Types::DATETIME_IMMUTABLE,
            // Adicione mais mapeamentos conforme necessário
            default => Types::STRING, // Tipo padrão
        };
    }

    /**
     * Retorna o nome da tabela associada a este modelo.
     *
     * @return string O nome da tabela.
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Retorna um array com as colunas da tabela associada a este modelo.
     *
     * @return array Um array de objetos Column.
     */
    public function getColumnsTable(): array
    {
        // Obtém as colunas da tabela usando a consulta ao banco de dados
        $schemaManager = $this->connection->createSchemaManager();

        return $schemaManager->listTableColumns($this->table);
    }

}
