<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Support\ServiceResult;

/**
 * Interface para serviços globais (sem tenant) - Padrão Legacy.
 *
 * Define contrato para operações CRUD básicas sem isolamento de tenant.
 * Esta interface segue exatamente o padrão legacy com apenas os 5 métodos
 * essenciais: getById, list, create, update e delete.
 *
 * Funcionalidades específicas como paginação devem ser implementadas
 * em interfaces opcionais ou classes concretas.
 */
interface ServiceNoTenantInterface extends BaseServiceInterface
{
    /**
     * Obtém entidade por ID global.
     *
     * @param int $id ID da entidade
     * @return ServiceResult
     */
    public function getById( int $id ): ServiceResult;

    /**
     * Lista entidades com filtros opcionais.
     *
     * @param array $filters Filtros para consulta
     * @return ServiceResult
     */
    public function list( array $filters = [] ): ServiceResult;

    /**
     * Cria nova entidade global.
     *
     * @param array $data Dados para criação
     * @return ServiceResult
     */
    public function create( array $data ): ServiceResult;

    /**
     * Atualiza entidade por ID global.
     *
     * @param int $id ID da entidade
     * @param array $data Dados para atualização
     * @return ServiceResult
     */
    public function update( int $id, array $data ): ServiceResult;

    /**
     * Deleta entidade por ID global.
     *
     * @param int $id ID da entidade
     * @return ServiceResult
     */
    public function delete( int $id ): ServiceResult;
}
