<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface para repositórios que trabalham com modelos globais.
 *
 * Define operações CRUD completas para modelos do sistema sem tenant_id,
 * incluindo funcionalidades avançadas como paginação e filtros.
 */
interface GlobalRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Busca uma entidade pelo seu ID.
     *
     * @param int $id ID da entidade
     * @return Model|null Retorna a entidade encontrada ou null.
     */
    public function findById( int $id ): ?Model;

    /**
     * Busca entidades com base em critérios específicos.
     *
     * @param array<string, mixed> $criteria Critérios de busca
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return Collection<Model> Retorna uma coleção de entidades (pode ser vazia).
     */
    public function findBy( array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): Collection;

    /**
     * Busca todas as entidades.
     *
     * @param array<string, mixed> $criteria Critérios adicionais de busca
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return Collection<Model> Retorna uma coleção de entidades (pode ser vazia).
     */
    public function findAll( array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): Collection;

    /**
     * Salva uma entidade no banco de dados.
     *
     * @param Model $entity Entidade a ser salva
     * @return Model|bool Entidade completa com dados atualizados ou false em caso de falha
     */
    public function save( Model $entity ): Model|bool;

    /**
     * Exclui uma entidade do banco de dados.
     *
     * @param int $id ID da entidade
     * @return bool Resultado da operação (true = sucesso, false = falha)
     */
    public function delete( int $id ): bool;

    /**
     * Busca uma entidade pelo seu ID ou lança exceção se não encontrada.
     *
     * @param int $id ID da entidade
     * @return Model Entidade encontrada
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Se entidade não encontrada
     */
    public function findOrFail( int $id ): Model;

    /**
     * Busca a primeira entidade que corresponda aos critérios ou lança exceção se nenhuma for encontrada.
     *
     * @param array<string, mixed> $criteria Critérios de busca
     * @return Model Primeira entidade encontrada
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Se nenhuma entidade encontrada
     */
    public function firstOrFail( array $criteria ): Model;

    /**
     * Atualiza uma entidade ou lança exceção se a operação falhar.
     *
     * @param Model $entity Entidade a ser atualizada
     * @param array<string, mixed> $data Dados para atualização
     * @return bool Resultado da operação
     * @throws \Exception Se a atualização falhar
     */
    public function updateOrFail( Model $entity, array $data ): bool;

    /**
     * Exclui uma entidade ou lança exceção se a operação falhar.
     *
     * @param int $id ID da entidade
     * @return bool Resultado da operação
     * @throws \Exception Se a exclusão falhar
     */
    public function deleteOrFail( int $id ): bool;
}
