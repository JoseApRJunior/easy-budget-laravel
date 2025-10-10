<?php

namespace App\Http\Controllers;

use App\Support\ServiceResult;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller base personalizado para o Easy Budget Laravel.
 *
 * Esta classe estende o Controller padrão do Laravel e adiciona funcionalidades
 * específicas para trabalhar com nossa arquitetura de serviços (Service Layer).
 * Fornece métodos auxiliares para tratamento consistente de responses,
 * integração com ServiceResult e operações comuns.
 *
 * @package App\Http\Controllers
 *
 * @example Uso básico:
 * ```php
 * class ProductController extends Controller
 * {
 *     public function index(): View
 *     {
 *         $result = $this->productService->list();
 *         return $this->view('products.index', $result);
 *     }
 * }
 * ```
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    // --------------------------------------------------------------------------
    // MÉTODOS AUXILIARES PARA SERVICERESULT
    // --------------------------------------------------------------------------

    /**
     * Trata resultado de serviço e retorna dados ou valor padrão.
     *
     * @param ServiceResult $result Resultado do serviço
     * @param mixed $default Valor padrão se operação falhou
     * @return mixed Dados do resultado ou valor padrão
     */
    protected function getServiceData( ServiceResult $result, mixed $default = [] ): mixed
    {
        return $result->isSuccess() ? $result->getData() : $default;
    }

    /**
     * Verifica se resultado do serviço foi bem-sucedido.
     *
     * @param ServiceResult $result Resultado do serviço
     * @return bool True se operação foi bem-sucedida
     */
    protected function isServiceSuccess( ServiceResult $result ): bool
    {
        return $result->isSuccess();
    }

    /**
     * Obtém mensagem de erro do resultado do serviço.
     *
     * @param ServiceResult $result Resultado do serviço
     * @param string $default Mensagem padrão se não houver erro específico
     * @return string Mensagem de erro
     */
    protected function getServiceErrorMessage( ServiceResult $result, string $default = 'Operação falhou' ): string
    {
        return $result->isSuccess() ? '' : ( $result->getMessage() ?: $default );
    }

    // --------------------------------------------------------------------------
    // MÉTODOS PARA TRATAMENTO DE VIEWS
    // --------------------------------------------------------------------------

    /**
     * Retorna view com dados de resultado de serviço.
     *
     * @param string $view Nome da view
     * @param ServiceResult $result Resultado do serviço
     * @param string $dataKey Chave para acessar dados na view (padrão: 'data')
     * @param array<string, mixed> $additionalData Dados adicionais para a view
     * @return View
     */
    protected function view( string $view, ServiceResult $result, string $dataKey = 'data', array $additionalData = [] ): View
    {
        $viewData = [ $dataKey => $this->getServiceData( $result ) ];

        if ( !empty( $additionalData ) ) {
            $viewData = array_merge( $viewData, $additionalData );
        }

        return view( $view, $viewData );
    }

    /**
     * Retorna view com paginação de resultado de serviço.
     *
     * @param string $view Nome da view
     * @param ServiceResult $result Resultado do serviço (deve conter paginator)
     * @param array<string, mixed> $additionalData Dados adicionais para a view
     * @return View
     */
    protected function paginatedView( string $view, ServiceResult $result, array $additionalData = [] ): View
    {
        $data = $this->getServiceData( $result, [] );

        // Se os dados forem um paginator, extrai informações úteis
        if ( method_exists( $data, 'items' ) ) {
            $viewData = [
                'data'         => $data->items(),
                'paginator'    => $data,
                'total'        => $data->total(),
                'perPage'      => $data->perPage(),
                'currentPage'  => $data->currentPage(),
                'lastPage'     => $data->lastPage(),
                'hasMorePages' => $data->hasMorePages(),
            ];
        } else {
            $viewData = [ 'data' => $data ];
        }

        if ( !empty( $additionalData ) ) {
            $viewData = array_merge( $viewData, $additionalData );
        }

        return view( $view, $viewData );
    }

    // --------------------------------------------------------------------------
    // MÉTODOS PARA REDIRECT COM MENSAGENS
    // --------------------------------------------------------------------------

    /**
     * Redirect com mensagem de sucesso.
     *
     * @param string $route Rota de destino
     * @param string $message Mensagem de sucesso
     * @param array<string, mixed> $parameters Parâmetros para a rota
     * @return RedirectResponse
     */
    protected function redirectSuccess( string $route, string $message = 'Operação realizada com sucesso', array $parameters = [] ): RedirectResponse
    {
        return redirect()->route( $route, $parameters )->with( 'success', $message );
    }

    /**
     * Redirect com mensagem de erro.
     *
     * @param string $route Rota de destino
     * @param string $message Mensagem de erro
     * @param array<string, mixed> $parameters Parâmetros para a rota
     * @return RedirectResponse
     */
    protected function redirectError( string $route, string $message = 'Erro na operação', array $parameters = [] ): RedirectResponse
    {
        return redirect()->route( $route, $parameters )->with( 'error', $message );
    }

    /**
     * Redirect com mensagem baseada em resultado de serviço.
     *
     * @param string $route Rota de destino
     * @param ServiceResult $result Resultado do serviço
     * @param string $successMessage Mensagem para sucesso (opcional)
     * @param array<string, mixed> $parameters Parâmetros para a rota
     * @return RedirectResponse
     */
    protected function redirectWithServiceResult(
        string $route,
        ServiceResult $result,
        string $successMessage = 'Operação realizada com sucesso',
        array $parameters = [],
    ): RedirectResponse {
        if ( $result->isSuccess() ) {
            return $this->redirectSuccess( $route, $successMessage, $parameters );
        }

        return $this->redirectError( $route, $this->getServiceErrorMessage( $result ), $parameters );
    }

    /**
     * Redirect back com mensagem baseada em resultado de serviço.
     *
     * @param ServiceResult $result Resultado do serviço
     * @param string $successMessage Mensagem para sucesso (opcional)
     * @return RedirectResponse
     */
    protected function redirectBackWithServiceResult(
        ServiceResult $result,
        string $successMessage = 'Operação realizada com sucesso',
    ): RedirectResponse {
        if ( $result->isSuccess() ) {
            return redirect()->back()->with( 'success', $successMessage );
        }

        return redirect()->back()
            ->with( 'error', $this->getServiceErrorMessage( $result ) )
            ->withInput();
    }

    // --------------------------------------------------------------------------
    // MÉTODOS PARA JSON RESPONSES
    // --------------------------------------------------------------------------

    /**
     * Retorna JSON response baseado em resultado de serviço.
     *
     * @param ServiceResult $result Resultado do serviço
     * @param int $successStatus Código HTTP para sucesso (padrão: 200)
     * @return JsonResponse
     */
    protected function jsonResponse( ServiceResult $result, int $successStatus = 200 ): JsonResponse
    {
        $statusCode = $result->isSuccess() ? $successStatus : $this->getErrorStatusCode( $result );

        return response()->json( [
            'success' => $result->isSuccess(),
            'message' => $result->getMessage(),
            'data'    => $result->getData(),
            'errors'  => $result->getErrors(),
        ], $statusCode );
    }

    /**
     * Retorna JSON response de sucesso.
     *
     * @param mixed $data Dados para retornar
     * @param string $message Mensagem de sucesso
     * @param int $statusCode Código HTTP (padrão: 200)
     * @return JsonResponse
     */
    protected function jsonSuccess( mixed $data = null, string $message = 'Operação realizada com sucesso', int $statusCode = 200 ): JsonResponse
    {
        return response()->json( [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $statusCode );
    }

    /**
     * Retorna JSON response de erro.
     *
     * @param string $message Mensagem de erro
     * @param mixed $errors Detalhes dos erros
     * @param int $statusCode Código HTTP (padrão: 400)
     * @return JsonResponse
     */
    protected function jsonError( string $message = 'Erro na operação', mixed $errors = null, int $statusCode = 400 ): JsonResponse
    {
        return response()->json( [
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $statusCode );
    }

    // --------------------------------------------------------------------------
    // MÉTODOS AUXILIARES INTERNOS
    // --------------------------------------------------------------------------

    /**
     * Obtém código HTTP apropriado baseado no tipo de erro do ServiceResult.
     *
     * @param ServiceResult $result Resultado do serviço
     * @return int Código HTTP apropriado
     */
    private function getErrorStatusCode( ServiceResult $result ): int
    {
        // Em uma implementação futura, podemos mapear diferentes tipos de erro
        // para códigos HTTP específicos (404 para NOT_FOUND, 409 para CONFLICT, etc.)

        return 400; // Bad Request como padrão
    }

    /**
     * Log de operações importantes para auditoria.
     *
     * @param string $action Ação realizada
     * @param array<string, mixed> $context Contexto da operação
     */
    protected function logOperation( string $action, array $context = [] ): void
    {
        Log::info( "Controller operation: {$action}", [
            'controller' => static::class,
            'context'    => $context,
            'ip'         => request()->ip(),
            'user_agent' => request()->userAgent(),
        ] );
    }

    /**
     * Validação comum para operações CRUD.
     *
     * @param Request $request Requisição a validar
     * @param array<string, string> $rules Regras de validação
     * @param array<string, string> $messages Mensagens customizadas (opcional)
     * @return array<string, mixed> Dados validados
     */
    protected function validateRequest( Request $request, array $rules, array $messages = [] ): array
    {
        return $request->validate( $rules, $messages );
    }

}
