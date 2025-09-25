<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ActivityService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseLaravelController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Controlador base da aplicação.
 * 
 * Fornece funcionalidades comuns para todos os controladores,
 * incluindo helpers para autenticação, tenant, sanitização,
 * validação e respostas padronizadas baseadas no sistema antigo.
 */
abstract class BaseController extends BaseLaravelController
{
    use AuthorizesRequests, ValidatesRequests;

    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Obtém o ID do tenant do usuário autenticado.
     */
    protected function tenantId(): ?int
    {
        return Auth::user()?->tenant_id ?? session('tenant_id');
    }

    /**
     * Obtém o usuário autenticado.
     */
    protected function user()
    {
        return Auth::user();
    }

    /**
     * Obtém o ID do usuário autenticado.
     */
    protected function userId(): ?int
    {
        return Auth::id();
    }

    /**
     * Verifica se o usuário está autenticado.
     */
    protected function isAuthenticated(): bool
    {
        return Auth::check();
    }

    /**
     * Valida se o usuário tem acesso ao tenant especificado.
     */
    protected function validateTenantAccess(int $tenantId): bool
    {
        $userTenantId = $this->tenantId();
        
        if ($userTenantId !== $tenantId) {
            $this->logAccessDenied("Tentativa de acesso ao tenant {$tenantId}");
            return false;
        }
        
        return true;
    }

    /**
     * Valida se o modelo pertence ao tenant atual.
     */
    protected function validateModelTenantAccess(Model $model): bool
    {
        if (!$model->hasAttribute('tenant_id')) {
            return true; // Modelo não tem tenant_id
        }

        $modelTenantId = $model->getAttribute('tenant_id');
        $userTenantId = $this->tenantId();

        if ($modelTenantId !== $userTenantId) {
            $this->logAccessDenied("Tentativa de acesso ao modelo " . get_class($model) . " #{$model->getKey()}");
            return false;
        }

        return true;
    }

    /**
     * Sanitiza dados de entrada removendo tags HTML e caracteres perigosos.
     */
    protected function sanitizeInput(array $data): array
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                // Remove tags HTML e PHP
                $value = strip_tags($value);
                // Remove caracteres de controle
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
                // Trim espaços
                $value = trim($value);
            } elseif (is_array($value)) {
                $value = $this->sanitizeInput($value);
            }
            
            return $value;
        }, $data);
    }

    /**
     * Valida dados de entrada com regras especificadas.
     */
    protected function validateInput(array $data, array $rules, array $messages = []): array
    {
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new InvalidArgumentException(
                'Dados de entrada inválidos: ' . implode(', ', $validator->errors()->all())
            );
        }

        return $validator->validated();
    }

    /**
     * Valida método HTTP da requisição.
     */
    protected function validateRequestMethod(Request $request, array $allowedMethods): bool
    {
        $method = strtoupper($request->method());
        $allowedMethods = array_map('strtoupper', $allowedMethods);

        if (!in_array($method, $allowedMethods)) {
            $this->logError("Método HTTP {$method} não permitido");
            return false;
        }

        return true;
    }

    /**
     * Registra atividade do usuário.
     */
    protected function logActivity(
        string $action,
        ?Model $model = null,
        array $details = [],
        ?string $description = null
    ): void {
        $this->activityService->logActivity($action, $model, $details, $description);
    }

    /**
     * Registra acesso negado.
     */
    protected function logAccessDenied(string $resource, array $details = []): void
    {
        $this->activityService->logAccessDenied($resource, $details);
    }

    /**
     * Registra erro no sistema.
     */
    protected function logError(string $error, array $details = []): void
    {
        $this->activityService->logError($error, $details);
    }

    /**
     * Redireciona para sucesso com mensagem flash.
     *
     * @param string $message Mensagem de sucesso
     * @param string|null $route Rota de redirecionamento (opcional)
     * @param array $parameters Parâmetros da rota (opcional)
     * @return RedirectResponse
     */
    protected function successRedirect(
        string $message,
        ?string $route = null,
        array $parameters = [],
    ): RedirectResponse {
        Session::flash( 'success', $message );
        return $route
            ? redirect()->route( $route, $parameters )
            : redirect()->back();
    }

    /**
     * Redireciona para erro com mensagem flash.
     *
     * @param string $message Mensagem de erro
     * @param string|null $route Rota de redirecionamento (opcional)
     * @param array $parameters Parâmetros da rota (opcional)
     * @return RedirectResponse
     */
    protected function errorRedirect(
        string $message,
        ?string $route = null,
        array $parameters = [],
    ): RedirectResponse {
        Session::flash( 'error', $message );
        return $route
            ? redirect()->route( $route, $parameters )
            : redirect()->back();
    }



    /**
     * Processa o resultado de um ServiceResult e retorna resposta apropriada.
     *
     * @param ServiceResult $result Resultado do serviço
     * @param Request $request Requisição atual
     * @param string|null $successMessage Mensagem de sucesso (opcional)
     * @param string|null $errorMessage Mensagem de erro (opcional)
     * @return RedirectResponse|JsonResponse|View
     */
    protected function handleServiceResult(
        ServiceResult $result,
        Request $request,
        ?string $successMessage = null,
        ?string $errorMessage = null,
    ) {
        if ( $result->isSuccess() ) {
            $this->logActivity(
                action: $result->getAction() ?? 'operation_success',
                entity: $result->getEntity() ?? 'unknown',
                entityId: $result->getEntityId(),
                metadata: $result->getMetadata(),
            );

            if ( $request->expectsJson() ) {
                return response()->json( [ 
                    'success' => true,
                    'data'    => $result->getData(),
                    'message' => $successMessage ?? 'Operação realizada com sucesso.'
                ] );
            }

            return $this->successRedirect(
                message: $successMessage ?? 'Operação realizada com sucesso.',
                route: $result->getRedirectRoute(),
                parameters: $result->getRedirectParameters(),
            );
        }

        $this->logActivity(
            action: 'operation_error',
            entity: $result->getEntity() ?? 'unknown',
            entityId: $result->getEntityId(),
            metadata: array_merge( $result->getMetadata(), [ 'error' => $result->getError() ] ),
        );

        if ( $request->expectsJson() ) {
            return response()->json( [ 
                'success' => false,
                'error'   => $result->getError(),
                'message' => $errorMessage ?? 'Erro ao processar a operação.'
            ], 422 );
        }

        return $this->errorRedirect(
            message: $errorMessage ?? $result->getError(),
            route: $result->getRedirectRoute(),
            parameters: $result->getRedirectParameters(),
        );
    }

    /**
     * Renderiza uma view com dados comuns.
     *
     * @param string $view Nome da view
     * @param array $data Dados para a view
     * @return View
     */
    protected function renderView( string $view, array $data = [] ): View
    {
        $data[ 'tenantId' ] = $this->tenantId();
        $data[ 'userId' ]   = $this->userId();

        return view( $view, $data );
    }

    /**
     * Resposta de sucesso padronizada.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Operação realizada com sucesso',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ], $status);
    }

    /**
     * Resposta de erro padronizada.
     */
    protected function errorResponse(
        string $message = 'Erro interno do servidor',
        int $status = 500,
        mixed $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Resposta baseada em ServiceResult.
     */
    protected function serviceResponse(ServiceResult $result): JsonResponse
    {
        return response()->json($result->toResponse(), $result->getHttpCode());
    }

    /**
     * Resposta de validação com erros.
     */
    protected function validationErrorResponse(array $errors, string $message = 'Dados inválidos'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Resposta de não encontrado.
     */
    protected function notFoundResponse(string $message = 'Recurso não encontrado'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Resposta de não autorizado.
     */
    protected function unauthorizedResponse(string $message = 'Não autorizado'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Resposta de acesso negado.
     */
    protected function forbiddenResponse(string $message = 'Acesso negado'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

}
