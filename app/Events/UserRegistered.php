<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado quando um usuário se registra no sistema.
 *
 * Este evento é usado para acionar notificações de boas-vindas,
 * verificação de e-mail e outras ações relacionadas ao registro.
 */
class UserRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User    $user;
    public ?Tenant $tenant;

    /**
     * Cria uma nova instância do evento.
     *
     * @param User $user Usuário que se registrou
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     */
    public function __construct( User $user, ?Tenant $tenant = null )
    {
        $this->user   = $user;
        $this->tenant = $tenant;
    }

}
