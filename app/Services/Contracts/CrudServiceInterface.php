<?php
declare(strict_types=1);

namespace App\Services\Contracts;

use App\Support\ServiceResult;

/**
 * Interface CrudServiceInterface
 *
 * Contrato fundamental e completo para operações básicas de manipulação de dados (CRUD).
 *
 * Qualquer Service que implementa esta interface garante as 5 operações básicas
 * de acesso a dados.
 */
interface CrudServiceInterface
{
    // --------------------------------------------------------------------------
    // CREATE
    // --------------------------------------------------------------------------

    /**
     * Cria um novo recurso no repositório.
     *
     * @param array<string, mixed> $data Dados validados para criação.
     * @return ServiceResult Resultado com a entidade criada (ou erro de conflito/servidor).
     */
    public function create( array $data ): ServiceResult;

    // --------------------------------------------------------------------------
    // READ (Leitura)
    // --------------------------------------------------------------------------

    /**
     * Busca uma entidade pelo seu identificador único.
     *
     * @param int $id Identificador único da entidade.
     * @param array<string> $with Relacionamentos para eager loading (opcional).
     * @return ServiceResult Resultado com a entidade encontrada (ou erro 404).
     */
    public function findById( int $id, array $with = [] ): ServiceResult;

    /**
     * Lista entidades com paginação e filtros.
     *
     * @param array<string, mixed> $filters Filtros, paginação e ordenação (opcional).
     * @return ServiceResult Resultado paginado ou lista completa.
     */
    public function list( array $filters = [] ): ServiceResult;

    /**
     * Conta o total de entidades com base nos filtros aplicados.
     *
     * @param array<string, mixed> $filters Filtros para contagem (opcional).
     * @return ServiceResult Resultado com o contador numérico.
     */
    public function count( array $filters = [] ): ServiceResult;

    // --------------------------------------------------------------------------
    // UPDATE
    // --------------------------------------------------------------------------

    /**
     * Atualiza um recurso existente pelo ID.
     *
     * @param int $id Identificador único da entidade.
     * @param array<string, mixed> $data Dados validados para atualização.
     * @return ServiceResult Resultado com a entidade atualizada (ou erro 404/conflito).
     */
    public function update( int $id, array $data ): ServiceResult;

    // --------------------------------------------------------------------------
    // DELETE
    // --------------------------------------------------------------------------

    /**
     * Deleta um recurso pelo ID.
     *
     * @param int $id Identificador único da entidade.
     * @return ServiceResult Confirmação da exclusão (ou erro 404/conflito de chave estrangeira).
     */
    public function delete( int $id ): ServiceResult;
}
