<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado quando um usuário faz login social com Google.
 *
 * Este evento é usado para acionar notificações de boas-vindas específicas
 * para login social, informando que a conta está ativa e verificada.
 */
class SocialLoginWelcome
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User    $user;
    public ?Tenant $tenant;
    public string  $provider;

    /**
     * Cria uma nova instância do evento.
     *
     * @param User $user Usuário que fez login social
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param string $provider Provedor social usado (ex: 'google')
     */
    public function __construct(
        User $user,
        ?Tenant $tenant = null,
        string $provider = 'google',
    ) {
        $this->user     = $user;
        $this->tenant   = $tenant;
        $this->provider = $provider;
    }

}
