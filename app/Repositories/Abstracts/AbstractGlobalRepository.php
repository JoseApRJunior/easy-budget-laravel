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
    public function find( int $id ): ?Model
    {
        return $this->model->find( $id );
    }

    /**
     * Busca todos os registros
     *
     * @return Collection Coleção de registros
     */
    public function all(): Collection
    {
        return $this->model->all();
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
     * Atualiza um registro existente
     *
     * @param int $id ID do registro a ser atualizado
     * @param array $data Dados para atualização
     * @return bool True se atualizado com sucesso
     */
    public function update( int $id, array $data ): bool
    {
        $model = $this->find( $id );
        if ( !$model ) {
            return false;
        }

        return $model->update( $data );
    }

    /**
     * Remove um registro
     *
     * @param int $id ID do registro a ser removido
     * @return bool True se removido com sucesso
     */
    public function delete( int $id ): bool
    {
        $model = $this->find( $id );
        if ( !$model ) {
            return false;
        }

        return $model->delete();
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
     * Busca registros por critérios
     *
     * @param array $criteria Critérios de busca
     * @return Collection Coleção de registros encontrados
     */
    public function findBy( array $criteria ): Collection
    {
        $query = $this->model->newQuery();

        foreach ( $criteria as $field => $value ) {
            if ( is_array( $value ) ) {
                $query->whereIn( $field, $value );
            } else {
                $query->where( $field, $value );
            }
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
