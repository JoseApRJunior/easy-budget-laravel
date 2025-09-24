<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Services\ActivityService;
use App\Support\ServiceResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * Controlador base para APIs.
 * 
 * Estende o BaseController com funcionalidades específicas para APIs,
 * incluindo tratamento de erros, validação e respostas JSON padronizadas
 * baseadas no padrão do sistema antigo.
 */
abstract class BaseApiController extends BaseController
{
    /**
     * Construtor do controlador API.
     */
    public function __construct(ActivityService $activityService)
    {
        parent::__construct($activityService);
        
        // Middleware padrão para APIs
        $this->middleware('auth:sanctum')->except($this->getPublicMethods());
        $this->middleware('tenant')->except($this->getPublicMethods());
    }

    /**
     * Métodos públicos que não requerem autenticação.
     */
    protected function getPublicMethods(): array
    {
        return [];
    }

    /**
     * Processa requisição com tratamento de erros padronizado.
     */
    protected function processRequest(callable $callback, Request $request): JsonResponse
    {
        try {
            // Sanitiza dados de entrada
            $sanitizedData = $this->sanitizeInput($request->all());
            $request->merge($sanitizedData);

            // Executa callback
            $result = $callback($request);

            // Se retornou ServiceResult, converte para resposta
            if ($result instanceof ServiceResult) {
                return $this->serviceResponse($result);
            }

            // Se retornou JsonResponse, retorna diretamente
            if ($result instanceof JsonResponse) {
                return $result;
            }

            // Caso contrário, assume sucesso com dados
            return $this->successResponse($result);

        } catch (ValidationException $e) {
            $this->logError('Erro de validação', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            
            return $this->validationErrorResponse(
                $e->errors(),
                'Dados de entrada inválidos'
            );

        } catch (Exception $e) {
            $this->logError('Erro na API', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->handleException($e);
        }
    }

    /**
     * Trata exceções específicas.
     */
    protected function handleException(Exception $e): JsonResponse
    {
        return match (get_class($e)) {
            'Illuminate\Database\Eloquent\ModelNotFoundException' => 
                $this->notFoundResponse('Recurso não encontrado'),
            
            'Illuminate\Auth\AuthenticationException' => 
                $this->unauthorizedResponse('Usuário não autenticado'),
            
            'Illuminate\Auth\Access\AuthorizationException' => 
                $this->forbiddenResponse('Acesso negado'),
            
            'InvalidArgumentException' => 
                $this->errorResponse($e->getMessage(), 400),
            
            default => $this->errorResponse(
                app()->environment('production') 
                    ? 'Erro interno do servidor' 
                    : $e->getMessage(),
                500
            )
        };
    }

    /**
     * Converte ServiceResult em resposta JSON.
     */
    protected function serviceResponse(ServiceResult $result): JsonResponse
    {
        return response()->json($result->toResponse(), $result->getHttpCode());
    }

    /**
     * Valida dados com regras e retorna dados validados.
     */
    protected function validateApiInput(Request $request, array $rules, array $messages = []): array
    {
        return $this->validateInput($request->all(), $rules, $messages);
    }

    /**
     * Cria resposta de sucesso para operação CRUD.
     */
    protected function crudSuccessResponse(
        string $operation,
        Model $model,
        ?string $customMessage = null
    ): JsonResponse {
        $messages = [
            'created' => 'Registro criado com sucesso',
            'updated' => 'Registro atualizado com sucesso',
            'deleted' => 'Registro excluído com sucesso',
            'retrieved' => 'Registro recuperado com sucesso',
        ];

        $message = $customMessage ?? $messages[$operation] ?? 'Operação realizada com sucesso';

        // Log da atividade
        $this->logActivity($operation, $model);

        return $this->successResponse($model, $message);
    }

    /**
     * Cria resposta de sucesso para listagem.
     */
    protected function listSuccessResponse(
        $data,
        ?string $message = null,
        array $metadata = []
    ): JsonResponse {
        $response = [
            'data' => $data,
        ];

        if (!empty($metadata)) {
            $response['metadata'] = $metadata;
        }

        return $this->successResponse(
            $response,
            $message ?? 'Listagem recuperada com sucesso'
        );
    }

    /**
     * Cria resposta paginada.
     */
    protected function paginatedResponse($paginator, ?string $message = null): JsonResponse
    {
        $metadata = [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ]
        ];

        return $this->listSuccessResponse(
            $paginator->items(),
            $message ?? 'Dados paginados recuperados com sucesso',
            $metadata
        );
    }

    /**
     * Valida acesso ao recurso baseado no tenant.
     */
    protected function validateResourceAccess(Model $model): bool
    {
        if (!$this->validateModelTenantAccess($model)) {
            return false;
        }

        return true;
    }

    /**
     * Aplica filtros de busca baseados na requisição.
     */
    protected function applySearchFilters($query, Request $request, array $searchableFields = []): void
    {
        // Filtro de busca geral
        if ($request->has('search') && !empty($searchableFields)) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchTerm, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                }
            });
        }

        // Filtros específicos
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'filter_') && !empty($value)) {
                $field = str_replace('filter_', '', $key);
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
        }

        // Ordenação
        if ($request->has('sort_by')) {
            $sortBy = $request->get('sort_by');
            $sortDirection = $request->get('sort_direction', 'asc');
            $query->orderBy($sortBy, $sortDirection);
        }
    }

    /**
     * Resposta de ServiceResult com logging automático.
     */
    protected function serviceResponseWithLogging(
        ServiceResult $result,
        ?string $successAction = null,
        ?Model $model = null
    ): JsonResponse {
        if ($result->isSuccess() && $successAction && $model) {
            $this->logActivity($successAction, $model);
        } elseif ($result->isError()) {
            $this->logError('Erro no serviço: ' . $result->getMessage());
        }

        return $this->serviceResponse($result);
    }

    /**
     * Valida se a requisição contém os campos obrigatórios.
     */
    protected function validateRequiredFields(Request $request, array $requiredFields): bool
    {
        foreach ($requiredFields as $field) {
            if (!$request->has($field) || empty($request->get($field))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extrai apenas os campos permitidos da requisição.
     */
    protected function extractAllowedFields(Request $request, array $allowedFields): array
    {
        return $request->only($allowedFields);
    }

    /**
     * Resposta de erro de validação.
     */
    protected function validationErrorResponse(
        array $errors,
        string $message = 'Dados inválidos'
    ): JsonResponse {
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