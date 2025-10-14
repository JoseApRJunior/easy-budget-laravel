<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado quando um e-mail de verificação é solicitado.
 *
 * Este evento segue o padrão estabelecido no sistema para notificações por e-mail,
 * permitindo desacoplamento entre a lógica de negócio e o envio de e-mails.
 *
 * O evento é capturado pelo listener SendEmailVerificationNotification que
 * utiliza o MailerService para envio efetivo do e-mail.
 */
class EmailVerificationRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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

}
