<?php

declare(strict_types=1);

namespace App\Services\Abstracts;

use App\Interfaces\ServiceInterface;
use App\Services\Abstracts\BaseService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Model;

/**
 * Classe base abstrata para serviços tenant-aware.
 *
 * Implementa ServiceInterface com os 5 métodos essenciais para operações
 * tenant-isoladas. Serviços concretos devem implementar métodos abstratos
 * para lógica específica da entidade.
 *
 * MÉTODOS OBRIGATÓRIOS:
 * - getByIdAndTenantId(int $id, int $tenant_id): ServiceResult
 * - listByTenantId(int $tenant_id, array $filters = []): ServiceResult
 * - createByTenantId(array $data, int $tenant_id): ServiceResult
 * - updateByIdAndTenantId(int $id, array $data, int $tenantId): ServiceResult
 * - deleteByIdAndTenantId(int $id, int $tenant_id): ServiceResult
 */
abstract class BaseTenantService extends BaseService implements ServiceInterface
{
    /**
     * Busca uma entidade pelo ID e tenant_id.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return ServiceResult
     */
    abstract public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult;

    /**
     * Lista entidades por tenant_id com filtros.
     *
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @param array $filters Filtros opcionais
     * @return ServiceResult
     */
    abstract public function listByTenantId( int $tenant_id, array $filters = [] ): ServiceResult;

    /**
     * Cria entidade para tenant_id.
     *
     * @param array $data Dados da entidade
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return ServiceResult
     */
    abstract public function createByTenantId( array $data, int $tenant_id ): ServiceResult;

    /**
     * Atualiza entidade por ID e tenant_id.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @param array $data Dados de atualização
     * @return ServiceResult
     */
    abstract public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult;

    /**
     * Deleta entidade por ID e tenant_id.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return ServiceResult
     */
    abstract public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult;

    /**
     * Busca uma entidade pelo ID e tenant_id.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return Model|null Entidade encontrada ou null
     */
    abstract protected function findEntityByIdAndTenantId( int $id, int $tenant_id ): ?Model;

    /**
     * Lista entidades por tenant_id.
     *
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @param array $filters Filtros opcionais
     * @param ?array $orderBy Ordem dos resultados
     * @param ?int $limit Limite de resultados
     * @param ?int $offset Offset dos resultados
     * @return array Lista de entidades
     */
    abstract protected function listEntitiesByTenantId( int $tenant_id, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array;

    /**
     * Cria uma nova entidade.
     *
     * @param array $data Dados da entidade
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return Model Entidade criada
     */
    abstract protected function createEntity( array $data, int $tenant_id ): Model;

    /**
     * Atualiza uma entidade existente.
     *
     * @param Model $entity Entidade a ser atualizada
     * @param array $data Dados para atualização
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return void
     */
    abstract protected function updateEntity( Model $entity, array $data, int $tenant_id ): void;

    /**
     * Remove uma entidade.
     *
     * @param Model $entity Entidade a ser removida
     * @return bool Sucesso da operação
     */
    abstract protected function deleteEntity( Model $entity ): bool;

    /**
     * Verifica se a entidade pode ser removida.
     *
     * @param Model $entity Entidade a ser verificada
     * @return bool Se pode ser removida
     */
    abstract protected function canDeleteEntity( Model $entity ): bool;

    /**
     * Salva uma entidade.
     *
     * @param Model $entity Entidade a ser salva
     * @return bool Sucesso da operação
     */
    abstract protected function saveEntity( Model $entity ): bool;

    /**
     * Verifica se a entidade pertence ao tenant.
     *
     * @param Model $entity Entidade a ser verificada
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return bool Se pertence ao tenant
     */
    abstract protected function belongsToTenant( Model $entity, int $tenant_id ): bool;

    /**
     * Valida dados para operações CRUD.
     *
     * @param array $data Dados a serem validados
     * @param bool $isUpdate Se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    abstract public function validate( array $data, bool $isUpdate = false ): ServiceResult;

}
