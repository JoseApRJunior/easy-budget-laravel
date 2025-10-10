<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Domain\PlanService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller para gerenciamento de planos
 *
 * Este controller implementa operações CRUD completas para planos do sistema,
 * seguindo a arquitetura Controller → Service → Repository → Model.
 *
 * Funcionalidades implementadas:
 * - Listagem paginada com filtros e ordenação
 * - Criação de novos planos com validação robusta
 * - Visualização detalhada de planos específicos
 * - Edição de planos existentes
 * - Exclusão segura com verificações
 * - Ativação e desativação de planos
 * - Suporte completo a APIs JSON e interface web
 *
 * @version 1.0.0
 * @author Sistema Easy Budget Laravel
 */
class PlanController extends Controller
{
    protected PlanService $planService;

    /**
     * Construtor com injeção de dependência
     *
     * Inicializa o controller com todas as dependências necessárias,
     * seguindo o princípio de Inversão de Dependência (DIP).
     *
     * @param PlanService $planService Camada de serviço para lógica de negócio
     */
    public function __construct( PlanService $planService )
    {
        $this->planService = $planService;
    }

    /**
     * Lista todos os planos
     *
     * Exibe uma lista paginada de planos com opções de filtro e ordenação.
     * Suporta respostas JSON para APIs e views Blade para interface web.
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
                    'data'    => $result->getData(),
                    'message' => 'Planos listados com sucesso.'
                ] );
            }

            return view( 'pages.plan.index', [
                'plans' => $result->isSuccess() ? $result->getData() : []
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
     * Processa a criação de um novo plano com validação completa e tratamento de erro.
     * Implementa o padrão de resposta dupla (JSON para APIs, redirect para Web).
     *
     * Regras de validação aplicadas:
     * - Nome único no sistema
     * - Preço positivo obrigatório
     * - Status válido (active, inactive, suspended)
     * - Features como array opcional de strings
     *
     * @param Request $request Dados validados do formulário/API
     * @return JsonResponse|RedirectResponse
     */
    public function store( Request $request ): JsonResponse|RedirectResponse
    {
        try {
            // Validação robusta dos dados de entrada
            $validatedData = $request->validate( [
                'name'        => 'required|string|max:255|unique:plans,name',
                'description' => 'nullable|string|max:1000',
                'price'       => 'required|numeric|min:0|max:999999.99',
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
            $result = $this->planService->findById( $id );

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
            $result = $this->planService->findById( $id );

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
     * Processa a atualização de um plano existente com validação completa.
     * Implementa verificação de existência e tratamento robusto de erros.
     *
     * Regras de validação aplicadas:
     * - Nome único no sistema (exceto para o próprio registro)
     * - Preço positivo obrigatório com limite máximo
     * - Status válido dentro das opções permitidas
     * - Features como array opcional de strings
     *
     * @param Request $request Dados validados do formulário/API
     * @param int $id ID único do plano a ser atualizado
     * @return JsonResponse|RedirectResponse
     */
    public function update( Request $request, int $id ): JsonResponse|RedirectResponse
    {
        try {
            // Validação robusta dos dados de entrada
            $validatedData = $request->validate( [
                'name'        => 'required|string|max:255|unique:plans,name,' . $id,
                'description' => 'nullable|string|max:1000',
                'price'       => 'required|numeric|min:0|max:999999.99',
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
            $result = $this->planService->findById( $id );

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
            $result = $this->planService->findById( $id );

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
            $result = $this->planService->findById( $id );

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
     * Trata erros de validação seguindo padrões do sistema
     *
     * Centraliza o tratamento de erros de validação, garantindo consistência
     * entre respostas JSON (API) e redirecionamentos (Web).
     *
     * @param mixed $result Objeto ServiceResult com dados de erro
     * @param Request $request Requisição HTTP para determinar formato de resposta
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
            ->withErrors( $result->getData() ?? [ $result->getMessage() ?? 'Erro de validação.' ] )
            ->withInput();
    }

    /**
     * Trata recurso não encontrado seguindo padrões do sistema
     *
     * Implementa tratamento consistente para recursos não encontrados,
     * retornando lista de planos com mensagem de erro apropriada.
     *
     * @param Request $request Requisição HTTP para determinar formato de resposta
     * @param string $message Mensagem descritiva do erro
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

        // Retorna à listagem com mensagem de erro
        try {
            $result = $this->planService->list();
            return view( 'pages.plan.index', [
                'plans' => $result->isSuccess() ? $result->getData() : []
            ] )->with( 'error', $message );
        } catch ( Exception $e ) {
            // Fallback para view de erro genérica
            return view( 'errors.generic', [
                'message' => $message,
                'error'   => null
            ] );
        }
    }

    /**
     * Trata erros genéricos seguindo padrões do sistema
     *
     * Implementa tratamento robusto de erros com fallback seguro,
     * garantindo que o usuário sempre receba uma resposta adequada.
     *
     * @param Exception $e Exceção capturada durante execução
     * @param Request $request Requisição HTTP para determinar formato de resposta
     * @param string $defaultMessage Mensagem padrão caso exceção não tenha mensagem
     * @return View|JsonResponse
     */
    private function handleError( Exception $e, Request $request, string $defaultMessage )
    {
        $message = $e->getMessage() ?: $defaultMessage;

        // Log detalhado do erro para auditoria
        Log::error( 'Erro no PlanController', [
            'action'  => debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 )[ 1 ][ 'function' ] ?? 'unknown',
            'message' => $message,
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString()
        ] );

        if ( $request->expectsJson() ) {
            return response()->json( [
                'success' => false,
                'message' => $message
            ], 500 );
        }

        // Tenta retornar à listagem com erro
        try {
            $result = $this->planService->list();
            return view( 'pages.plan.index', [
                'plans' => $result->isSuccess() ? $result->getData() : []
            ] )->with( 'error', $message );
        } catch ( Exception $fallbackError ) {
            // Fallback seguro para view de erro genérica
            return view( 'errors.generic', [
                'message' => $message,
                'error'   => config( 'app.debug' ) ? $e : null
            ] );
        }
    }

}
