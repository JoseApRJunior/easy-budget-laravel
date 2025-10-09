<?php

declare(strict_types=1);

namespace App\Repositories\Abstracts;

use App\Contracts\Interfaces\GlobalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositório abstrato base para operações globais
 *
 * Esta classe implementa apenas os métodos básicos essenciais para repositórios
 * que trabalham com dados globais, sem isolamento por tenant_id
 */
abstract class AbstractGlobalRepository implements GlobalRepositoryInterface
{
    protected Model $model;

    /**
     * Construtor básico do repositório
     */
    public function __construct()
    {
        $this->model = $this->makeModel();
    }

    /**
     * Cria uma nova instância do modelo
     *
     * @return Model
     */
    abstract protected function makeModel(): Model;

    /**
     * Encontra um registro por ID
     *
     * @param int $id ID do registro
     * @return Model|null Registro encontrado ou null
     */
    public function findById( int $id ): ?Model
    {
        return $this->model->find( $id );
    }

    /**
     * Busca todas as entidades.
     *
     * @param array<string, mixed> $criteria Critérios adicionais de busca
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return Collection<Model> Retorna uma coleção de entidades (pode ser vazia).
     */
    public function findAll( array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): Collection
    {
        $query = $this->model->newQuery();

        // Aplica critérios de busca
        if ( !empty( $criteria ) ) {
            foreach ( $criteria as $field => $value ) {
                if ( is_array( $value ) ) {
                    $query->whereIn( $field, $value );
                } else {
                    $query->where( $field, $value );
                }
            }
        }

        // Aplica ordenação
        if ( $orderBy !== null ) {
            foreach ( $orderBy as $field => $direction ) {
                $query->orderBy( $field, $direction );
            }
        }

        // Aplica limite e offset
        if ( $offset !== null ) {
            $query->offset( $offset );
        }

        if ( $limit !== null ) {
            $query->limit( $limit );
        }

        return $query->get();
    }

    /**
     * Cria um novo registro
     *
     * @param array $data Dados do registro
     * @return Model Registro criado
     */
    public function create( array $data ): Model
    {
        return $this->model->create( $data );
    }

    /**
     * Salva uma entidade no banco de dados.
     *
     * @param Model $entity Entidade a ser salva
     * @return Model|bool Entidade completa com dados atualizados ou false em caso de falha
     */
    public function save( Model $entity ): Model|bool
    {
        if ( $entity->exists ) {
            return $entity->update() ? $entity : false;
        } else {
            return $this->model->create( $entity->toArray() );
        }
    }

    /**
     * Busca uma entidade pelo seu ID ou lança exceção se não encontrada.
     *
     * @param int $id ID da entidade
     * @return Model Entidade encontrada
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Se entidade não encontrada
     */
    public function findOrFail( int $id ): Model
    {
        return $this->model->findOrFail( $id );
    }

    /**
     * Busca a primeira entidade que corresponda aos critérios ou lança exceção se nenhuma for encontrada.
     *
     * @param array<string, mixed> $criteria Critérios de busca
     * @return Model Primeira entidade encontrada
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Se nenhuma entidade encontrada
     */
    public function firstOrFail( array $criteria ): Model
    {
        $query = $this->model->newQuery();

        foreach ( $criteria as $field => $value ) {
            if ( is_array( $value ) ) {
                $query->whereIn( $field, $value );
            } else {
                $query->where( $field, $value );
            }
        }

        return $query->firstOrFail();
    }

    /**
     * Atualiza uma entidade ou lança exceção se a operação falhar.
     *
     * @param Model $entity Entidade a ser atualizada
     * @param array<string, mixed> $data Dados para atualização
     * @return bool Resultado da operação
     * @throws \Exception Se a atualização falhar
     */
    public function updateOrFail( Model $entity, array $data ): bool
    {
        $entity->fill( $data );
        if ( !$entity->update() ) {
            throw new \Exception( 'Falha ao atualizar entidade' );
        }
        return true;
    }

    /**
     * Exclui uma entidade ou lança exceção se a operação falhar.
     *
     * @param int $id ID da entidade
     * @return bool Resultado da operação
     * @throws \Exception Se a exclusão falhar
     */
    public function deleteOrFail( int $id ): bool
    {
        $model = $this->findById( $id );
        if ( !$model ) {
            throw new \Exception( 'Entidade não encontrada para exclusão' );
        }

        if ( !$model->delete() ) {
            throw new \Exception( 'Falha ao excluir entidade' );
        }

        return true;
    }

    /**
     * Busca registros paginados
     *
     * @param int $perPage Itens por página
     * @param array $filters Filtros de busca
     * @return LengthAwarePaginator Paginator com resultados
     */
    public function paginate( int $perPage = 15, array $filters = [] ): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if ( !empty( $filters ) ) {
            foreach ( $filters as $field => $value ) {
                if ( is_array( $value ) ) {
                    $query->whereIn( $field, $value );
                } else {
                    $query->where( $field, $value );
                }
            }
        }

        return $query->paginate( $perPage );
    }

    /**
     * Busca entidades com base em critérios específicos.
     *
     * @param array<string, mixed> $criteria Critérios de busca
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return Collection<Model> Retorna uma coleção de entidades (pode ser vazia).
     */
    public function findBy( array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): Collection
    {
        $query = $this->model->newQuery();

        // Aplica critérios de busca
        foreach ( $criteria as $field => $value ) {
            if ( is_array( $value ) ) {
                $query->whereIn( $field, $value );
            } else {
                $query->where( $field, $value );
            }
        }

        // Aplica ordenação
        if ( $orderBy !== null ) {
            foreach ( $orderBy as $field => $direction ) {
                $query->orderBy( $field, $direction );
            }
        }

        // Aplica limite e offset
        if ( $offset !== null ) {
            $query->offset( $offset );
        }

        if ( $limit !== null ) {
            $query->limit( $limit );
        }

        return $query->get();
    }

    /**
     * Conta o número total de registros
     *
     * @param array $filters Filtros opcionais para contar
     * @return int Número de registros
     */
    public function count( array $filters = [] ): int
    {
        $query = $this->model->newQuery();

        if ( !empty( $filters ) ) {
            foreach ( $filters as $field => $value ) {
                if ( is_array( $value ) ) {
                    $query->whereIn( $field, $value );
                } else {
                    $query->where( $field, $value );
                }
            }
        }

        return $query->count();
    }

}
