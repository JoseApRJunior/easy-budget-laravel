<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OperationStatus;
use App\Services\Domain\PlanService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para gerenciamento de planos
 *
 * Este controller gerencia operações CRUD para planos do sistema,
 * incluindo listagem, criação, edição e exclusão de planos.
 */
class PlanController extends \Illuminate\Routing\Controller
{
    protected PlanService $planService;

    /**
     * Construtor com injeção de dependência
     */
    public function __construct( PlanService $planService )
    {
        $this->planService = $planService;
    }

    /**
     * Lista todos os planos
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function index( Request $request ): View|JsonResponse
    {
        try {
            $filters = $request->only( [ 'status', 'name' ] );
            $orderBy = $request->get( 'order_by', [ 'name' => 'asc' ] );
            $limit   = $request->get( 'limit', 15 );

            $result = $this->planService->list( $filters );

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $result,
                    'message' => 'Planos listados com sucesso.'
                ] );
            }

            return view( 'pages.plan.index', [
                'plans' => $result
            ] );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao listar planos.' );
        }
    }

    /**
     * Exibe formulário para criação de plano
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

            return view( 'pages.plan.create' );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao exibir formulário de criação.' );
        }
    }

    /**
     * Cria um novo plano
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function store( Request $request ): JsonResponse|RedirectResponse
    {
        try {
            // Validação dos dados
            $validatedData = $request->validate( [
                'name'        => 'required|string|max:255|unique:plans,name',
                'description' => 'nullable|string|max:1000',
                'price'       => 'required|numeric|min:0',
                'status'      => 'required|in:active,inactive,suspended',
                'features'    => 'nullable|array',
                'features.*'  => 'string|max:255'
            ] );

            $result = $this->planService->create( $validatedData );

            if ( !$result->isSuccess() ) {
                return $this->handleValidationError( $result, $request );
            }

            $message = 'Plano criado com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $result->getData(),
                    'message' => $message
                ], 201 );
            }

            return redirect()->route( 'pages.plan.index' )
                ->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao criar plano.' );
        }
    }

    /**
     * Exibe detalhes de um plano específico
     *
     * @param int $id
     * @param Request $request
     * @return View|JsonResponse
     */
    public function show( int $id, Request $request ): View|JsonResponse
    {
        try {
            $result = $this->planService->getById( $id );

            if ( !$result->isSuccess() ) {
                return $this->handleNotFound( $request, $result->getMessage() ?? 'Plano não encontrado.' );
            }

            $plan = $result->getData();

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $plan,
                    'message' => 'Plano encontrado com sucesso.'
                ] );
            }

            return view( 'pages.plan.show', [
                'plan' => $plan
            ] );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao exibir plano.' );
        }
    }

    /**
     * Exibe formulário para edição de plano
     *
     * @param int $id
     * @param Request $request
     * @return View|JsonResponse
     */
    public function edit( int $id, Request $request ): View|JsonResponse
    {
        try {
            $result = $this->planService->getById( $id );

            if ( !$result->isSuccess() ) {
                return $this->handleNotFound( $request, $result->getMessage() ?? 'Plano não encontrado.' );
            }

            $plan = $result->getData();

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $plan,
                    'message' => 'Formulário de edição disponível.'
                ] );
            }

            return view( 'pages.plan.edit', [
                'plan' => $plan
            ] );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao exibir formulário de edição.' );
        }
    }

    /**
     * Atualiza um plano específico
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse|RedirectResponse
     */
    public function update( Request $request, int $id ): JsonResponse|RedirectResponse
    {
        try {
            // Validação dos dados
            $validatedData = $request->validate( [
                'name'        => 'required|string|max:255|unique:plans,name,' . $id,
                'description' => 'nullable|string|max:1000',
                'price'       => 'required|numeric|min:0',
                'status'      => 'required|in:active,inactive,suspended',
                'features'    => 'nullable|array',
                'features.*'  => 'string|max:255'
            ] );

            $result = $this->planService->update( $id, $validatedData );

            if ( !$result->isSuccess() ) {
                return $this->handleValidationError( $result, $request );
            }

            $message = 'Plano atualizado com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $result->getData(),
                    'message' => $message
                ] );
            }

            return redirect()->route( 'pages.plan.index' )
                ->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao atualizar plano.' );
        }
    }

    /**
     * Remove um plano específico
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function destroy( int $id, Request $request ): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->planService->getById( $id );

            if ( !$result->isSuccess() ) {
                return $this->handleNotFound( $request, $result->getMessage() ?? 'Plano não encontrado.' );
            }

            $deleteResult = $this->planService->delete( $id );

            if ( !$deleteResult->isSuccess() ) {
                return $this->handleError( new Exception( $deleteResult->getMessage() ?? 'Falha ao deletar plano.' ), $request, 'Erro ao deletar plano.' );
            }

            $message = 'Plano deletado com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'message' => $message
                ] );
            }

            return redirect()->route( 'pages.plan.index' )
                ->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao deletar plano.' );
        }
    }

    /**
     * Ativa um plano
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function activate( int $id, Request $request ): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->planService->getById( $id );

            if ( !$result->isSuccess() ) {
                return $this->handleNotFound( $request, $result->getMessage() ?? 'Plano não encontrado.' );
            }

            $updateResult = $this->planService->update( $id, [ 'status' => 'active' ] );

            if ( !$updateResult->isSuccess() ) {
                return $this->handleValidationError( $updateResult, $request );
            }

            $message = 'Plano ativado com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $updateResult->getData(),
                    'message' => $message
                ] );
            }

            return redirect()->back()->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao ativar plano.' );
        }
    }

    /**
     * Desativa um plano
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function deactivate( int $id, Request $request ): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->planService->getById( $id );

            if ( !$result->isSuccess() ) {
                return $this->handleNotFound( $request, $result->getMessage() ?? 'Plano não encontrado.' );
            }

            $updateResult = $this->planService->update( $id, [ 'status' => 'inactive' ] );

            if ( !$updateResult->isSuccess() ) {
                return $this->handleValidationError( $updateResult, $request );
            }

            $message = 'Plano desativado com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $result->getData(),
                    'message' => $message
                ] );
            }

            return redirect()->back()->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao desativar plano.' );
        }
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
     * @return View|JsonResponse
     */
    private function handleNotFound( Request $request, string $message )
    {
        if ( $request->expectsJson() ) {
            return response()->json( [
                'success' => false,
                'message' => $message
            ], 404 );
        }

        // Return view with error instead of redirect
        $result = $this->planService->list();
        return view( 'plans.index', [
            'plans' => $result->isSuccess() ? $result->getData() : []
        ] )->with( 'error', $message );
    }

    /**
     * Trata erros genéricos
     *
     * @param Exception $e
     * @param Request $request
     * @param string $defaultMessage
     * @return View|JsonResponse
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

        // Return view with error instead of redirect
        try {
            $result = $this->planService->list();
            return view( 'plans.index', [
                'plans' => $result->isSuccess() ? $result->getData() : []
            ] )->with( 'error', $message );
        } catch ( Exception $fallbackError ) {
            // Fallback to simple error view
            return view( 'errors.generic', [
                'message' => $message,
                'error'   => $e
            ] );
        }
    }

}
