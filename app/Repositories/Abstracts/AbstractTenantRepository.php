<?php
declare(strict_types=1);

namespace App\Repositories\Abstracts;

use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório abstrato base para modelos tenant-scoped.
 * * Assume que o Model retornado por makeModel() possui um Global Scope
 * que filtra automaticamente por tenant_id (HasTenantScope Trait).
 */
abstract class AbstractTenantRepository implements TenantRepositoryInterface
{
    protected Model $model;

    public function __construct()
    {
        $this->model = $this->makeModel();
    }

    /**
     * Define o Model a ser utilizado. Deve ser um Model com HasTenantScope.
     */
    abstract protected function makeModel(): Model;

    // --------------------------------------------------------------------------
    // MÉTODOS CRUD (O filtro de tenant é aplicado pelo MODEL)
    // --------------------------------------------------------------------------

    /**
     * Encontra um registro por ID.
     */
    public function find( int $id ): ?Model
    {
        // O find é seguro devido ao Global Scope no Model.
        return $this->model->find( $id );
    }

    /**
     * Busca todos os registros do tenant ativo.
     */
    public function getAll(): Collection
    {
        return $this->model->all();
    }

    /**
     * Cria um novo registro.
     */
    public function create( array $data ): Model
    {
        // O tenant_id deve ser preenchido aqui, ou na Service Layer, ou no Model.
        // Se o Service Layer envia o tenant_id, a criação é segura.
        return $this->model->create( $data );
    }

    /**
     * Atualiza um registro existente.
     */
    public function update( int $id, array $data ): ?Model
    {
        $model = $this->find( $id );

        if ( !$model ) {
            return null;
        }

        $model->update( $data );
        return $model;
    }

    /**
     * Remove um registro.
     */
    public function delete( int $id ): bool
    {
        // O método find() garante que a deleção só ocorre dentro do tenant.
        $model = $this->find( $id );

        if ( !$model ) {
            return false;
        }

        return (bool) $model->delete();
    }

}
