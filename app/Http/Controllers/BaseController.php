<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ActivityService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseLaravelController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

/**
 * Classe base para todos os controladores da aplicação.
 * Fornece métodos auxiliares comuns para acesso a tenant, autenticação,
 * logging de atividades e padronização de respostas.
 *
 * @package App\Http\Controllers
 * @author IA
 */
abstract class BaseController extends BaseLaravelController
{
    /**
     * @var ActivityService|null
     */
    protected ?ActivityService $activityService = null;

    /**
     * Construtor da classe BaseController.
     * Inicializa o serviço de atividades se disponível.
     */
    public function __construct()
    {
        $this->activityService = app( ActivityService::class);
    }

    /**
     * Obtém o ID do tenant atual do usuário autenticado.
     *
     * @return int|null ID do tenant ou null se não autenticado
     */
    protected function tenantId(): ?int
    {
        $user = Auth::user();
        return $user?->tenant_id ?? null;
    }

    /**
     * Obtém o ID do usuário autenticado.
     *
     * @return int|null ID do usuário ou null se não autenticado
     */
    protected function userId(): ?int
    {
        return Auth::id();
    }

    /**
     * Registra uma atividade no sistema.
     *
     * @param string $action Ação realizada
     * @param string $entity Tipo de entidade afetada
     * @param int|null $entityId ID da entidade (opcional)
     * @param array $metadata Metadados adicionais (opcional)
     * @return void
     */
    protected function logActivity(
        string $action,
        string $entity,
        ?int $entityId = null,
        array $metadata = [],
    ): void {
        if ( $this->activityService ) {
            $tenantId = $this->tenantId();
            $userId   = $this->userId();

            $this->activityService->createActivity(
                action: $action,
                entity: $entity,
                entity_id: $entityId,
                user_id: $userId,
                tenant_id: $tenantId,
                metadata: $metadata,
            );
        }
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
     * Valida o acesso ao tenant atual.
     * Verifica se o usuário tem acesso ao tenant_id atual.
     *
     * @param int $requiredTenantId ID do tenant requerido
     * @return void
     * @throws Exception Se acesso negado
     */
    protected function validateTenantAccess( int $requiredTenantId ): void
    {
        $currentTenantId = $this->tenantId();

        if ( $currentTenantId !== $requiredTenantId ) {
            Log::warning( 'Tentativa de acesso indevido a tenant', [ 
                'user_id'         => $this->userId(),
                'current_tenant'  => $currentTenantId,
                'required_tenant' => $requiredTenantId
            ] );

            throw new Exception( 'Acesso negado: Tenant inválido.', 403 );
        }
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
     * Retorna resposta JSON padronizada de sucesso.
     *
     * @param mixed $data Dados da resposta
     * @param string|null $message Mensagem (opcional)
     * @param int $statusCode Código de status HTTP (padrão 200)
     * @return JsonResponse
     */
    protected function jsonSuccess(
        mixed $data = null,
        ?string $message = null,
        int $statusCode = 200,
    ): JsonResponse {
        return response()->json( [ 
            'success' => true,
            'message' => $message,
            'data'    => $data
        ], $statusCode );
    }

    /**
     * Retorna resposta JSON padronizada de erro.
     *
     * @param string $message Mensagem de erro
     * @param mixed $errors Erros de validação (opcional)
     * @param int $statusCode Código de status HTTP (padrão 400)
     * @return JsonResponse
     */
    protected function jsonError(
        string $message,
        mixed $errors = null,
        int $statusCode = 400,
    ): JsonResponse {
        return response()->json( [ 
            'success' => false,
            'message' => $message,
            'errors'  => $errors
        ], $statusCode );
    }

}