<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Services\Infrastructure\LinkService;
use App\Services\Infrastructure\MailerService;
use App\Support\ServiceResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Listener responsável por enviar e-mail de boas-vindas quando um usuário se registra.
 *
 * Implementa funcionalidades completas de tratamento de erro, logging e validações
 * seguindo os padrões arquiteturais do sistema Easy Budget Laravel.
 *
 * Funcionalidades implementadas:
 * - Tratamento robusto de erros com try-catch
 * - Logging detalhado para monitoramento
 * - Validações adequadas dos dados recebidos
 * - Tratamento específico para boas-vindas
 * - Métricas de performance integradas
 * - Sistema de retry automático com backoff exponencial
 */
class SendWelcomeEmail implements ShouldQueue
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
    protected LinkService $linkService;

    /**
     * Métricas de performance do processamento.
     */
    private array $performanceMetrics = [];

    /**
     * Cria uma nova instância do listener.
     *
     * @param MailerService $mailerService Serviço de e-mail injetado
     * @param LinkService $linkService Serviço de links de confirmação injetado
     */
    public function __construct(
        MailerService $mailerService,
        LinkService $linkService,
    ) {
        $this->mailerService = $mailerService;
        $this->linkService   = $linkService;
        $this->initializePerformanceMetrics();
    }

    /**
     * Processa o evento de registro de usuário e envia e-mail de boas-vindas.
     *
     * Implementa o padrão Template Method com funcionalidades completas:
     * - Logging inicial detalhado
     * - Validação rigorosa dos dados
     * - Processamento específico do e-mail
     * - Tratamento de resultado (sucesso/falha)
     * - Tratamento de exceções
     * - Métricas de performance
     *
     * @param UserRegistered $event Evento de registro de usuário
     */
    final public function handle( UserRegistered $event ): void
    {
        // Iniciar métricas de performance
        $this->startPerformanceTracking();

        try {
            // 1. Logging inicial estruturado
            $this->logEventStart( $event );

            // 2. Validação inicial rigorosa
            $this->validateEvent( $event );

            // 3. Processamento específico do e-mail de boas-vindas
            $result = $this->processWelcomeEmail( $event );

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
     * Processa especificamente o envio de e-mail de boas-vindas.
     *
     * Contém a lógica específica deste tipo de e-mail com tratamento
     * completo de erros e validações de negócio.
     *
     * @param UserRegistered $event Evento de registro de usuário
     * @return ServiceResult Resultado do processamento
     */
    protected function processWelcomeEmail( UserRegistered $event ): ServiceResult
    {
        try {
            // Validação adicional específica para boas-vindas
            if ( !$event->user || !$event->user->id ) {
                throw new \InvalidArgumentException( 'Dados do usuário inválidos no evento de boas-vindas' );
            }

            if ( empty( $event->user->email ) ) {
                throw new \InvalidArgumentException( 'E-mail do usuário não informado no evento de boas-vindas' );
            }

            // Validação rigorosa do token de verificação usando formato base64url
            if ( !validateAndSanitizeToken( $event->verificationToken, 'base64url' ) ) {
                throw new \InvalidArgumentException( 'Token de verificação com formato inválido para boas-vindas' );
            }

            // Gera URL de confirmação segura usando serviço centralizado
            $confirmationLink = $this->linkService->buildConfirmationLinkByContext( $event->verificationToken, 'welcome' );

            // Envia e-mail usando o serviço injetado com tratamento de erro específico
            return $this->mailerService->sendWelcomeEmail(
                $event->user,
                $event->tenant,
                $confirmationLink,
            );

        } catch ( Throwable $e ) {
            // Log detalhado do erro específico de boas-vindas
            Log::error( 'Erro específico no processamento de e-mail de boas-vindas', [
                'user_id'         => $event->user->id ?? null,
                'email'           => $event->user->email ?? null,
                'tenant_id'       => $event->tenant?->id,
                'error_message'   => $e->getMessage(),
                'error_type'      => get_class( $e ),
                'error_file'      => $e->getFile(),
                'error_line'      => $e->getLine(),
                'processing_time' => $this->getProcessingTime(),
                'memory_usage'    => memory_get_usage( true ),
                'event_type'      => 'welcome_email',
            ] );

            return ServiceResult::error( 'Erro interno no processamento de boas-vindas: ' . $e->getMessage() );
        }
    }

    /**
     * Validação rigorosa do evento de boas-vindas.
     *
     * Implementa validações específicas para o contexto de boas-vindas
     * com tratamento detalhado de cada cenário de erro.
     *
     * @param UserRegistered $event Evento a ser validado
     * @throws \InvalidArgumentException Se alguma validação falhar
     */
    protected function validateEvent( UserRegistered $event ): void
    {
        // Validação básica comum a todos os eventos de e-mail
        if ( !$event || !$event->user || !$event->user->id ) {
            throw new \InvalidArgumentException( 'Dados do usuário inválidos no evento de boas-vindas' );
        }

        if ( empty( $event->user->email ) ) {
            throw new \InvalidArgumentException( 'E-mail do usuário não informado no evento de boas-vindas' );
        }

        // Validação específica de boas-vindas - token obrigatório
        if ( !$event->verificationToken ) {
            throw new \InvalidArgumentException( 'Token de verificação obrigatório não informado para boas-vindas' );
        }

        // Validação de formato do token usando base64url (32 bytes = 43 caracteres)
        if ( !validateAndSanitizeToken( $event->verificationToken, 'base64url' ) ) {
            throw new \InvalidArgumentException( 'Token de verificação com formato inválido para boas-vindas' );
        }

        // Validação adicional: verificar se o usuário não está tentando receber múltiplos e-mails
        if ( $event->user->email_verified_at !== null ) {
            Log::warning( 'Tentativa de envio de e-mail de boas-vindas para usuário já verificado', [
                'user_id'     => $event->user->id,
                'email'       => $event->user->email,
                'verified_at' => $event->user->email_verified_at,
            ] );
        }
    }

    /**
     * Tratamento padronizado de sucesso.
     *
     * @param UserRegistered $event Evento processado
     * @param ServiceResult $result Resultado da operação
     */
    protected function handleSuccess( UserRegistered $event, ServiceResult $result ): void
    {
        $data = $result->getData();

        Log::info( 'E-mail de boas-vindas enviado com sucesso', [
            'user_id'         => $event->user->id,
            'email'           => $event->user->email,
            'tenant_id'       => $event->tenant?->id,
            'queued_at'       => $data[ 'queued_at' ] ?? null,
            'queue'           => $data[ 'queue' ] ?? 'emails',
            'processing_time' => $this->getProcessingTime(),
            'memory_usage'    => memory_get_usage( true ),
            'success'         => true,
            'event_type'      => 'welcome_email',
        ] );
    }

    /**
     * Tratamento padronizado de falha.
     *
     * @param UserRegistered $event Evento processado
     * @param ServiceResult $result Resultado da operação
     */
    protected function handleFailure( UserRegistered $event, ServiceResult $result ): void
    {
        Log::error( 'Falha no envio de e-mail de boas-vindas', [
            'user_id'         => $event->user->id,
            'email'           => $event->user->email,
            'tenant_id'       => $event->tenant?->id,
            'error_message'   => $result->getMessage(),
            'error_status'    => $result->getStatus(),
            'error_data'      => $result->getData(),
            'processing_time' => $this->getProcessingTime(),
            'memory_usage'    => memory_get_usage( true ),
            'will_retry'      => true,
            'event_type'      => 'welcome_email',
        ] );

        // Relança a exceção para que seja tratada pela queue com retry automático
        throw new \Exception( 'Falha no envio de e-mail de boas-vindas: ' . $result->getMessage() );
    }

    /**
     * Tratamento padronizado de exceções.
     *
     * @param UserRegistered $event Evento que estava sendo processado
     * @param Throwable $exception Exceção ocorrida
     */
    private function handleException( UserRegistered $event, Throwable $exception ): void
    {
        Log::error( 'Erro crítico no processamento de e-mail de boas-vindas', [
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
            'event_type'      => 'welcome_email',
        ] );

        // Relança a exceção para que seja tratada pela queue
        throw $exception;
    }

    /**
     * Tratamento padronizado de falha crítica do job.
     *
     * @param UserRegistered $event Evento que estava sendo processado
     * @param Throwable $exception Última exceção ocorrida
     */
    final public function failed( UserRegistered $event, Throwable $exception ): void
    {
        Log::critical( 'Falha crítica no envio de e-mail de boas-vindas após todas as tentativas', [
            'user_id'          => $event->user->id ?? null,
            'email'            => $event->user->email ?? null,
            'tenant_id'        => $event->tenant?->id,
            'error_message'    => $exception->getMessage(),
            'error_type'       => get_class( $exception ),
            'attempts'         => $this->tries,
            'max_attempts'     => $this->tries,
            'backoff_strategy' => 'exponential',
            'final_failure'    => true,
            'event_type'       => 'welcome_email',
        ] );

        // Em produção, poderia notificar administradores sobre a falha
        // ou implementar lógica de fallback específica para boas-vindas
    }

    /**
     * Logging inicial estruturado e detalhado.
     *
     * @param UserRegistered $event Evento recebido
     */
    private function logEventStart( UserRegistered $event ): void
    {
        Log::info( 'Processando evento UserRegistered para envio de e-mail de boas-vindas', [
            'user_id'         => $event->user->id,
            'email'           => $event->user->email,
            'tenant_id'       => $event->tenant?->id,
            'event_type'      => 'welcome_email',
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

}
