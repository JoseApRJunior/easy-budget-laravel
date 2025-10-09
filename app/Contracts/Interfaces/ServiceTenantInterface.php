<?php
declare(strict_types=1);

namespace App\Contracts\Interfaces;

use App\Support\ServiceResult;

interface ServiceTenantInterface extends BaseServiceInterface
{
    /**
     * Busca uma entidade pelo ID e tenant ID.
     *
     * @param int $id
     * @param int $tenantId
     * @return ServiceResult
     */
    public function getByIdAndTenantId( int $id, int $tenantId ): ServiceResult;

    /**
     * Lista entidades pelo tenant ID.
     *
     * @param int $tenantId
     * @param array $filters
     * @param ?array $orderBy
     * @param ?int $limit
     * @param ?int $offset
     * @return ServiceResult
     */
    public function listByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): ServiceResult;

    /**
     * Cria uma nova entidade para tenant.
     *
     * @param array $data
     * @param int $tenantId
     * @return ServiceResult
     */
    public function create( array $data, int $tenantId ): ServiceResult;

    /**
     * Atualiza entidade por ID e tenant ID.
     *
     * @param int $id
     * @param array $data
     * @param int $tenantId
     * @return ServiceResult
     */
    public function update( int $id, array $data, int $tenantId ): ServiceResult;

    /**
     * Remove entidade por ID e tenant ID.
     *
     * @param int $id
     * @param int $tenantId
     * @return ServiceResult
     */
    public function delete( int $id, int $tenantId ): ServiceResult;

    /**
     * Valida dados para tenant (create/update).
     *
     * @param array $data
     * @param int $tenantId
     * @param bool $isUpdate
     * @return ServiceResult
     */
    public function validateForTenant( array $data, int $tenantId, bool $isUpdate = false ): ServiceResult;
}
