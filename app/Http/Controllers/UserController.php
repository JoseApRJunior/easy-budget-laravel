<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OperationStatus;
use App\Http\Controllers\Abstracts\Controller;
use App\Services\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para gerenciamento de usuários
 *
 * Este controller gerencia operações CRUD para usuários do sistema,
 * incluindo listagem, criação, edição, exclusão e funcionalidades específicas
 * como ativação de contas e confirmação de email.
 */
class UserController extends Controller
{

}
