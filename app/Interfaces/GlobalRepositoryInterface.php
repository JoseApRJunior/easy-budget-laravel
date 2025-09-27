<?php

declare(strict_types=1);

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface para repositórios globais
 *
 * Define métodos para repositórios que trabalham com dados globais,
 * sem isolamento por tenant_id
 */
interface GlobalRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Cria um novo registro
     *
     * @param array $data Dados do registro
     * @return Model Registro criado
     */
    public function create( array $data ): Model;

    /**
     * Atualiza um registro existente
     *
     * @param Model $model Registro a ser atualizado
     * @param array $data Dados para atualização
     * @return Model Registro atualizado
     */
    public function update( Model $model, array $data ): Model;

    /**
     * Remove um registro
     *
     * @param Model $model Registro a ser removido
     * @return bool True se removido com sucesso
     */
    public function delete( Model $model ): bool;

    /**
     * Busca registros por critérios
     *
     * @param array $criteria Critérios de busca
     * @return Collection Coleção de registros encontrados
     */
    public function findByCriteria( array $criteria ): Collection;

    /**
     * Busca um registro por critérios
     *
     * @param array $criteria Critérios de busca
     * @return Model|null Registro encontrado ou null
     */
    public function findOneByCriteria( array $criteria ): ?Model;

    /**
     * Busca registro por slug
     *
     * @param string $slug Slug do registro
     * @return Model|null Registro encontrado ou null
     */
    public function findBySlug( string $slug ): ?Model;

    /**
     * Atualiza múltiplos registros por critérios
     *
     * @param array $criteria Critérios para seleção
     * @param array $updates Dados para atualização
     * @return int Número de registros atualizados
     */
    public function updateMany( array $criteria, array $updates ): int;

    /**
     * Remove múltiplos registros por critérios
     *
     * @param array $criteria Critérios para seleção
     * @return int Número de registros removidos
     */
    public function deleteMany( array $criteria ): int;

    /**
     * Busca registros paginados
     *
     * @param int $perPage Itens por página
     * @param array $criteria Critérios de busca
     * @return LengthAwarePaginator Paginator com resultados
     */
    public function paginate( int $perPage = 15, array $criteria = [] ): LengthAwarePaginator;

    /**
     * Valida se um valor é único em um campo
     *
     * @param string $field Campo a ser verificado
     * @param mixed $value Valor a ser verificado
     * @param int|null $excludeId ID a ser excluído da verificação (para updates)
     * @return bool True se é único, false caso contrário
     */
    public function validateUnique( string $field, mixed $value, ?int $excludeId = null ): bool;
}
