<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface TenantRepositoryInterface
 *
 * Contrato especializado para repositórios que operam em contexto multi-tenant,
 * com isolamento automático de dados por empresa/tenant. Herda todas as operações
 * básicas do BaseRepositoryInterface e adiciona funcionalidades específicas para
 * ambientes multi-tenant.
 *
 * Características principais:
 * - Todos os métodos respeitam automaticamente o tenant_id do usuário autenticado
 * - Implementa Global Scope para filtrar dados automaticamente
 * - Garante isolamento completo de dados entre tenants
 * - Ideal para entidades específicas de cada empresa (clientes, produtos, etc.)
 */
interface TenantRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Busca registros com filtros avançados específicos do tenant.
     *
     * @param  array<string, mixed>  $criteria  Critérios de filtro.
     * @param  array<string, string>|null  $orderBy  Ordenação ['campo' => 'asc|desc'].
     * @param  int|null  $limit  Limite de registros.
     * @param  int|null  $offset  Offset para paginação.
     * @return Collection<Model> Registros filtrados do tenant atual.
     *
     * @example
     * $criteria = ['status' => 'active', 'category_id' => 1];
     * $orderBy = ['name' => 'asc'];
     * $products = $repository->getAllByTenant($criteria, $orderBy);
     */
    public function getAllByTenant(
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection;

    /**
     * Retorna registros paginados com filtros avançados.
     *
     * @param  array<string, mixed>  $filters  Filtros a aplicar (ex: ['search' => 'termo', 'active' => true, 'per_page' => 20])
     * @param  int  $perPage  Número padrão de itens por página (10)
     * @param  array<string>  $with  Relacionamentos para eager loading (ex: ['category', 'inventory'])
     * @param  array<string, string>|null  $orderBy  Ordenação personalizada (ex: ['name' => 'asc', 'created_at' => 'desc'])
     * @return LengthAwarePaginator Resultado paginado
     *
     * @example Uso básico:
     * ```php
     * $results = $repository->getPaginated();
     * ```
     * @example Com filtros:
     * ```php
     * $results = $repository->getPaginated([
     *     'search' => 'produto',
     *     'active' => true,
     *     'per_page' => 20
     * ]);
     * ```
     * @example Com eager loading:
     * ```php
     * $results = $repository->getPaginated([], 15, ['category', 'inventory']);
     * ```
     * @example Com ordenação customizada:
     * ```php
     * $results = $repository->getPaginated([], 15, [], ['created_at' => 'desc', 'name' => 'asc']);
     * ```
     */
    public function getPaginated(
        array $filters = [],
        int $perPage = 10,
        array $with = [],
        ?array $orderBy = null,
    ): LengthAwarePaginator;

    /**
     * Conta registros do tenant atual com filtros opcionais.
     *
     * @param  array<string, mixed>  $filters  Filtros para contagem.
     * @return int Total de registros encontrados.
     *
     * @example
     * $activeClientsCount = $repository->countByTenant(['status' => 'active']);
     */
    public function countByTenant(array $filters = []): int;

    /**
     * Busca registros por slug único dentro do tenant atual.
     *
     * @param  string  $slug  Slug único do registro.
     * @param  bool  $withTrashed  Se deve incluir registros deletados (soft delete).
     * @return Model|null Registro encontrado ou null.
     *
     * @example
     * $category = $repository->findByTenantAndSlug('produtos-eletronicos');
     */
    public function findByTenantAndSlug(string $slug, bool $withTrashed = false): ?Model;

    /**
     * Busca registros por código único dentro do tenant atual.
     *
     * @param  string  $code  Código único do registro.
     * @param  bool  $withTrashed  Se deve incluir registros deletados (soft delete).
     * @return Model|null Registro encontrado ou null.
     *
     * @example
     * $product = $repository->findByTenantAndCode('PROD-001');
     */
    public function findByTenantAndCode(string $code, bool $withTrashed = false): ?Model;

    /**
     * Verifica se um valor de campo único já existe dentro do tenant atual.
     *
     * @param  string  $field  Campo a verificar unicidade.
     * @param  mixed  $value  Valor a ser verificado.
     * @param  int|null  $excludeId  ID a ser excluído da verificação (para updates).
     * @return bool True se o valor já existe.
     *
     * @example
     * $emailExists = $repository->isUniqueInTenant('email', 'user@example.com', 1);
     */
    public function isUniqueInTenant(string $field, mixed $value, ?int $excludeId = null): bool;

    /**
     * Busca um único registro por um campo específico, opcionalmente incluindo deletados.
     *
     * @param  string  $field  Campo para busca.
     * @param  mixed  $value  Valor do campo.
     * @param  array  $with  Relacionamentos para carregar.
     * @param  bool  $withTrashed  Se deve incluir registros deletados.
     */
    public function findOneBy(string $field, mixed $value, array $with = [], bool $withTrashed = false): ?Model;

    /**
     * Busca registros por múltiplos IDs dentro do tenant atual.
     *
     * @param  array<int>  $ids  Lista de IDs a buscar.
     * @return Collection<Model> Registros encontrados.
     *
     * @example
     * $products = $repository->findManyByTenant([1, 2, 3, 4]);
     */
    public function findManyByTenant(array $ids): Collection;

    /**
     * Remove múltiplos registros por IDs dentro do tenant atual.
     *
     * @param  array<int>  $ids  Lista de IDs a remover.
     * @return int Número de registros removidos.
     *
     * @example
     * $deletedCount = $repository->deleteManyByTenant([1, 2, 3]);
     */
    public function deleteManyByTenant(array $ids): int;

    /**
     * Busca registros por nome/descrição com pesquisa parcial.
     *
     * @param  string  $search  Termo de busca.
     * @param  array<string, mixed>  $filters  Filtros adicionais.
     * @param  array<string, string>|null  $orderBy  Ordenação.
     * @param  int|null  $limit  Limite de resultados.
     * @return Collection<Model> Registros encontrados.
     *
     * @example
     * $results = $repository->searchByTenant('notebook', ['status' => 'active']);
     */
    public function searchByTenant(
        string $search,
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
    ): Collection;

    /**
     * Busca registros ativos (não deletados) do tenant atual.
     *
     * @param  array<string, mixed>  $filters  Filtros adicionais.
     * @param  array<string, string>|null  $orderBy  Ordenação.
     * @param  int|null  $limit  Limite de resultados.
     * @return Collection<Model> Registros ativos encontrados.
     *
     * @example
     * $activeProducts = $repository->getActiveByTenant(['category_id' => 1]);
     */
    public function getActiveByTenant(
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
    ): Collection;

    /**
     * Busca registros deletados (soft delete) do tenant atual.
     *
     * @param  array<string, mixed>  $filters  Filtros adicionais.
     * @param  array<string, string>|null  $orderBy  Ordenação.
     * @param  int|null  $limit  Limite de resultados.
     * @return Collection<Model> Registros deletados encontrados.
     *
     * @example
     * $deletedProducts = $repository->getDeletedByTenant(['category_id' => 1]);
     */
    public function getDeletedByTenant(
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
    ): Collection;

    /**
     * Restaura registros deletados (soft delete) por IDs.
     *
     * @param  array<int>  $ids  Lista de IDs a restaurar.
     * @return int Número de registros restaurados.
     *
     * @example
     * $restoredCount = $repository->restoreManyByTenant([1, 2, 3]);
     */
    public function restoreManyByTenant(array $ids): int;
}
