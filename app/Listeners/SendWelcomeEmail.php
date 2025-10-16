<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Services\Infrastructure\MailerService;
use App\Support\ServiceResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Listener responsável por enviar e-mail de boas-vindas quando um usuário se registra.
 *
 * Este listener é executado de forma assíncrona através da queue para melhorar
 * a performance e responsividade da aplicação.
 *
 * Arquitetura: Event Listener → Service Layer → Mail Layer
 * - Usa injeção de dependência para MailerService
 * - Implementa ServiceResult para tratamento padronizado
 * - Logging detalhado para auditoria
 * - Tratamento robusto de erros com retry automático
 */
class SendWelcomeEmail implements ShouldQueue
{
    /**
     * O número de vezes que o job pode ser executado novamente em caso de falha.
     */
    public int $tries = 3;

    /**
     * O tempo em segundos antes de tentar executar o job novamente.
     */
    public int $backoff = 30;

    /**
     * Serviço de e-mail com funcionalidades avançadas.
     */
    private MailerService $mailerService;

    /**
     * Cria uma nova instância do listener.
     *
     * @param MailerService $mailerService Serviço de e-mail injetado
     */
    public function __construct( MailerService $mailerService )
    {
        $this->mailerService = $mailerService;
    }

    /**
     * Handle the event.
     *
     * @param UserRegistered $event
     * @return void
     */
    public function handle( UserRegistered $event ): void
    {
        // Logging inicial com contexto completo
        Log::info( 'Processando evento UserRegistered para envio de e-mail de boas-vindas', [
            'user_id'            => $event->user->id,
            'email'              => $event->user->email,
            'tenant_id'          => $event->tenant?->id,
            'verification_token' => substr( $event->verificationToken ?? '', 0, 10 ) . '...',
            'has_tenant'         => $event->tenant !== null,
            'event_timestamp'    => now()->toISOString(),
        ] );

        try {
            // Validação inicial dos dados do evento
            $this->validateEventData( $event );

            // Gera URL de verificação usando o token do evento
            $confirmationLink = $this->buildConfirmationLink( $event->verificationToken );

            // Envia e-mail usando o serviço injetado
            $result = $this->mailerService->sendWelcomeEmail(
                $event->user,
                $event->tenant,
                $confirmationLink,
            );

            // Trata resultado seguindo padrões arquiteturais
            if ( $result->isSuccess() ) {
                $this->handleSuccess( $event, $result );
            } else {
                $this->handleFailure( $event, $result );
            }

        } catch ( Throwable $e ) {
            $this->handleException( $event, $e );
        }
    }

    /**
     * Valida dados do evento antes do processamento.
     *
     * @param UserRegistered $event Evento a ser validado
     * @return void
     * @throws \InvalidArgumentException Se dados inválidos forem encontrados
     */
    private function validateEventData( UserRegistered $event ): void
    {
        if ( !$event->user || !$event->user->id ) {
            throw new \InvalidArgumentException( 'Dados do usuário inválidos no evento UserRegistered' );
        }

        if ( empty( $event->user->email ) ) {
            throw new \InvalidArgumentException( 'E-mail do usuário não informado no evento UserRegistered' );
        }

        if ( !$event->verificationToken ) {
            throw new \InvalidArgumentException( 'Token de verificação não informado no evento UserRegistered' );
        }

        // Validação adicional de segurança do token
        if ( strlen( $event->verificationToken ) !== 64 ) {
            throw new \InvalidArgumentException( 'Token de verificação com comprimento inválido' );
        }
    }

    /**
     * Constrói URL de confirmação segura usando o token do evento.
     *
     * @param string|null $token Token de confirmação
     * @return string URL completa e funcional
     */
    private function buildConfirmationLink( ?string $token ): string
    {
        if ( empty( $token ) ) {
            Log::warning( 'Token de confirmação vazio - redirecionando para página de verificação', [
                'action' => 'redirect_to_verification_page'
            ] );
            return config( 'app.url' ) . '/email/verify';
        }

        // Validar token - deve ter formato alfanumérico e comprimento adequado
        if ( !preg_match( '/^[a-zA-Z0-9]{64}$/', $token ) ) {
            Log::warning( 'Token de confirmação inválido detectado', [
                'token_length'    => strlen( $token ),
                'expected_length' => 64,
                'action'          => 'redirect_to_login'
            ] );
            return config( 'app.url' ) . '/login';
        }

        // Sanitizar token para uso seguro em URL
        $sanitizedToken = htmlspecialchars( $token, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

        return config( 'app.url' ) . '/confirm-account?token=' . urlencode( $sanitizedToken );
    }

    /**
     * Trata resultado de sucesso do envio de e-mail.
     *
     * @param UserRegistered $event Evento processado
     * @param ServiceResult $result Resultado da operação
     * @return void
     */
    private function handleSuccess( UserRegistered $event, ServiceResult $result ): void
    {
        $data = $result->getData();

        Log::info( 'E-mail de boas-vindas enviado com sucesso via evento', [
            'user_id'         => $event->user->id,
            'email'           => $event->user->email,
            'tenant_id'       => $event->tenant?->id,
            'queued_at'       => $data[ 'queued_at' ] ?? null,
            'queue'           => $data[ 'queue' ] ?? 'unknown',
            'processing_time' => microtime( true ),
            'success'         => true,
        ] );
    }

    /**
     * Trata resultado de falha do envio de e-mail.
     *
     * @param UserRegistered $event Evento processado
     * @param ServiceResult $result Resultado da operação
     * @return void
     * @throws \Exception Para que seja tratada pela queue
     */
    private function handleFailure( UserRegistered $event, ServiceResult $result ): void
    {
        Log::error( 'Falha ao enviar e-mail de boas-vindas via evento', [
            'user_id'         => $event->user->id,
            'email'           => $event->user->email,
            'tenant_id'       => $event->tenant?->id,
            'error_message'   => $result->getMessage(),
            'error_status'    => $result->getStatus(),
            'error_data'      => $result->getData(),
            'processing_time' => microtime( true ),
            'will_retry'      => true,
        ] );

        // Relança a exceção para que seja tratada pela queue com retry automático
        throw new \Exception( 'Falha no envio de e-mail de boas-vindas: ' . $result->getMessage() );
    }

    /**
     * Trata exceções durante o processamento do evento.
     *
     * @param UserRegistered $event Evento que estava sendo processado
     * @param Throwable $exception Exceção ocorrida
     * @return void
     * @throws Throwable Para que seja tratada pela queue
     */
    private function handleException( UserRegistered $event, Throwable $exception ): void
    {
        Log::error( 'Erro crítico no listener SendWelcomeEmail', [
            'user_id'         => $event->user->id,
            'email'           => $event->user->email,
            'tenant_id'       => $event->tenant?->id,
            'error_message'   => $exception->getMessage(),
            'error_type'      => get_class( $exception ),
            'error_file'      => $exception->getFile(),
            'error_line'      => $exception->getLine(),
            'processing_time' => microtime( true ),
            'memory_usage'    => memory_get_usage( true ),
            'will_retry'      => true,
        ] );

        // Relança a exceção para que seja tratada pela queue
        throw $exception;
    }

    /**
     * Handle a job failure.
     *
     * @param UserRegistered $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed( UserRegistered $event, \Throwable $exception ): void
    {
        Log::critical( 'Listener SendWelcomeEmail falhou após todas as tentativas', [
            'user_id'          => $event->user->id,
            'email'            => $event->user->email,
            'tenant_id'        => $event->tenant?->id,
            'error_message'    => $exception->getMessage(),
            'error_type'       => get_class( $exception ),
            'attempts'         => $this->tries,
            'max_attempts'     => $this->tries,
            'backoff_strategy' => 'exponential',
            'final_failure'    => true,
        ] );

        // Em produção, poderia notificar administradores sobre a falha
        // ou implementar lógica de fallback
    }

}
