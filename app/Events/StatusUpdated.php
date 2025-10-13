<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado quando o status de uma entidade é atualizado.
 *
 * Este evento é usado para acionar notificações de atualização de status,
 * auditoria e outras ações relacionadas à mudança de status de entidades.
 */
class StatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Model   $entity;
    public string  $oldStatus;
    public string  $newStatus;
    public string  $statusName;
    public ?Tenant $tenant;

    /**
     * Cria uma nova instância do evento.
     *
     * @param Model $entity Entidade que teve o status atualizado
     * @param string $oldStatus Status anterior da entidade
     * @param string $newStatus Novo status da entidade
     * @param string $statusName Nome do status para exibição
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     */
    public function __construct(
        Model $entity,
        string $oldStatus,
        string $newStatus,
        string $statusName,
        ?Tenant $tenant = null,
    ) {
        $this->entity     = $entity;
        $this->oldStatus  = $oldStatus;
        $this->newStatus  = $newStatus;
        $this->statusName = $statusName;
        $this->tenant     = $tenant;
    }

}
