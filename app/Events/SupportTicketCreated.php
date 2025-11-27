<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Support;
use App\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado quando um novo ticket de suporte é criado.
 *
 * Este evento é usado para acionar notificações de contato,
 * logs de auditoria, métricas e outras ações relacionadas à criação de tickets.
 */
class SupportTicketCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * O ticket de suporte criado.
     */
    public Support $support;

    /**
     * Tenant do usuário (opcional, para contexto multi-tenant).
     */
    public ?Tenant $tenant;

    /**
     * Dados originais do formulário de contato.
     */
    public array $contactData;

    /**
     * Cria uma nova instância do evento.
     *
     * @param  Support  $support  Ticket de suporte criado
     * @param  array  $contactData  Dados originais do formulário
     * @param  Tenant|null  $tenant  Tenant do usuário (opcional)
     */
    public function __construct(Support $support, array $contactData, ?Tenant $tenant = null)
    {
        $this->support = $support;
        $this->contactData = $contactData;
        $this->tenant = $tenant;
    }

    /**
     * Obtém dados formatados do ticket para uso em listeners.
     *
     * @return array Dados do ticket formatados
     */
    public function getTicketData(): array
    {
        return [
            'id' => $this->support->id,
            'first_name' => $this->support->first_name,
            'last_name' => $this->support->last_name,
            'email' => $this->support->email,
            'subject' => $this->support->subject,
            'message' => $this->support->message,
            'status' => $this->support->status,
            'tenant_id' => $this->support->tenant_id,
            'created_at' => $this->support->created_at?->toISOString(),
            'updated_at' => $this->support->updated_at?->toISOString(),
        ];
    }

    /**
     * Obtém o nome completo do contato.
     *
     * @return string Nome completo
     */
    public function getContactName(): string
    {
        $firstName = $this->support->first_name ?? '';
        $lastName = $this->support->last_name ?? '';

        $fullName = trim($firstName.' '.$lastName);

        return ! empty($fullName) ? $fullName : 'Usuário';
    }

    /**
     * Verifica se o ticket é de um tenant específico.
     *
     * @param  int  $tenantId  ID do tenant
     * @return bool True se pertence ao tenant
     */
    public function belongsToTenant(int $tenantId): bool
    {
        return $this->support->tenant_id === $tenantId;
    }

    /**
     * Obtém informações de contexto para logs.
     *
     * @return array Contexto para logging
     */
    public function getLogContext(): array
    {
        return [
            'event_type' => 'support_ticket_created',
            'support_id' => $this->support->id,
            'email' => $this->support->email,
            'subject' => $this->support->subject,
            'tenant_id' => $this->tenant?->id,
            'tenant_name' => $this->tenant?->name,
            'created_at' => now()->toISOString(),
        ];
    }
}
