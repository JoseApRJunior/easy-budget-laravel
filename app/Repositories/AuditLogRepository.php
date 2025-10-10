<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AuditLog;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para gerenciamento de logs de auditoria.
 *
 * Esta classe implementa operações CRUD específicas para o modelo AuditLog,
 * fornecendo acesso controlado e consistente aos dados de auditoria do sistema.
 */
class AuditLogRepository implements BaseRepositoryInterface
{
    /**
     * Modelo gerenciado por este repositório.
     */
    protected AuditLog $model;

    /**
     * Construtor do repositório.
     */
    public function __construct( AuditLog $auditLog )
    {
        $this->model = $auditLog;
    }

    /**
     * {@inheritdoc}
     */
    public function find( int $id ): ?Model
    {
        return $this->model->find( $id );
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(): Collection
    {
        return $this->model->all();
    }

    /**
     * {@inheritdoc}
     */
    public function create( array $data ): Model
    {
        return $this->model->create( $data );
    }

    /**
     * {@inheritdoc}
     */
    public function update( int $id, array $data ): ?Model
    {
        $model = $this->find( $id );

        if ( !$model ) {
            return null;
        }

        $model->update( $data );

        return $model->fresh();
    }

    /**
     * {@inheritdoc}
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
     * Busca logs de auditoria por tenant.
     */
    public function findByTenantId( int $tenantId ): Collection
    {
        return $this->model->where( 'tenant_id', $tenantId )->get();
    }

    /**
     * Busca logs de auditoria por usuário.
     */
    public function findByUserId( int $userId ): Collection
    {
        return $this->model->where( 'user_id', $userId )->get();
    }

    /**
     * Busca logs de auditoria por ação.
     */
    public function findByAction( string $action ): Collection
    {
        return $this->model->where( 'action', $action )->get();
    }

    /**
     * Busca logs de auditoria por severidade.
     */
    public function findBySeverity( string $severity ): Collection
    {
        return $this->model->where( 'severity', $severity )->get();
    }

    /**
     * Busca logs de auditoria por categoria.
     */
    public function findByCategory( string $category ): Collection
    {
        return $this->model->where( 'category', $category )->get();
    }

    /**
     * Busca logs de auditoria por tipo de modelo.
     */
    public function findByModelType( string $modelType ): Collection
    {
        return $this->model->where( 'model_type', $modelType )->get();
    }

    /**
     * Busca logs de auditoria por ID do modelo.
     */
    public function findByModelId( int $modelId ): Collection
    {
        return $this->model->where( 'model_id', $modelId )->get();
    }

    /**
     * Conta logs de auditoria por tenant.
     */
    public function countByTenantId( int $tenantId ): int
    {
        return $this->model->where( 'tenant_id', $tenantId )->count();
    }

    /**
     * Conta logs de auditoria por severidade.
     */
    public function countBySeverity( string $severity ): int
    {
        return $this->model->where( 'severity', $severity )->count();
    }

    /**
     * Conta logs de auditoria por categoria.
     */
    public function countByCategory( string $category ): int
    {
        return $this->model->where( 'category', $category )->count();
    }

}
