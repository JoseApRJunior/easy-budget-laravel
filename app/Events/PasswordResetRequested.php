<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado quando um usuário solicita redefinição de senha.
 *
 * Este evento é usado para acionar o envio de e-mail de redefinição de senha
 * e outras ações relacionadas à segurança da conta.
 */
class PasswordResetRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;

    public string $resetToken;

    public ?Tenant $tenant;

    /**
     * Cria uma nova instância do evento.
     *
     * @param  User  $user  Usuário que solicitou a redefinição
     * @param  string  $resetToken  Token de redefinição de senha
     * @param  Tenant|null  $tenant  Tenant do usuário (opcional)
     */
    public function __construct(User $user, string $resetToken, ?Tenant $tenant = null)
    {
        $this->user = $user;
        $this->resetToken = $resetToken;
        $this->tenant = $tenant;
    }
}
