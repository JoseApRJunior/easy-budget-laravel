<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento assíncrono disparado quando um e-mail de verificação é solicitado.
 *
 * Este evento implementa ShouldQueue para processamento assíncrono via filas,
 * garantindo melhor performance e confiabilidade no envio de e-mails.
 *
 * ARQUITETURA OTIMIZADA:
 * - Processamento assíncrono via Laravel Queue
 * - Retry automático em caso de falhas temporárias
 * - Balanceamento de carga entre workers
 * - Persistência de jobs em caso de reinicialização
 * - Monitoramento de performance e falhas
 *
 * O evento é processado pelo listener SendEmailVerificationNotification que
 * utiliza o MailerService para envio efetivo do e-mail.
 */
class EmailVerificationRequested implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Número máximo de tentativas de processamento.
     */
    public int $tries = 3;

    /**
     * Timeout para processamento (em segundos).
     */
    public int $timeout = 30;

    /**
     * Delay entre tentativas de retry (em segundos).
     */
    public int $backoff = 10;

    /**
     * Fila específica para processamento de e-mails de verificação.
     */
    public string $queue = 'emails';

    public function __construct(
        public User $user,
        public string $verificationToken,
        public ?Tenant $tenant = null,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [];
    }

    /**
     * Determina se o evento deve ser processado imediatamente ou enfileirado.
     *
     * @return bool
     */
    public function shouldQueue(): bool
    {
        return true;
    }

    /**
     * Define configurações específicas para processamento na fila.
     *
     * @return array
     */
    public function viaQueue(): string
    {
        return $this->queue;
    }

    /**
     * Trata falhas no processamento do evento.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed( \Throwable $exception ): void
    {
        \Illuminate\Support\Facades\Log::error( 'Falha crítica no processamento assíncrono de e-mail de verificação', [
            'user_id'   => $this->user->id,
            'tenant_id' => $this->tenant?->id,
            'email'     => $this->user->email,
            'error'     => $exception->getMessage(),
            'max_tries' => $this->tries,
            'queue'     => $this->queue,
        ] );

        // Em produção, poderíamos:
        // - Notificar administradores sobre falha crítica
        // - Implementar circuito breaker
        // - Acionar sistema de monitoramento
    }

}
