<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\BudgetFormRequest;
use App\Http\Requests\BudgetChangeStatusFormRequest;
use App\Http\Requests\BudgetBulkUpdateStatusFormRequest;
use App\Http\Requests\BudgetReportFormRequest;
use App\Services\BudgetService;
use App\Services\ActivityService;
use App\Services\PdfService;
use App\Services\NotificationService;
use App\Support\ServiceResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class BudgetController extends BaseApiController
{
    public function __construct(
        private readonly BudgetService $budgetService,
        private readonly PdfService $pdfService,
        private readonly NotificationService $notificationService,
        ActivityService $activityService,
    ) {
        parent::__construct($activityService);
    }

    /**
     * Lista orçamentos com filtros e paginação.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->validateRequestMethod($request, 'GET');
            
            $tenantId = $this->tenantId();
            $userId = Auth::id();

            // Sanitizar e validar filtros
            $filters = $this->sanitizeInput($request->only([
                'status', 'customer_id', 'category_id', 'user_id',
                'date_from', 'date_to', 'amount_min', 'amount_max',
                'search', 'sort_by', 'sort_order'
            ]));

            // Validar filtros específicos
            $this->validateInput($filters, [
                'status' => 'sometimes|array',
                'status.*' => 'string|in:pending,approved,rejected,completed,finalized',
                'customer_id' => 'sometimes|integer|exists:customers,id',
                'category_id' => 'sometimes|integer|exists:categories,id',
                'user_id' => 'sometimes|integer|exists:users,id',
                'date_from' => 'sometimes|date',
                'date_to' => 'sometimes|date|after_or_equal:date_from',
                'amount_min' => 'sometimes|numeric|min:0',
                'amount_max' => 'sometimes|numeric|gte:amount_min',
                'search' => 'sometimes|string|max:255',
                'sort_by' => 'sometimes|string|in:created_at,updated_at,total,code,title',
                'sort_order' => 'sometimes|string|in:asc,desc'
            ]);

            $perPage = min((int) $request->get('per_page', 15), 100);
            
            $result = $this->budgetService->getPaginatedBudgets($tenantId, $filters, $perPage);

            if (!$result->isSuccess()) {
                return $this->errorResponse($result);
            }

            // Log da atividade
            $this->logActivity('budget_list_accessed', 'budget', null, 
                "Usuário acessou lista de orçamentos", ['filters' => $filters]);

            return $this->paginatedResponse($result->data, 'Orçamentos listados com sucesso');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Throwable $e) {
            Log::error('Erro ao listar orçamentos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId()
            ]);
            return $this->internalErrorResponse('Erro interno ao listar orçamentos');
        }
    }

    /**
     * Exibe detalhes de um orçamento específico.
     */
    public function show(Request $request, string $code): JsonResponse
    {
        try {
            $this->validateRequestMethod($request, 'GET');
            
            $tenantId = $this->tenantId();
            
            $result = $this->budgetService->getBudgetFullById($code, $tenantId);

            if (!$result->isSuccess()) {
                return $this->errorResponse($result);
            }

            // Log da atividade
            $this->logActivity('budget_viewed', 'budget', $result->data->id, 
                "Orçamento {$code} visualizado");

            return $this->successResponse($result->data, 'Orçamento encontrado');

        } catch (Throwable $e) {
            Log::error('Erro ao buscar orçamento', [
                'code' => $code,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId()
            ]);
            return $this->internalErrorResponse('Erro interno ao buscar orçamento');
        }
    }

    /**
     * Cria um novo orçamento.
     */
    public function store(BudgetFormRequest $request): JsonResponse
    {
        try {
            $this->validateRequestMethod($request, 'POST');
            
            $tenantId = $this->tenantId();
            $userId = Auth::id();

            $validatedData = $request->validated();
            $validatedData['tenant_id'] = $tenantId;
            $validatedData['user_id'] = $userId;

            DB::beginTransaction();

            $result = $this->budgetService->createBudgetWithCode($validatedData);

            if (!$result->isSuccess()) {
                DB::rollBack();
                return $this->errorResponse($result);
            }

            DB::commit();

            // Log da atividade
            $this->logActivity('budget_created', 'budget', $result->data->id, 
                "Orçamento {$result->data->code} criado com sucesso");

            return $this->createdResponse($result->data, 'Orçamento criado com sucesso');

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->validationErrorResponse($e);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao criar orçamento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId()
            ]);
            return $this->internalErrorResponse('Erro interno ao criar orçamento');
        }
    }

    /**
     * Atualiza um orçamento existente.
     */
    public function update(BudgetFormRequest $request, string $code): JsonResponse
    {
        try {
            $this->validateRequestMethod($request, ['PUT', 'PATCH']);
            
            $tenantId = $this->tenantId();
            $userId = Auth::id();

            $validatedData = $request->validated();

            DB::beginTransaction();

            $result = $this->budgetService->updateBudgetByCode($code, $validatedData, $tenantId, $userId);

            if (!$result->isSuccess()) {
                DB::rollBack();
                return $this->errorResponse($result);
            }

            DB::commit();

            // Log da atividade
            $this->logActivity('budget_updated', 'budget', $result->data->id, 
                "Orçamento {$code} atualizado");

            return $this->successResponse($result->data, 'Orçamento atualizado com sucesso');

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->validationErrorResponse($e);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar orçamento', [
                'code' => $code,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId()
            ]);
            return $this->internalErrorResponse('Erro interno ao atualizar orçamento');
        }
    }

    /**
     * Altera o status de um orçamento.
     */
    public function changeStatus(BudgetChangeStatusFormRequest $request, string $code): JsonResponse
    {
        try {
            $this->validateRequestMethod($request, 'PATCH');
            
            $tenantId = $this->tenantId();
            $userId = Auth::id();

            $validatedData = $request->validated();

            DB::beginTransaction();

            $result = $this->budgetService->updateBudgetStatus(
                $code, 
                $validatedData['status'], 
                $tenantId, 
                $userId,
                $validatedData['comments'] ?? null
            );

            if (!$result->isSuccess()) {
                DB::rollBack();
                return $this->errorResponse($result);
            }

            DB::commit();

            // Log da atividade
            $this->logActivity('budget_status_changed', 'budget', $result->data->id, 
                "Status do orçamento {$code} alterado para {$validatedData['status']}");

            return $this->successResponse($result->data, 'Status alterado com sucesso');

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->validationErrorResponse($e);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao alterar status do orçamento', [
                'code' => $code,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId()
            ]);
            return $this->internalErrorResponse('Erro interno ao alterar status');
        }
    }

    /**
     * Duplica um orçamento existente.
     */
    public function duplicate(Request $request, string $code): JsonResponse
    {
        try {
            $this->validateRequestMethod($request, 'POST');
            
            $tenantId = $this->tenantId();
            $userId = Auth::id();

            DB::beginTransaction();

            $result = $this->budgetService->duplicateBudget($code, $tenantId, $userId);

            if (!$result->isSuccess()) {
                DB::rollBack();
                return $this->errorResponse($result);
            }

            DB::commit();

            // Log da atividade
            $this->logActivity('budget_duplicated', 'budget', $result->data->id, 
                "Orçamento {$code} duplicado para {$result->data->code}");

            return $this->createdResponse($result->data, 'Orçamento duplicado com sucesso');

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao duplicar orçamento', [
                'code' => $code,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId()
            ]);
            return $this->internalErrorResponse('Erro interno ao duplicar orçamento');
        }
    }

    /**
     * Remove um orçamento.
     */
    public function destroy(Request $request, string $code): JsonResponse
    {
        try {
            $this->validateRequestMethod($request, 'DELETE');
            
            $tenantId = $this->tenantId();
            $userId = Auth::id();

            DB::beginTransaction();

            $result = $this->budgetService->deleteBudgetByCode($code, $tenantId, $userId);

            if (!$result->isSuccess()) {
                DB::rollBack();
                return $this->errorResponse($result);
            }

            DB::commit();

            // Log da atividade
            $this->logActivity('budget_deleted', 'budget', null, 
                "Orçamento {$code} removido");

            return $this->successResponse(null, 'Orçamento removido com sucesso');

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao remover orçamento', [
                'code' => $code,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId()
            ]);
            return $this->internalErrorResponse('Erro interno ao remover orçamento');
        }
    }

    /**
     * Gera PDF do orçamento.
     */
    public function generatePdf(Request $request, string $code): JsonResponse
    {
        try {
            $this->validateRequestMethod($request, 'POST');
            
            $tenantId = $this->tenantId();
            
            $result = $this->budgetService->getBudgetFullById($code, $tenantId);

            if (!$result->isSuccess()) {
                return $this->errorResponse($result);
            }

            $pdfResult = $this->pdfService->generateBudgetPdf($result->data);

            if (!$pdfResult->isSuccess()) {
                return $this->errorResponse($pdfResult);
            }

            // Log da atividade
            $this->logActivity('budget_pdf_generated', 'budget', $result->data->id, 
                "PDF do orçamento {$code} gerado");

            return $this->successResponse([
                'pdf_url' => $pdfResult->data['url'],
                'pdf_path' => $pdfResult->data['path']
            ], 'PDF gerado com sucesso');

        } catch (Throwable $e) {
            Log::error('Erro ao gerar PDF do orçamento', [
                'code' => $code,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId()
            ]);
            return $this->internalErrorResponse('Erro interno ao gerar PDF');
        }
    }

    /**
     * Relatório de orçamentos por período.
     */
    public function report(BudgetReportFormRequest $request): JsonResponse
    {
        try {
            $this->validateRequestMethod($request, 'GET');
            
            $tenantId = $this->tenantId();

            // Os dados já foram validados pelo Form Request
            $period = $request->get('period');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $format = $request->get('format', 'json');
            $filters = $request->only([
                'status', 'customer_id', 'category_id', 'user_id',
                'amount_min', 'amount_max', 'group_by',
                'include_items', 'include_totals'
            ]);

            $result = $this->budgetService->getBudgetReport($tenantId, $dateFrom, $dateTo, $format, $filters);

            if (!$result->isSuccess()) {
                return $this->errorResponse($result);
            }

            // Log da atividade
            $this->logActivity('budget_report_generated', 'budget', null, 
                "Relatório de orçamentos gerado para período {$dateFrom} a {$dateTo}");

            return $this->successResponse($result->data, 'Relatório gerado com sucesso');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Throwable $e) {
            Log::error('Erro ao gerar relatório de orçamentos', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId()
            ]);
            return $this->internalErrorResponse('Erro interno ao gerar relatório');
        }
    }

    /**
     * Estatísticas de conversão de orçamentos.
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $this->validateRequestMethod($request, 'GET');
            
            $tenantId = $this->tenantId();

            $period = $request->get('period', '30 days');
            
            // Validar período
            $this->validateInput(['period' => $period], [
                'period' => 'string|in:7 days,30 days,90 days,1 year'
            ]);

            $result = $this->budgetService->getConversionStats($tenantId, $period);

            if (!$result->isSuccess()) {
                return $this->errorResponse($result);
            }

            return $this->successResponse($result->data, 'Estatísticas obtidas com sucesso');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Throwable $e) {
            Log::error('Erro ao obter estatísticas de orçamentos', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId()
            ]);
            return $this->internalErrorResponse('Erro interno ao obter estatísticas');
        }
    }

    /**
     * Busca orçamentos próximos ao vencimento.
     */
    public function nearExpiration(Request $request): JsonResponse
    {
        try {
            $this->validateRequestMethod($request, 'GET');
            
            $tenantId = $this->tenantId();
            $days = min((int) $request->get('days', 7), 30);

            $result = $this->budgetService->getBudgetsNearExpiration($tenantId, $days);

            if (!$result->isSuccess()) {
                return $this->errorResponse($result);
            }

            return $this->successResponse($result->data, 'Orçamentos próximos ao vencimento');

        } catch (Throwable $e) {
            Log::error('Erro ao buscar orçamentos próximos ao vencimento', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId()
            ]);
            return $this->internalErrorResponse('Erro interno ao buscar orçamentos');
        }
    }

    /**
     * Atualização em lote de status de orçamentos.
     */
    public function bulkUpdateStatus(BudgetBulkUpdateStatusFormRequest $request): JsonResponse
    {
        try {
            $this->validateRequestMethod($request, 'PATCH');
            
            $tenantId = $this->tenantId();
            $userId = Auth::id();

            // Os dados já foram validados pelo Form Request
            $budgetIds = $request->get('budget_ids');
            $status = $request->get('status');
            $comment = $request->get('comment');
            $notifyCustomers = $request->get('notify_customers', false);

            DB::beginTransaction();

            $result = $this->budgetService->bulkUpdateStatus($budgetIds, $status, $tenantId, $userId, $comment, $notifyCustomers);

            if (!$result->isSuccess()) {
                DB::rollBack();
                return $this->errorResponse($result);
            }

            DB::commit();

            // Log da atividade
            $this->logActivity('budget_bulk_status_update', 'budget', null, 
                "Status de " . count($budgetIds) . " orçamentos alterado para {$status}",
                ['budget_ids' => $budgetIds, 'comment' => $comment]);

            return $this->successResponse([
                'updated_count' => $result->data,
                'status' => $status
            ], 'Status dos orçamentos atualizado com sucesso');

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->validationErrorResponse($e);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Erro na atualização em lote de status', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId()
            ]);
            return $this->internalErrorResponse('Erro interno na atualização em lote');
        }
    }
}

