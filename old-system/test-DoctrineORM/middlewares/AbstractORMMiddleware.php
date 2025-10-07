<?php

/**
 * Middleware base abstrato para implementação ORM
 *
 * Esta classe fornece funcionalidades comuns para todos os middlewares que utilizam
 * o padrão ORM com Doctrine. Implementa o padrão Template Method para definir
 * um fluxo padrão de execução, permitindo que middlewares específicos implementem
 * apenas suas verificações particulares.
 *
 * Funcionalidades principais:
 * - Gerenciamento de sessões via SessionService
 * - Autenticação via AuthenticationService
 * - Atualização automática de última atividade
 * - Tratamento padronizado de erros
 * - Logging de execução
 * - Redirecionamentos padronizados
 */

namespace core\middlewares;

use app\database\servicesORM\AuthenticationService;
use app\database\servicesORM\SessionService;
use core\interfaces\MiddlewareInterface;
use core\library\AuthService;
use core\traits\MetricsCollectionTrait;
use Exception;
use http\Redirect;

/**
 * Classe base abstrata para middlewares ORM
 *
 * Implementa o padrão Template Method para definir um fluxo comum
 * de execução para todos os middlewares que utilizam ORM
 */
abstract class AbstractORMMiddleware implements MiddlewareInterface
{
    use MetricsCollectionTrait;

    protected SessionService $sessionService;
    protected AuthService    $authService;

    /**
     * Construtor do middleware base
     *
     * @param SessionService $sessionService Serviço para gerenciamento de sessões
     * @param AuthService $authService Serviço para autenticação
     */
    public function __construct(
        SessionService $sessionService,
        AuthService $authService,
    ) {
        $this->sessionService = $sessionService;
        $this->authService    = $authService;
    }

    /**
     * Executa o middleware seguindo o padrão Template Method
     *
     * Define o fluxo padrão de execução:
     * 1. Atualiza a última atividade da sessão
     * 2. Executa a verificação específica do middleware
     * 3. Trata erros e exceções
     * 4. Registra logs de execução
     *
     * @return Redirect|null Redirecionamento se necessário, null caso contrário
     */
    public function execute(): Redirect|null
    {
        error_log( "[DEBUG] AbstractORMMiddleware::execute() - INICIANDO para " . $this->getMiddlewareName() );

        // Inicia coleta de métricas
        $this->startMetricsCollection();

        try {
            error_log( "[DEBUG] Atualizando atividade da sessão..." );
            // Atualiza a última atividade da sessão se necessário
            $this->updateSessionActivity();

            error_log( "[DEBUG] Executando verificação específica..." );
            // Executa a verificação específica do middleware
            $result = $this->performCheck();

            error_log( "[DEBUG] Resultado da verificação: " . ( $result ? 'REDIRECT' : 'NULL (permite acesso)' ) );

            // Log da execução bem-sucedida
            $this->logExecution( true, $this->getMiddlewareName() );

            // Registra métricas de sucesso
            $this->endMetricsCollection( $this->getMiddlewareName() );

            error_log( "[DEBUG] AbstractORMMiddleware::execute() - FINALIZANDO" );
            return $result;

        } catch ( Exception $e ) {
            // Log do erro
            $this->logExecution( false, $this->getMiddlewareName(), $e->getMessage() );

            // Registra métricas de erro
            $this->endMetricsCollection( $this->getMiddlewareName(), 500 );

            // Trata o erro de forma padronizada
            return $this->handleError( $e );
        }
    }

    /**
     * Atualiza a última atividade da sessão
     *
     * Verifica se é necessário atualizar a sessão baseado na função
     * handleLastUpdateSession e atualiza via SessionService
     */
    protected function updateSessionActivity(): void
    {
        if ( handleLastUpdateSession( $this->getSessionKey() ) ) {
            // Obtém o token da sessão atual
            $sessionToken = $_SESSION[ 'session_token' ] ?? null;

            if ( $sessionToken ) {
                // Atualiza a última atividade via SessionService
                $this->sessionService->updateLastActivity( $sessionToken );
            }
        }
    }

    /**
     * Executa a verificação específica do middleware
     *
     * Método abstrato que deve ser implementado por cada middleware específico
     * para definir suas regras de verificação particulares
     *
     * @return Redirect|null Redirecionamento se a verificação falhar, null se passar
     */
    abstract protected function performCheck(): Redirect|null;

    /**
     * Retorna a chave de identificação do tipo de sessão
     *
     * Método abstrato que deve retornar uma string identificando o tipo
     * de middleware para uso em logs e controle de sessão
     *
     * @return string Chave identificadora do middleware (ex: 'admin', 'auth', 'provider')
     */
    abstract protected function getSessionKey(): string;

    /**
     * Retorna o nome do middleware para logs
     *
     * Por padrão, retorna a chave da sessão, mas pode ser sobrescrito
     * para fornecer nomes mais descritivos
     *
     * @return string Nome do middleware
     */
    protected function getMiddlewareName(): string
    {
        return $this->getSessionKey();
    }

    /**
     * Trata erros de forma padronizada
     *
     * Define como erros e exceções devem ser tratados.
     * Por padrão, redireciona para a página inicial em caso de erro.
     *
     * @param Exception $e Exceção capturada
     * @return Redirect Redirecionamento para página de erro
     */
    protected function handleError( Exception $e ): Redirect
    {
        // Remove dados da sessão em caso de erro crítico
        $this->clearSessionData();

        // Redireciona para a página inicial
        return new Redirect( '/' );
    }

    /**
     * Limpa dados específicos da sessão
     *
     * Remove dados relacionados ao middleware da sessão atual.
     * Pode ser sobrescrito por middlewares específicos para limpeza customizada.
     */
    protected function clearSessionData(): void
    {
        // Remove dados básicos da sessão
        unset( $_SESSION[ 'auth' ] );
        unset( $_SESSION[ 'user_id' ] );
        unset( $_SESSION[ 'session_token' ] );
    }

    /**
     * Registra logs de execução do middleware
     *
     * @param bool $success Se a execução foi bem-sucedida
     * @param string $middlewareName Nome do middleware
     * @param string|null $errorMessage Mensagem de erro, se houver
     */
    protected function logExecution( bool $success, string $middlewareName, ?string $errorMessage = null ): void
    {
        $logLevel     = $success ? 'INFO' : 'ERROR';
        $status       = $success ? 'SUCCESS' : 'FAILED';
        $userId       = $_SESSION[ 'user_id' ] ?? 'anonymous';
        $sessionToken = $_SESSION[ 'session_token' ] ?? 'no-token';

        $logMessage = sprintf(
            '[%s] Middleware %s execution %s - User: %s, Session: %s',
            $logLevel,
            $middlewareName,
            $status,
            $userId,
            substr( $sessionToken, 0, 8 ) . '...' // Apenas primeiros 8 caracteres por segurança
        );

        if ( $errorMessage ) {
            $logMessage .= ' - Error: ' . $errorMessage;
        }

        // Log usando o sistema de logs do projeto
        error_log( $logMessage );
    }

    /**
     * Cria um redirecionamento padronizado
     *
     * @param string $url URL de destino
     * @param string|null $message Mensagem opcional para exibir
     * @return Redirect Objeto de redirecionamento
     */
    protected function createRedirect( string $url, ?string $message = null ): Redirect
    {
        if ( $message ) {
            $_SESSION[ 'flash_message' ] = $message;
        }

        return new Redirect( $url );
    }

    /**
     * Verifica se o usuário atual é administrador
     *
     * Método auxiliar para verificações de privilégios administrativos
     *
     * @return bool True se for administrador, false caso contrário
     */
    protected function isAdmin(): bool
    {
        return $this->authService->isAdmin();
    }

    /**
     * Verifica se o usuário está autenticado
     *
     * Método auxiliar para verificações básicas de autenticação
     *
     * @return bool True se estiver autenticado, false caso contrário
     */
    protected function isAuthenticated(): bool
    {
        return $this->authService->isAuthenticated();
    }

    /**
     * Obtém o ID do usuário atual da sessão
     *
     * @return int|null ID do usuário ou null se não autenticado
     */
    protected function getCurrentUserId(): ?int
    {
        return $_SESSION[ 'user_id' ] ?? null;
    }

    /**
     * Obtém o token da sessão atual
     *
     * @return string|null Token da sessão ou null se não disponível
     */
    protected function getCurrentSessionToken(): ?string
    {
        return $_SESSION[ 'session_token' ] ?? null;
    }

    /**
     * Verifica se a sessão expirou
     *
     * Utiliza a função handleSessionTimeout() existente do projeto
     *
     * @return Redirect|null Redirecionamento se expirou, null caso contrário
     */
    protected function checkSessionTimeout(): Redirect|null
    {
        return handleSessionTimeout();
    }

    /**
     * Valida a sessão atual via SessionService
     *
     * @return bool True se a sessão é válida, false caso contrário
     */
    protected function validateCurrentSession(): bool
    {
        $sessionToken = $this->getCurrentSessionToken();

        if ( !$sessionToken ) {
            return false;
        }

        $result = $this->sessionService->validateSession( $sessionToken );

        return $result->isSuccess();
    }

}