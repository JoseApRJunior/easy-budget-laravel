<?php
declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
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
 *
 * @package App\Repositories\Contracts
 */
interface TenantRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Busca registros com filtros avançados específicos do tenant.
     *
     * @param array<string, mixed> $criteria Critérios de filtro.
     * @param array<string, string>|null $orderBy Ordenação ['campo' => 'asc|desc'].
     * @param int|null $limit Limite de registros.
     * @param int|null $offset Offset para paginação.
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
     * Retorna registros paginados do tenant atual.
     *
     * @param int $perPage Itens por página (padrão: 10).
     * @param array<string, mixed> $filters Filtros a aplicar.
     * @param array<string, string>|null $orderBy Ordenação.
     * @return LengthAwarePaginator Resultado paginado.
     *
     * @example
     * $clients = $repository->paginate(10, ['status' => 'active']);
     */
    public function paginate(
        int $perPage = 10,
        array $filters = [],
        ?array $orderBy = null,
    ): LengthAwarePaginator;

    /**
     * Conta registros do tenant atual com filtros opcionais.
     *
     * @param array<string, mixed> $filters Filtros para contagem.
     * @return int Total de registros encontrados.
     *
     * @example
     * $activeClientsCount = $repository->countByTenant(['status' => 'active']);
     */
    public function countByTenant( array $filters = [] ): int;

    /**
     * Busca registros por slug único dentro do tenant atual.
     *
     * @param string $slug Slug único do registro.
     * @return Model|null Registro encontrado ou null.
     *
     * @example
     * $category = $repository->findByTenantAndSlug('produtos-eletronicos');
     */
    public function findByTenantAndSlug( string $slug ): ?Model;

    /**
     * Busca registros por código único dentro do tenant atual.
     *
     * @param string $code Código único do registro.
     * @return Model|null Registro encontrado ou null.
     *
     * @example
     * $product = $repository->findByTenantAndCode('PROD-001');
     */
    public function findByTenantAndCode( string $code ): ?Model;

    /**
     * Verifica se um valor de campo único já existe dentro do tenant atual.
     *
     * @param string $field Campo a verificar unicidade.
     * @param mixed $value Valor a ser verificado.
     * @param int|null $excludeId ID a ser excluído da verificação (para updates).
     * @return bool True se o valor já existe.
     *
     * @example
     * $emailExists = $repository->isUniqueInTenant('email', 'user@example.com', 1);
     */
    public function isUniqueInTenant( string $field, mixed $value, ?int $excludeId = null ): bool;

    /**
     * Busca registros por múltiplos IDs dentro do tenant atual.
     *
     * @param array<int> $ids Lista de IDs a buscar.
     * @return Collection<Model> Registros encontrados.
     *
     * @example
     * $products = $repository->findManyByTenant([1, 2, 3, 4]);
     */
    public function findManyByTenant( array $ids ): Collection;

    /**
     * Remove múltiplos registros por IDs dentro do tenant atual.
     *
     * @param array<int> $ids Lista de IDs a remover.
     * @return int Número de registros removidos.
     *
     * @example
     * $deletedCount = $repository->deleteManyByTenant([1, 2, 3]);
     */
    public function deleteManyByTenant( array $ids ): int;
}
