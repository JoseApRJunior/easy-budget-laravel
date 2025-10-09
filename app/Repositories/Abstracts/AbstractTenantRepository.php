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
     * Busca registro por ID e tenant
     *
     * @param int $id ID do registro
     * @param int $tenantId ID do tenant
     * @return Model|null Registro encontrado ou null
     */
    public function findByIdAndTenant( int $id, int $tenantId ): ?Model
    {
        return $this->model->where( 'tenant_id', $tenantId )->find( $id );
    }

    /**
     * Busca todos os registros de um tenant
     *
     * @param int $tenantId ID do tenant
     * @return Collection Coleção de registros
     */
    public function findAllByTenant( int $tenantId ): Collection
    {
        return $this->model->where( 'tenant_id', $tenantId )->get();
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
