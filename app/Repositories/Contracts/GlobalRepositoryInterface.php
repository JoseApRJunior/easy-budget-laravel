<?php
declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface GlobalRepositoryInterface
 *
 * Contrato especializado para repositórios que operam em contexto global,
 * sem restrições de tenant. Herda todas as operações básicas do BaseRepositoryInterface
 * e adiciona funcionalidades avançadas específicas para operações globais.
 *
 * Esta interface é ideal para:
 * - Entidades que não precisam de isolamento por tenant (ex: configurações globais)
 * - Operações administrativas que acessam dados de todos os tenants
 * - Relatórios e analytics que agregam dados globais
 *
 * @package App\Repositories\Contracts
 */
interface GlobalRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Busca registros com critérios avançados de filtro, ordenação e paginação.
     *
     * @param array<string, mixed> $criteria Critérios de filtro (where conditions).
     * @param array<string, string>|null $orderBy Campos para ordenação ['campo' => 'asc|desc'].
     * @param int|null $limit Limite de registros (para evitar paginação).
     * @param int|null $offset Offset para paginação manual.
     * @return Collection<Model> Coleção filtrada e ordenada.
     *
     * @example
     * $criteria = ['status' => 'active', 'type' => 'premium'];
     * $orderBy = ['created_at' => 'desc', 'name' => 'asc'];
     * $users = $repository->getAllGlobal($criteria, $orderBy, 50, 0);
     */
    public function getAllGlobal(
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection;

    /**
     * Busca um registro específico pelo ID no contexto global.
     *
     * @param int $id Identificador único do registro.
     * @return Model|null Registro encontrado ou null.
     *
     * @example
     * $user = $repository->findGlobal(123);
     */
    public function findGlobal( int $id ): ?Model;

    /**
     * Cria um novo registro no contexto global.
     *
     * @param array<string, mixed> $data Dados para criação.
     * @return Model Registro criado.
     *
     * @example
     * $data = ['name' => 'Sistema Global', 'type' => 'config'];
     * $config = $repository->createGlobal($data);
     */
    public function createGlobal( array $data ): Model;

    /**
     * Atualiza um registro existente no contexto global.
     *
     * @param int $id ID do registro a ser atualizado.
     * @param array<string, mixed> $data Dados para atualização.
     * @return Model|null Registro atualizado ou null se não encontrado.
     *
     * @example
     * $updated = $repository->updateGlobal(123, ['name' => 'Novo Nome']);
     */
    public function updateGlobal( int $id, array $data ): ?Model;

    /**
     * Remove um registro pelo ID no contexto global.
     *
     * @param int $id ID do registro a ser removido.
     * @return bool True se removido com sucesso.
     *
     * @example
     * $deleted = $repository->deleteGlobal(123);
     */
    public function deleteGlobal( int $id ): bool;

    /**
     * Retorna registros paginados com filtros opcionais.
     *
     * @param int $perPage Número de itens por página (padrão: 15).
     * @param array<string, mixed> $filters Filtros a serem aplicados.
     * @return LengthAwarePaginator Resultado paginado.
     *
     * @example
     * $users = $repository->paginateGlobal(10, ['status' => 'active']);
     * echo $users->total(); // Total de registros
     * foreach ($users as $user) { ... }
     */
    public function paginateGlobal( int $perPage = 15, array $filters = [] ): LengthAwarePaginator;

    /**
     * Conta registros baseado nos filtros aplicados.
     *
     * @param array<string, mixed> $filters Filtros para contagem.
     * @return int Número total de registros encontrados.
     *
     * @example
     * $activeUsersCount = $repository->countGlobal(['status' => 'active']);
     */
    public function countGlobal( array $filters = [] ): int;
}
