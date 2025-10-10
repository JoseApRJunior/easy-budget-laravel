<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Services\Core\Abstracts\AbstractBaseService;

/**
 * Serviço para operações de usuário com tenant.
 *
 * Migra lógica legacy: criação com hash de senha, tokens de confirmação,
 * ativação de conta, gerenciamento de usuários. Usa Eloquent via repositórios.
 * Mantém compatibilidade API com métodos *ByTenantId.
 */
class UserService extends AbstractBaseService
{

}
