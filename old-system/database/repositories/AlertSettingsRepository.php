<?php
declare(strict_types=1);

namespace app\database\repositories;

use app\database\entitiesORM\AlertSettingsEntity;

/**
 * Repositório para gerenciar as configurações de alerta.
 *
 * Estende AbstractNoTenantRepository para ter todos os métodos básicos sem tenant
 * implementados automaticamente.
 *
 * @template T of AlertSettingsEntity
 * @extends AbstractNoTenantRepository<T>
 */
class AlertSettingsRepository extends AbstractNoTenantRepository
{
    // Métodos obrigatórios (findById, findAll, save, deleteById)
    // já estão implementados na classe abstrata AbstractNoTenantRepository.
    // Métodos específicos para AlertSettings podem ser adicionados aqui quando necessário.
}
