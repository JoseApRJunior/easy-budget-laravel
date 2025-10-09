<?php

declare(strict_types=1);

namespace App\Repositories\Abstracts;

use App\Contracts\Interfaces\TenantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório abstrato base para operações com modelos tenant-scoped
 *
 * Esta classe implementa apenas os métodos básicos essenciais para repositórios
 * que trabalham com dados isolados por tenant
 */
abstract class AbstractTenantRepository implements TenantRepositoryInterface
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
     * Busca uma entidade pelo seu ID.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return Model|null Retorna a entidade encontrada ou null.
     */
    public function findByIdAndTenantId( int $id, int $tenant_id ): ?Model
    {
        return $this->model->where( 'tenant_id', $tenant_id )->find( $id );
    }

    /**
     * Busca todas as entidades de um tenant.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $criteria Critérios adicionais de busca
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return Collection<Model> Retorna uma coleção de entidades (pode ser vazia).
     */
    public function findAllByTenantId( int $tenant_id, array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): Collection
    {
        $query = $this->model->where( 'tenant_id', $tenant_id );

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
     * Salva uma entidade no banco de dados.
     *
     * @param Model $entity Entidade a ser salva
     * @param int $tenant_id ID do tenant
     * @return Model|bool Entidade completa com dados atualizados ou false em caso de falha
     */
    public function save( Model $entity, int $tenant_id ): Model|bool
    {
        // Garante que o tenant_id está definido na entidade
        $entity->tenant_id = $tenant_id;

        if ( $entity->exists ) {
            return $entity->update() ? $entity : false;
        } else {
            return $this->model->create( $entity->toArray() );
        }
    }

    /**
     * Exclui uma entidade do banco de dados.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return bool Retorna true em caso de sucesso na exclusão, false caso contrário.
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): bool
    {
        $model = $this->findByIdAndTenantId( $id, $tenant_id );
        if ( !$model ) {
            return false;
        }

        return $model->delete();
    }

    /**
     * Busca uma entidade pelo seu ID e tenant ou lança exceção se não encontrada.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return Model Entidade encontrada
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Se entidade não encontrada
     */
    public function findByIdAndTenantOrFail( int $id, int $tenant_id ): Model
    {
        return $this->model->where( 'tenant_id', $tenant_id )->findOrFail( $id );
    }

    /**
     * Busca a primeira entidade do tenant que corresponda aos critérios ou lança exceção se nenhuma for encontrada.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $criteria Critérios de busca
     * @return Model Primeira entidade encontrada
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Se nenhuma entidade encontrada
     */
    public function firstByTenantOrFail( int $tenant_id, array $criteria ): Model
    {
        $query = $this->model->where( 'tenant_id', $tenant_id );

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
     * @param int $tenant_id ID do tenant
     * @return bool Resultado da operação
     * @throws \Exception Se a exclusão falhar
     */
    public function deleteOrFail( int $id, int $tenant_id ): bool
    {
        $model = $this->findByIdAndTenantId( $id, $tenant_id );
        if ( !$model ) {
            throw new \Exception( 'Entidade não encontrada para exclusão' );
        }

        if ( !$model->delete() ) {
            throw new \Exception( 'Falha ao excluir entidade' );
        }

        return true;
    }

    /**
     * Valida se o modelo pertence ao tenant especificado
     *
     * @param Model $model Modelo a ser validado
     * @param int $tenantId ID do tenant
     * @return bool True se pertence ao tenant
     */
    public function validateTenantOwnership( Model $model, int $tenantId ): bool
    {
        return $model->getAttribute( 'tenant_id' ) === $tenantId;
    }

}
