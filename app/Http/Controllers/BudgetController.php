<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OperationStatus;
use App\Services\BudgetService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para gerenciamento de orçamentos
 *
 * Este controller gerencia operações CRUD para orçamentos do sistema,
 * incluindo listagem, criação, edição, exclusão e funcionalidades específicas
 * como mudança de status, visualização detalhada e duplicação.
 */
class BudgetController extends \Illuminate\Routing\Controller
{
    protected BudgetService $budgetService;

    /**
     * Construtor com injeção de dependência
     */
    public function __construct( BudgetService $budgetService )
    {
        $this->budgetService = $budgetService;
    }

    /**
     * Lista todos os orçamentos do tenant
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function index( Request $request ): View|JsonResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );
            $filters  = $request->only( [ 'status', 'customer_id', 'category_id', 'date_from', 'date_to' ] );
            $orderBy  = $request->get( 'order_by', [ 'created_at' => 'desc' ] );
            $limit    = $request->get( 'limit', 15 );

            $result = $this->budgetService->listByTenantId( $tenantId, $filters );

            if ( !$result->isSuccess() ) {
                return $this->handleError( new Exception( $result->getMessage() ), $request, 'Erro ao listar orçamentos.' );
            }

            $budgets = $result->getData();

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $budgets,
                    'message' => 'Orçamentos listados com sucesso.'
                ] );
            }

            return view( 'budgets.index', [
                'budgets' => $budgets
            ] );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao listar orçamentos.' );
        }
    }

    /**
     * Exibe formulário para criação de orçamento
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function create( Request $request ): View|JsonResponse
    {
        try {
            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'message' => 'Formulário de criação disponível.'
                ] );
            }

            return view( 'budgets.create' );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao exibir formulário de criação.' );
        }
    }

    /**
     * Cria um novo orçamento
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function store( Request $request ): JsonResponse|RedirectResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );
            $userId   = $this->getUserId( $request );

            // Validação dos dados
            $validatedData = $request->validate( [
                'customer_id'         => 'required|exists:customers,id',
                'category_id'         => 'required|exists:categories,id',
                'title'               => 'nullable|string|max:255',
                'description'         => 'nullable|string|max:1000',
                'amount'              => 'required|numeric|min:0',
                'status'              => 'required|in:pending,approved,rejected,completed,finalized',
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
                'items'               => 'nullable|array',
                'items.*.description' => 'required|string|max:255',
                'items.*.quantity'    => 'required|numeric|min:0',
                'items.*.price'       => 'required|numeric|min:0'
            ] );

            $validatedData[ 'tenant_id' ] = $tenantId;

            $result = $this->budgetService->createBudgetWithCode( $validatedData, $tenantId, $userId );

            if ( !$result->isSuccess() ) {
                return $this->handleValidationError( $result, $request );
            }

            $message = 'Orçamento criado com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $result->getData(),
                    'message' => $message
                ], 201 );
            }

            return redirect()->route( 'budgets.index' )
                ->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao criar orçamento.' );
        }
    }

    /**
     * Exibe detalhes de um orçamento específico
     *
     * @param int $id
     * @param Request $request
     * @return View|JsonResponse
     */
    public function show( int $id, Request $request ): View|JsonResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );

            $result = $this->budgetService->getBudgetFullById( $id, $tenantId );

            if ( !$result->isSuccess() ) {
                return $this->handleNotFound( $request, $result->getMessage() ?? 'Orçamento não encontrado.' );
            }

            $budget = $result->getData();

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $budget,
                    'message' => 'Orçamento encontrado com sucesso.'
                ] );
            }

            return view( 'budgets.show', [
                'budget' => $budget
            ] );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao exibir orçamento.' );
        }
    }

    /**
     * Exibe formulário para edição de orçamento
     *
     * @param int $id
     * @param Request $request
     * @return View|JsonResponse
     */
    public function edit( int $id, Request $request ): View|JsonResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );

            $result = $this->budgetService->getByIdAndTenantId( $id, $tenantId );

            if ( !$result->isSuccess() ) {
                return $this->handleNotFound( $request, $result->getMessage() ?? 'Orçamento não encontrado.' );
            }

            $budget = $result->getData();

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $budget,
                    'message' => 'Formulário de edição disponível.'
                ] );
            }

            return view( 'budgets.edit', [
                'budget' => $budget
            ] );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao exibir formulário de edição.' );
        }
    }

    /**
     * Atualiza um orçamento específico
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse|RedirectResponse
     */
    public function update( Request $request, int $id ): JsonResponse|RedirectResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );

            // Validação dos dados
            $validatedData = $request->validate( [
                'customer_id'         => 'required|exists:customers,id',
                'category_id'         => 'required|exists:categories,id',
                'title'               => 'nullable|string|max:255',
                'description'         => 'nullable|string|max:1000',
                'amount'              => 'required|numeric|min:0',
                'status'              => 'required|in:pending,approved,rejected,completed,finalized',
                'discount_percentage' => 'nullable|numeric|min:0|max:100'
            ] );

            $result = $this->budgetService->updateByIdAndTenantId( $id, $validatedData, $tenantId );

            if ( !$result->isSuccess() ) {
                return $this->handleValidationError( $result, $request );
            }

            $message = 'Orçamento atualizado com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $result->getData(),
                    'message' => $message
                ] );
            }

            return redirect()->route( 'budgets.index' )
                ->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao atualizar orçamento.' );
        }
    }

    /**
     * Remove um orçamento específico
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function destroy( int $id, Request $request ): JsonResponse|RedirectResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );

            $result = $this->budgetService->deleteByIdAndTenantId( $id, $tenantId );

            if ( !$result->isSuccess() ) {
                return $this->handleError( new Exception( $result->getMessage() ?? 'Erro ao deletar orçamento.' ), $request, 'Erro ao deletar orçamento.' );
            }

            $message = 'Orçamento deletado com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'message' => $message
                ] );
            }

            return redirect()->route( 'budgets.index' )
                ->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao deletar orçamento.' );
        }
    }

    /**
     * Atualiza status do orçamento
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse|RedirectResponse
     */
    public function updateStatus( Request $request, int $id ): JsonResponse|RedirectResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );
            $userId   = $this->getUserId( $request );

            $validatedData = $request->validate( [
                'status' => 'required|in:pending,approved,rejected,completed,finalized',
                'reason' => 'nullable|string|max:500'
            ] );

            $result = $this->budgetService->updateBudgetStatus(
                $id,
                $validatedData[ 'status' ],
                $tenantId,
                $userId,
                $validatedData[ 'reason' ] ?? null
            );

            if ( !$result->isSuccess() ) {
                return $this->handleValidationError( $result, $request );
            }

            $message = 'Status do orçamento atualizado com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $result->getData(),
                    'message' => $message
                ] );
            }

            return redirect()->back()->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao atualizar status do orçamento.' );
        }
    }

    /**
     * Duplica um orçamento existente
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function duplicate( int $id, Request $request ): JsonResponse|RedirectResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );
            $userId   = $this->getUserId( $request );

            $overrides = $request->only( [ 'customer_id', 'category_id', 'title', 'description' ] );

            $result = $this->budgetService->duplicateBudget( $id, $tenantId, $userId, $overrides );

            if ( !$result->isSuccess() ) {
                return $this->handleError( new Exception( $result->getMessage() ?? 'Erro ao duplicar orçamento.' ), $request, 'Erro ao duplicar orçamento.' );
            }

            $message = 'Orçamento duplicado com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $result->getData(),
                    'message' => $message
                ], 201 );
            }

            return redirect()->route( 'budgets.index' )
                ->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao duplicar orçamento.' );
        }
    }

    /**
     * Exibe dados formatados para impressão do orçamento
     *
     * @param int $id
     * @param Request $request
     * @return View|JsonResponse
     */
    public function print( int $id, Request $request ): View|JsonResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );

            $result = $this->budgetService->getBudgetPrintData( $id, $tenantId );

            if ( !$result->isSuccess() ) {
                return $this->handleNotFound( $request, $result->getMessage() ?? 'Orçamento não encontrado.' );
            }

            $printData = $result->getData();

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $printData,
                    'message' => 'Dados para impressão obtidos com sucesso.'
                ] );
            }

            return view( 'budgets.print', [
                'printData' => $printData
            ] );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao obter dados para impressão.' );
        }
    }

    /**
     * Obtém o ID do tenant da requisição
     *
     * @param Request $request
     * @return int
     */
    private function getTenantId( Request $request ): int
    {
        return (int) $request->header( 'X-Tenant-ID', 1 );
    }

    /**
     * Obtém o ID do usuário da requisição
     *
     * @param Request $request
     * @return int
     */
    private function getUserId( Request $request ): int
    {
        return (int) $request->header( 'X-User-ID', 1 );
    }

    /**
     * Trata erros de validação
     *
     * @param mixed $result
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    private function handleValidationError( $result, Request $request )
    {
        if ( $request->expectsJson() ) {
            return response()->json( [
                'success' => false,
                'message' => $result->getMessage() ?? 'Erro de validação.',
                'errors'  => $result->getData() ?? []
            ], 422 );
        }

        return redirect()->back()
            ->withErrors( $result->getMessage() ?? 'Erro de validação.' )
            ->withInput();
    }

    /**
     * Trata recurso não encontrado
     *
     * @param Request $request
     * @param string $message
     * @return JsonResponse|RedirectResponse
     */
    private function handleNotFound( Request $request, string $message )
    {
        if ( $request->expectsJson() ) {
            return response()->json( [
                'success' => false,
                'message' => $message
            ], 404 );
        }

        return redirect()->route( 'budgets.index' )
            ->with( 'error', $message );
    }

    /**
     * Trata erros genéricos
     *
     * @param Exception $e
     * @param Request $request
     * @param string $defaultMessage
     * @return JsonResponse|RedirectResponse
     */
    private function handleError( Exception $e, Request $request, string $defaultMessage )
    {
        $message = $e->getMessage() ?: $defaultMessage;

        if ( $request->expectsJson() ) {
            return response()->json( [
                'success' => false,
                'message' => $message
            ], 500 );
        }

        return redirect()->back()
            ->with( 'error', $message );
    }

}
