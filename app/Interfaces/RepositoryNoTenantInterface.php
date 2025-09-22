<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface para repositórios que trabalham com dados globais (não tenant-aware)
 *
 * Esta interface define os métodos padrão para repositórios que operam
 * com dados globais sem isolamento por tenant, como roles, permissions, plans, etc.
 */
interface RepositoryNoTenantInterface extends BaseRepositoryInterface
{
    /**
     * Busca uma entidade pelo ID
     *
     * @param int $id ID da entidade
     * @return Model|null Entidade encontrada ou null
     */
    public function findById( int $id ): ?Model;

    /**
     * Busca múltiplas entidades por seus IDs
     *
     * @param array $id Array de IDs das entidades
     * @return Model[] Array de entidades encontradas
     */
    public function findManyByIds( array $id ): array;

    /**
     * Busca entidades por critérios específicos
     *
     * @param array $criteria Critérios de busca
     * @param array|null $orderBy Ordenação opcional [campo => direção]
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Offset para paginação
     * @return Model[] Array de entidades encontradas
     */
    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array;

    /**
     * Busca uma entidade por critérios específicos
     *
     * @param array $criteria Critérios de busca
     * @return Model|null Entidade encontrada ou null
     */
    public function findOneBy( array $criteria ): ?Model;

    /**
     * Busca todas as entidades
     *
     * @param array $criteria Critérios adicionais de busca
     * @param array|null $orderBy Ordenação opcional [campo => direção]
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Offset para paginação
     * @return Model[] Array de todas as entidades
     */
    public function findAll(
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array;

    /**
     * Busca entidade por slug
     *
     * @param string $slug Slug da entidade
     * @return Model|null Entidade encontrada ou null
     */
    public function findBySlug( string $slug ): ?Model;

    /**

    /**
     * Salva uma entidade no banco
     *
     * @param Model $entity Entidade a ser salva
     * @return bool True se salvou com sucesso, false caso contrário
     */
    public function save( Model $entity ): Model|false;

    /**
     * Atualiza uma entidade existente
     *
     * @param Model $entity Entidade a ser atualizada
     * @return bool True se atualizou com sucesso, false caso contrário
     */
    public function update( Model $entity ): Model|false;

    /**
     * Remove uma entidade pelo ID
     *
     * @param int $id ID da entidade a ser removida
     * @return bool True se removeu com sucesso, false caso contrário
     */
    public function deleteById( int $id ): bool;

    /**
     * Remove uma entidade
     *
     * @param Model $entity Entidade a ser removida
     * @return bool True se removeu com sucesso, false caso contrário
     */
    public function delete( Model $entity ): bool;

    /**
     * Conta entidades por critérios
     *
     * @param array $criteria Critérios opcionais de busca
     * @return int Número de entidades encontradas
     */
    public function countBy( array $criteria = [] ): int;

    /**
     * Verifica se existe uma entidade por critérios
     *
     * @param array $criteria Critérios de busca
     * @return bool True se existe, false caso contrário
     */
    public function existsBy( array $criteria ): bool;

    /**
     * Busca entidades com paginação
     *
     * @param int $page Página atual (inicia em 1)
     * @param int $perPage Itens por página
     * @param array $criteria Critérios opcionais de busca
     * @param array|null $orderBy Ordenação opcional
     * @return array ['data' => Model[], 'total' => int, 'current_page' => int, 'per_page' => int]
     */
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        array $criteria = [],
        ?array $orderBy = null,
    ): array;

    /**
     * Remove múltiplas entidades por IDs
     *
     * @param array $id Array de IDs das entidades
     * @return int Número de entidades removidas
     */
    public function deleteManyByIds( array $id ): int;

    /**
     * Atualiza múltiplas entidades por critérios
     *
     * @param array $criteria Critérios para seleção
     * @param array $updates Dados para atualização
     * @return int Número de entidades atualizadas
     */
    public function updateMany( array $criteria, array $updates ): int;

    /**
     * Busca entidades ordenadas por um campo específico
     *
     * @param string $field Campo para ordenação
     * @param string $direction Direção da ordenação ('asc' ou 'desc')
     * @param int|null $limit Limite de resultados
     * @return Model[] Array de entidades ordenadas
     */
    public function findOrderedBy( string $field, string $direction = 'asc', ?int $limit = null ): array;

    /**
     * Busca a primeira entidade por critérios
     *
     * @param array $criteria Critérios de busca
     * @param array|null $orderBy Ordenação opcional
     * @return Model|null Primeira entidade encontrada ou null
     */
    public function findFirstBy( array $criteria, ?array $orderBy = null ): ?Model;

    /**
     * Busca a última entidade por critérios
     *
     * @param array $criteria Critérios de busca
     * @param array|null $orderBy Ordenação opcional
     * @return Model|null Última entidade encontrada ou null
     */
    public function findLastBy( array $criteria, ?array $orderBy = null ): ?Model;
}
