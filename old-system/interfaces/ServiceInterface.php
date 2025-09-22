<?php

namespace app\interfaces;

use app\support\ServiceResult;

/**
 * Interface ServiceInterface
 *
 * Define o contrato para todas as classes de serviço no sistema.
 * Responsável por encapsular a lógica de negócios e orquestrar operações entre repositórios.
 */
interface ServiceInterface extends BaseServiceInterface
{
    /**
     * Busca uma entidade pelo seu ID.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult;

    /**
     * Lista todas as entidades de um tenant com possibilidade de filtros.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $filters Filtros a serem aplicados
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function listByTenantId( int $tenant_id, array $filters = [] ): ServiceResult;

    /**
     * Cria uma nova entidade.
     *
     * @param array<string, mixed> $data Dados para criação da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function createByTenantId( array $data, int $tenant_id ): ServiceResult;

    /**
     * Atualiza uma entidade existente.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function updateByIdAndTenantId( int $id, int $tenant_id, array $data ): ServiceResult;

    /**
     * Remove uma entidade.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult;
}
