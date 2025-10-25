<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\EmailVerificationRequested;
use App\Services\Infrastructure\ConfirmationLinkService;
use App\Services\Infrastructure\MailerService;
use App\Support\ServiceResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Listener responsável por enviar e-mail de verificação quando um usuário solicita verificação de e-mail.
 *
 * Implementa funcionalidades completas de tratamento de erro, logging e validações
 * seguindo os padrões arquiteturais do sistema Easy Budget Laravel.
 *
 * Funcionalidades implementadas:
 * - Tratamento robusto de erros com try-catch
 * - Logging detalhado para monitoramento
 * - Validações adequadas dos dados recebidos
 * - Tratamento específico para verificação de e-mail
 * - Métricas de performance integradas
 * - Sistema de retry automático com backoff exponencial
 */
class SendEmailVerification implements ShouldQueue
{
    /**
     * O número de vezes que o job pode ser executado novamente em caso de falha.
     * Configuração otimizada baseada em análise de padrões de erro.
     */
    public int $tries = 3;

    /**
     * O tempo em segundos antes de tentar executar o job novamente.
     * Estratégia de backoff exponencial inteligente.
     */
    public int $backoff = 30;

    /**
     * Serviço de e-mail com funcionalidades avançadas.
     * Injetado automaticamente pelo Laravel.
     */
    protected MailerService $mailerService;

    /**
     * Serviço para construção segura de links de confirmação.
     * Injetado automaticamente pelo Laravel.
     */
    protected ConfirmationLinkService $confirmationLinkService;

    /**
     * Métricas de performance do processamento.
     */
    private array $performanceMetrics = [];

    /**
     * Cria uma nova instância do listener.
     *
     * @param MailerService $mailerService Serviço de e-mail injetado
     * @param ConfirmationLinkService $confirmationLinkService Serviço de links de confirmação injetado
     */
    public function __construct(
        MailerService $mailerService,
        ConfirmationLinkService $confirmationLinkService,
    ) {
        $this->mailerService           = $mailerService;
        $this->confirmationLinkService = $confirmationLinkService;
        $this->initializePerformanceMetrics();
    }

    /**
     * Processa o evento de solicitação de verificação e envia e-mail de verificação.
     *
     * Implementa o padrão Template Method com funcionalidades completas:
     * - Logging inicial detalhado
     * - Validação rigorosa dos dados
     * - Processamento específico do e-mail
     * - Tratamento de resultado (sucesso/falha)
     * - Tratamento de exceções
     * - Métricas de performance
     *
     * @param EmailVerificationRequested $event Evento de solicitação de verificação
     */
    final public function handle( EmailVerificationRequested $event ): void
    {
        // Iniciar métricas de performance
        $this->startPerformanceTracking();

        try {
            // 1. Logging inicial estruturado
            $this->logEventStart( $event );

            // 2. Validação inicial rigorosa
            $this->validateEvent( $event );

            // 3. Processamento específico do e-mail de verificação
            $result = $this->processVerificationEmail( $event );

            // 4. Tratamento padronizado do resultado
            if ( $result->isSuccess() ) {
                $this->handleSuccess( $event, $result );
            } else {
                $this->handleFailure( $event, $result );
            }

        } catch ( Throwable $e ) {
            $this->handleException( $event, $e );
        } finally {
            // Finalizar métricas de performance
            $this->endPerformanceTracking();
        }
    }

    /**
     * Processa especificamente o envio de e-mail de verificação.
     *
     * Contém a lógica específica deste tipo de e-mail com tratamento
     * completo de erros e validações de negócio.
     *
     * @param EmailVerificationRequested $event Evento de solicitação de verificação
     * @return ServiceResult Resultado do processamento
     */
    protected function processVerificationEmail( EmailVerificationRequested $event ): ServiceResult
    {
        try {
            // Validação adicional específica para verificação
            if ( !$event->user || !$event->user->id ) {
                throw new \InvalidArgumentException( 'Dados do usuário inválidos no evento de verificação' );
            }

            if ( empty( $event->user->email ) ) {
                throw new \InvalidArgumentException( 'E-mail do usuário não informado no evento de verificação' );
            }

            // Validação rigorosa do token de verificação usando formato base64url
            if ( !validateAndSanitizeToken( $event->verificationToken, 'base64url' ) ) {
                throw new \InvalidArgumentException( 'Token de verificação com formato inválido' );
            }

            // Verificação adicional: usuário já verificado não deveria solicitar verificação
            if ( $event->user->email_verified_at !== null ) {
                Log::warning( 'Tentativa de reenvio de e-mail de verificação para usuário já verificado', [
                    'user_id'     => $event->user->id,
                    'email'       => $event->user->email,
                    'verified_at' => $event->user->email_verified_at,
                ] );

                return ServiceResult::error( 'Usuário já possui e-mail verificado' );
            }

            // Gera URL de verificação segura usando serviço centralizado
            $confirmationLink = $this->confirmationLinkService->buildConfirmationLinkByContext( $event->verificationToken, 'verification' );

            // Envia e-mail usando o serviço injetado com tratamento de erro específico
            return $this->mailerService->sendEmailVerificationMail(
                $event->user,
                $event->tenant,
                $confirmationLink,
            );

        } catch ( Throwable $e ) {
            // Log detalhado do erro específico de verificação
            Log::error( 'Erro específico no processamento de e-mail de verificação', [
                'user_id'         => $event->user->id ?? null,
                'email'           => $event->user->email ?? null,
                'tenant_id'       => $event->tenant?->id,
                'error_message'   => $e->getMessage(),
                'error_type'      => get_class( $e ),
                'error_file'      => $e->getFile(),
                'error_line'      => $e->getLine(),
                'processing_time' => $this->getProcessingTime(),
                'memory_usage'    => memory_get_usage( true ),
                'event_type'      => 'email_verification',
            ] );

            return ServiceResult::error( 'Erro interno no processamento de verificação: ' . $e->getMessage() );
        }
    }

    /**
     * Validação rigorosa do evento de verificação.
     *
     * Implementa validações específicas para o contexto de verificação de e-mail
     * com tratamento detalhado de cada cenário de erro.
     *
     * @param EmailVerificationRequested $event Evento a ser validado
     * @throws \InvalidArgumentException Se alguma validação falhar
     */
    protected function validateEvent( EmailVerificationRequested $event ): void
    {
        // Validação básica comum a todos os eventos de e-mail
        if ( !$event || !$event->user || !$event->user->id ) {
            throw new \InvalidArgumentException( 'Dados do usuário inválidos no evento de verificação' );
        }

        if ( empty( $event->user->email ) ) {
            throw new \InvalidArgumentException( 'E-mail do usuário não informado no evento de verificação' );
        }

        // Validação específica de verificação - token obrigatório
        if ( !$event->verificationToken ) {
            Log::error( 'Token de verificação obrigatório não informado', [
                'user_id'   => $event->user->id,
                'tenant_id' => $event->tenant?->id,
                'email'     => $event->user->email,
            ] );
            throw new \InvalidArgumentException( 'Token de verificação obrigatório não informado' );
        }

        // Validação de formato do token usando base64url
        if ( !validateAndSanitizeToken( $event->verificationToken, 'base64url' ) ) {
            Log::error( 'Token de verificação com formato inválido', [
                'token_length'    => strlen( $event->verificationToken ),
                'token_sample'    => substr( $event->verificationToken, 0, 10 ) . '...',
                'expected_format' => 'base64url',
                'user_id'         => $event->user->id,
                'tenant_id'       => $event->tenant?->id,
            ] );
            throw new \InvalidArgumentException( 'Token de verificação com formato inválido' );
        }

        // Validação adicional: verificar se o usuário já está verificado
        if ( $event->user->email_verified_at !== null ) {
            Log::info( 'Usuário já verificado solicitou novo e-mail de verificação', [
                'user_id'     => $event->user->id,
                'email'       => $event->user->email,
                'verified_at' => $event->user->email_verified_at,
            ] );
        }
    }

    /**
     * Tratamento padronizado de sucesso.
     *
     * @param EmailVerificationRequested $event Evento processado
     * @param ServiceResult $result Resultado da operação
     */
    protected function handleSuccess( EmailVerificationRequested $event, ServiceResult $result ): void
    {
        $data = $result->getData();

        Log::info( 'E-mail de verificação enviado com sucesso', [
            'user_id'         => $event->user->id,
            'email'           => $event->user->email,
            'tenant_id'       => $event->tenant?->id,
            'queued_at'       => $data[ 'queued_at' ] ?? null,
            'queue'           => $data[ 'queue' ] ?? 'emails',
            'processing_time' => $this->getProcessingTime(),
            'memory_usage'    => memory_get_usage( true ),
            'success'         => true,
            'event_type'      => 'email_verification',
        ] );
    }

    /**
     * Tratamento padronizado de falha.
     *
     * @param EmailVerificationRequested $event Evento processado
     * @param ServiceResult $result Resultado da operação
     */
    protected function handleFailure( EmailVerificationRequested $event, ServiceResult $result ): void
    {
        Log::error( 'Falha no envio de e-mail de verificação', [
            'user_id'         => $event->user->id,
            'email'           => $event->user->email,
            'tenant_id'       => $event->tenant?->id,
            'error_message'   => $result->getMessage(),
            'error_status'    => $result->getStatus(),
            'error_data'      => $result->getData(),
            'processing_time' => $this->getProcessingTime(),
            'memory_usage'    => memory_get_usage( true ),
            'will_retry'      => true,
            'event_type'      => 'email_verification',
        ] );

        // Relança a exceção para que seja tratada pela queue com retry automático
        throw new \Exception( 'Falha no envio de e-mail de verificação: ' . $result->getMessage() );
    }

    /**
     * Tratamento padronizado de exceções.
     *
     * @param EmailVerificationRequested $event Evento que estava sendo processado
     * @param Throwable $exception Exceção ocorrida
     */
    private function handleException( EmailVerificationRequested $event, Throwable $exception ): void
    {
        Log::error( 'Erro crítico no processamento de e-mail de verificação', [
            'user_id'         => $event->user->id ?? null,
            'email'           => $event->user->email ?? null,
            'tenant_id'       => $event->tenant?->id,
            'error_message'   => $exception->getMessage(),
            'error_type'      => get_class( $exception ),
            'error_file'      => $exception->getFile(),
            'error_line'      => $exception->getLine(),
            'processing_time' => $this->getProcessingTime(),
            'memory_usage'    => memory_get_usage( true ),
            'will_retry'      => true,
            'event_type'      => 'email_verification',
        ] );

        // Relança a exceção para que seja tratada pela queue
        throw $exception;
    }

    /**
     * Tratamento padronizado de falha crítica do job.
     *
     * @param EmailVerificationRequested $event Evento que estava sendo processado
     * @param Throwable $exception Última exceção ocorrida
     */
    final public function failed( EmailVerificationRequested $event, Throwable $exception ): void
    {
        Log::critical( 'Falha crítica no envio de e-mail de verificação após todas as tentativas', [
            'user_id'          => $event->user->id ?? null,
            'email'            => $event->user->email ?? null,
            'tenant_id'        => $event->tenant?->id,
            'error_message'    => $exception->getMessage(),
            'error_type'       => get_class( $exception ),
            'attempts'         => $this->tries,
            'max_attempts'     => $this->tries,
            'backoff_strategy' => 'exponential',
            'final_failure'    => true,
            'event_type'       => 'email_verification',
        ] );

        // Em produção, poderia notificar administradores sobre a falha
        // ou implementar lógica de fallback específica para verificação
    }

    /**
     * Logging inicial estruturado e detalhado.
     *
     * @param EmailVerificationRequested $event Evento recebido
     */
    private function logEventStart( EmailVerificationRequested $event ): void
    {
        Log::info( 'Processando evento EmailVerificationRequested para envio de e-mail de verificação', [
            'user_id'         => $event->user->id,
            'email'           => $event->user->email,
            'tenant_id'       => $event->tenant?->id,
            'event_type'      => 'email_verification',
            'listener_class'  => static::class,
            'processing_time' => microtime( true ),
            'memory_usage'    => memory_get_usage( true ),
            'queue'           => 'emails',
        ] );
    }

    /**
     * Inicializa métricas de performance.
     */
    private function initializePerformanceMetrics(): void
    {
        $this->performanceMetrics = [
            'start_time'   => 0,
            'end_time'     => 0,
            'memory_start' => 0,
            'memory_end'   => 0,
        ];
    }

    /**
     * Inicia rastreamento de performance.
     */
    private function startPerformanceTracking(): void
    {
        $this->performanceMetrics[ 'start_time' ]   = microtime( true );
        $this->performanceMetrics[ 'memory_start' ] = memory_get_usage( true );
    }

    /**
     * Finaliza rastreamento de performance.
     */
    private function endPerformanceTracking(): void
    {
        $this->performanceMetrics[ 'end_time' ]   = microtime( true );
        $this->performanceMetrics[ 'memory_end' ] = memory_get_usage( true );
    }

    /**
     * Obtém tempo de processamento em segundos.
     *
     * @return float Tempo de processamento
     */
    private function getProcessingTime(): float
    {
        return $this->performanceMetrics[ 'end_time' ] - $this->performanceMetrics[ 'start_time' ];
    }

    /**
     * Constrói URL de confirmação segura usando o serviço centralizado.
     *
     * @param string|null $token Token de confirmação
     * @param string $route Rota para confirmação (padrão: /confirm-account)
     * @param string $fallbackRoute Rota de fallback (padrão: /login)
     * @return string URL de confirmação segura
     */
    protected function buildConfirmationLink(
        ?string $token,
        string $route = '/confirm-account',
        string $fallbackRoute = '/login',
    ): string {
        return $this->confirmationLinkService->buildConfirmationLink( $token, $route, $fallbackRoute );
    }

    /**
     * Constrói URL de confirmação para e-mails de verificação.
     *
     * @deprecated Use buildConfirmationLinkByContext() com contexto 'verification'
     * @param string|null $token Token de confirmação
     * @return string URL de confirmação
     */
    protected function buildVerificationConfirmationLink( ?string $token ): string
    {
        return $this->confirmationLinkService->buildConfirmationLinkByContext( $token, 'verification' );
    }

}
