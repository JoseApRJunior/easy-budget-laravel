<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Repositories\UserConfirmationTokenRepository;
use App\Repositories\UserRepository;
use App\Services\Domain\Abstracts\BaseTenantService;
use App\Support\ServiceResult;
use App\Traits\SlugGenerator;
use Exception;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

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
