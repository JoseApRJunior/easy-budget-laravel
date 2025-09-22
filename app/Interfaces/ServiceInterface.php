<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Support\ServiceResult;

/**
 * Interface para services que são tenant-aware (multi-tenant)
 *
 * Esta interface define os 5 métodos essenciais para services que trabalham
 * com dados isolados por tenant, seguindo exatamente o padrão legacy.
 * Herda de BaseServiceInterface para incluir o método validate.
 */
interface ServiceInterface extends BaseServiceInterface
{
    /**
     * Busca uma entidade pelo ID e tenant_id
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return ServiceResult Resultado da operação com a entidade encontrada
     */
    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult;

    /**
     * Lista todas as entidades de um tenant
     *
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @param array $filters Filtros opcionais
     * @return ServiceResult Resultado da operação com lista de entidades
     */
    public function listByTenantId( int $tenant_id, array $filters = [] ): ServiceResult;

    /**
     * Cria uma nova entidade para um tenant
     *
     * @param array $data Dados da entidade a ser criada
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return ServiceResult Resultado da operação com a entidade criada
     */
    public function createByTenantId( array $data, int $tenant_id ): ServiceResult;

    /**
     * Atualiza uma entidade existente por ID e tenant_id
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @param array $data Dados para atualização
     * @return ServiceResult Resultado da operação com a entidade atualizada
     */
    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult;

    /**
     * Remove uma entidade por ID e tenant_id
     *
     * @param int $id ID da entidade a ser removida
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return ServiceResult Resultado da operação de remoção
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult;
}
