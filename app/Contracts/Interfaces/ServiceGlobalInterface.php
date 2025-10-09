<?php
declare(strict_types=1);

namespace App\Contracts\Interfaces;

use App\Support\ServiceResult;

/**
 * Interface ServiceNoTenantInterface
 *
 * Define o contrato para todas as classes de serviço no sistema.
 * Responsável por encapsular a lógica de negócios e orquestrar operações entre repositórios.
 */
interface ServiceGlobalInterface extends BaseServiceInterface
{
    /**
     * Busca uma entidade pelo seu ID.
     *
     * @param int $id ID da entidade
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function getById( int $id ): ServiceResult;

    /**
     * Lista todas as entidades com possibilidade de filtros.
     *
     * @param array<string, mixed> $filters Filtros a serem aplicados
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function list( array $filters = [] ): ServiceResult;

    /**
     * Cria uma nova entidade.
     *
     * @param array<string, mixed> $data Dados para criação da entidade
     * @return ServiceResult Resultado da operação
     */
    public function create( array $data ): ServiceResult;

    /**
     * Atualiza uma entidade existente.
     *
     * @param int $id ID da entidade
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function update( int $id, array $data ): ServiceResult;

    /**
     * Remove uma entidade.
     *
     * @param int $id ID da entidade
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult;
}
