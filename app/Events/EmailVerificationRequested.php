<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado quando é solicitada a verificação de e-mail de um usuário.
 *
 * Este evento é usado especificamente para acionar o envio do e-mail de verificação,
 * permitindo que o usuário confirme seu endereço de e-mail e ative sua conta.
 * Diferencia-se do evento de registro por focar exclusivamente no processo de verificação.
 */
class EmailVerificationRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User    $user;
    public ?Tenant $tenant;
    public string  $verificationToken;

    /**
     * Cria uma nova instância do evento.
     *
     * @param User $user Usuário que se registrou
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param string $verificationToken Token de verificação criado
     */
    public function __construct( User $user, ?Tenant $tenant = null, string $verificationToken )
    {
        $this->user              = $user;
        $this->tenant            = $tenant;
        $this->verificationToken = $verificationToken;
    }

}
