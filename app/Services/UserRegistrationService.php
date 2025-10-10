<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Repositories\TenantRepository;
use App\Repositories\UserConfirmationTokenRepository;
use App\Repositories\UserRepository;
use App\Services\Abstracts\BaseTenantService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Serviço para registro completo de usuários no sistema Easy Budget.
 *
 * Este serviço migra a lógica do UserRegistrationService legacy para a nova arquitetura,
 * mantendo compatibilidade com o processo existente enquanto implementa as melhores
 * práticas do Laravel e do padrão de serviços do projeto.
 *
 * Funcionalidades principais:
 * - Registro completo de usuários com tenant isolation
 * - Integração com UserService, TenantService e MailerService
 * - Validação usando Laravel validation
 * - Compatibilidade com processo legacy
 * - Criação automática de tenants para novos usuários
 * - Envio de e-mails de confirmação
 * - Gerenciamento de tokens de confirmação
 * - Recuperação de senha
 * - Confirmação de conta
 *
 * O serviço é registrado como singleton no container DI e pode ser injetado
 * em controllers e outros serviços conforme necessário.
 */
class UserRegistrationService extends AbstractBaseService
{

}
