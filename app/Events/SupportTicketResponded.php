<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado quando uma resposta de suporte é enviada.
 *
 * Este evento é usado para acionar notificações de resposta de suporte,
 * atualização de status do ticket e outras ações relacionadas ao suporte.
 */
class SupportTicketResponded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $ticket;

    public string $response;

    public ?Tenant $tenant;

    /**
     * Cria uma nova instância do evento.
     *
     * @param  array  $ticket  Dados do ticket de suporte
     * @param  string  $response  Resposta enviada para o ticket
     * @param  Tenant|null  $tenant  Tenant do usuário (opcional)
     */
    public function __construct(array $ticket, string $response, ?Tenant $tenant = null)
    {
        $this->ticket = $ticket;
        $this->response = $response;
        $this->tenant = $tenant;
    }
}
