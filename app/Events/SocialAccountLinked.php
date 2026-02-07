<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado quando uma conta social é vinculada a um usuário existente.
 *
 * Este evento é usado para notificar o usuário sobre a vinculação de uma conta social
 * (Google, Facebook, etc.) a uma conta existente, enviando um e-mail de confirmação.
 */
class SocialAccountLinked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;

    public string $provider;

    public array $socialData;

    /**
     * Cria uma nova instância do evento.
     *
     * @param  User  $user  Usuário que teve a conta vinculada
     * @param  string  $provider  Provedor social (google, facebook, etc.)
     * @param  array  $socialData  Dados do provedor social
     */
    public function __construct(User $user, string $provider, array $socialData)
    {
        $this->user = $user;
        $this->provider = $provider;
        $this->socialData = $socialData;
    }
}
