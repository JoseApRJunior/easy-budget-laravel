<?php
declare(strict_types=1);

namespace App\Services\Contracts;

/**
 * Interface BaseServiceInterface
 *
 * Contrato de composição que agrega todas as responsabilidades básicas do sistema.
 *
 * IMPORTANTE: Para seguir o SOLID (Interface Segregation Principle),
 * prefira injetar interfaces menores (CrudServiceInterface, ValidationServiceInterface)
 * sempre que possível. Use esta interface de composição apenas para classes
 * que realmente precisam de todas as funcionalidades.
 *
 * @package App\Services\Contracts
 */
interface BaseServiceInterface extends
    CrudServiceInterface,
    CommandServiceInterface,
    ValidationServiceInterface,
    UtilityServiceInterface
{
    // A interface fica vazia. Ela apenas herda e agrupa todos os métodos das sub-interfaces.
}
