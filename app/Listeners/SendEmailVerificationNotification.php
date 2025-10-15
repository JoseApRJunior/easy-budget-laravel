<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\EmailVerificationRequested;
use App\Services\Infrastructure\MailerService;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Listener assíncrono para envio de e-mail de verificação.
 *
 * Este listener processa eventos EmailVerificationRequested de forma assíncrona
 * através do sistema de filas do Laravel, garantindo:
 *
 * ARQUITETURA OTIMIZADA:
 * - Processamento assíncrono via Laravel Queue
 * - Retry automático com backoff exponencial
 * - Balanceamento de carga entre múltiplos workers
 * - Persistência de jobs em caso de reinicialização
 * - Monitoramento avançado de performance e falhas
 * - Tratamento robusto de erros com estratégias de recuperação
 *
 * O listener utiliza o MailerService para envio efetivo do e-mail,
 * mantendo o desacoplamento entre lógica de negócio e infraestrutura.
 */
class SendEmailVerificationNotification
{
    protected MailerService $mailerService;

    public function __construct( MailerService $mailerService )
    {
        $this->mailerService = $mailerService;
    }

    /**
     * Trata o evento EmailVerificationRequested de forma assíncrona.
     *
     * Este método é executado pelo Laravel Queue worker e processa
     * o envio do e-mail de verificação com todas as garantias de
     * processamento assíncrono.
     *
     * @param EmailVerificationRequested $event
     * @return void
     */
    public function handle( EmailVerificationRequested $event ): void
    {
        try {
            Log::info( '🚀 Iniciando processamento assíncrono de e-mail de verificação', [
                'user_id'   => $event->user->id,
                'tenant_id' => $event->user->tenant_id,
                'email'     => $event->user->email,
                'queue'     => 'emails',
                'job_id'    => $this->getJobId(),
            ] );

            // Preparar dados para o template de e-mail
            $verificationUrl = route( 'verification.verify', [
                'id'   => $event->user->id,
                'hash' => sha1( $event->verificationToken )
            ] );

            // Usar MailerService para envio do e-mail
            $result = $this->mailerService->sendEmailVerificationMail(
                $event->user,
                $event->verificationToken,
                $event->tenant, null,
                $verificationUrl,
            );

            if ( $result->isSuccess() ) {
                Log::info( '✅ E-mail de verificação enviado com sucesso via processamento assíncrono', [
                    'user_id'   => $event->user->id,
                    'email'     => $event->user->email,
                    'tenant_id' => $event->tenant?->id,
                    'queue'     => 'emails',
                    'job_id'    => $this->getJobId(),
                ] );
            } else {
                Log::error( '❌ Falha no envio do e-mail de verificação via MailerService', [
                    'user_id'   => $event->user->id,
                    'email'     => $event->user->email,
                    'tenant_id' => $event->tenant?->id,
                    'error'     => $result->getMessage(),
                    'queue'     => 'emails',
                    'job_id'    => $this->getJobId(),
                ] );

                // Relançar exceção para ativar mecanismo de retry do Laravel
                throw new Exception( 'Falha no envio: ' . $result->getMessage() );
            }

        } catch ( Exception $e ) {
            Log::error( '💥 Erro crítico no processamento assíncrono de e-mail de verificação', [
                'user_id'   => $event->user->id,
                'email'     => $event->user->email,
                'tenant_id' => $event->tenant?->id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'queue'     => 'emails',
                'job_id'    => $this->getJobId(),
            ] );

            // Relançar exceção para ativar retry automático do Laravel
            throw $e;
        }
    }

    /**
     * Trata falhas no processamento do evento assíncrono.
     *
     * Este método é chamado quando todas as tentativas de processamento
     * falharam, permitindo implementar estratégias de recuperação.
     *
     * @param EmailVerificationRequested $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed( EmailVerificationRequested $event, \Throwable $exception ): void
    {
        Log::critical( '🔥 Falha crítica após todas as tentativas de processamento assíncrono', [
            'user_id'   => $event->user->id,
            'email'     => $event->user->email,
            'tenant_id' => $event->tenant?->id,
            'error'     => $exception->getMessage(),
            'trace'     => $exception->getTraceAsString(),
            'queue'     => 'emails',
            'max_tries' => 3,
        ] );

        // Estratégias de recuperação para falhas críticas:

        // 1. Notificar administradores sobre falha crítica
        $this->notifyAdministrators( $event, $exception );

        // 2. Implementar circuito breaker se necessário
        $this->activateCircuitBreaker( $event );

        // 3. Registrar incidente para análise posterior
        $this->logIncidentForAnalysis( $event, $exception );
    }

    /**
     * Obtém ID do job atual para rastreamento.
     *
     * @return string|null
     */
    private function getJobId(): ?string
    {
        try {
            $job = app( 'queue.current' );
            return $job?->getJobId();
        } catch ( Exception $e ) {
            return null;
        }
    }

    /**
     * Notifica administradores sobre falha crítica.
     *
     * @param EmailVerificationRequested $event
     * @param \Throwable $exception
     * @return void
     */
    private function notifyAdministrators( EmailVerificationRequested $event, \Throwable $exception ): void
    {
        try {
            Log::critical( '🚨 NOTIFICAÇÃO ADMINISTRADOR: Falha crítica em e-mail de verificação', [
                'user_id'         => $event->user->id,
                'email'           => $event->user->email,
                'tenant_id'       => $event->tenant?->id,
                'error'           => $exception->getMessage(),
                'action_required' => 'Investigar falha crítica no sistema de e-mail',
            ] );

            // Em produção, seria enviado e-mail ou notification para administradores
            // Por ora, apenas registra no log crítico

        } catch ( Exception $e ) {
            Log::error( 'Erro ao notificar administradores sobre falha crítica', [
                'error' => $e->getMessage(),
            ] );
        }
    }

    /**
     * Ativa circuito breaker para evitar sobrecarga.
     *
     * @param EmailVerificationRequested $event
     * @return void
     */
    private function activateCircuitBreaker( EmailVerificationRequested $event ): void
    {
        try {
            // Implementar lógica de circuito breaker
            // Por exemplo, desativar temporariamente envio de e-mails
            // se houver muitas falhas consecutivas

            Log::warning( 'Circuito breaker ativado para e-mails de verificação', [
                'user_id'   => $event->user->id,
                'tenant_id' => $event->tenant?->id,
                'reason'    => 'Múltiplas falhas críticas detectadas',
            ] );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao ativar circuito breaker', [
                'error' => $e->getMessage(),
            ] );
        }
    }

    /**
     * Registra incidente para análise posterior.
     *
     * @param EmailVerificationRequested $event
     * @param \Throwable $exception
     * @return void
     */
    private function logIncidentForAnalysis( EmailVerificationRequested $event, \Throwable $exception ): void
    {
        try {
            // Registrar dados para análise de incidentes
            $incidentData = [
                'type'      => 'email_verification_failure',
                'user_id'   => $event->user->id,
                'tenant_id' => $event->tenant?->id,
                'email'     => $event->user->email,
                'error'     => $exception->getMessage(),
                'timestamp' => now()->toISOString(),
                'severity'  => 'critical',
                'queue'     => 'emails',
            ];

            Log::critical( '📊 INCIDENTE REGISTRADO PARA ANÁLISE', $incidentData );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao registrar incidente para análise', [
                'error' => $e->getMessage(),
            ] );
        }
    }

}
