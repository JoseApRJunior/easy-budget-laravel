<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable class para envio de notificações de atualização de status.
 *
 * Esta classe implementa o padrão ShouldQueue para processamento assíncrono,
 * garantindo melhor performance e confiabilidade no envio de e-mails.
 * Pode ser usada para diferentes tipos de entidades (budgets, services, invoices, etc.).
 */
class StatusUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Entidade que teve o status atualizado.
     */
    public Model $entity;

    /**
     * Novo status da entidade.
     */
    public string $status;

    /**
     * Nome do status para exibição.
     */
    public string $statusName;

    /**
     * Tenant do usuário (opcional, para contexto multi-tenant).
     */
    public ?Tenant $tenant;

    /**
     * Dados adicionais da empresa para o template.
     */
    public array $company;

    /**
     * URL para acessar a entidade.
     */
    public ?string $entityUrl;

    /**
     * Cria uma nova instância da mailable.
     *
     * @param  Model  $entity  Entidade que teve o status atualizado
     * @param  string  $status  Novo status da entidade
     * @param  string  $statusName  Nome do status para exibição
     * @param  Tenant|null  $tenant  Tenant do usuário (opcional)
     * @param  array|null  $company  Dados da empresa (opcional)
     * @param  string|null  $entityUrl  URL para acessar a entidade (opcional)
     */
    public function __construct(
        Model $entity,
        string $status,
        string $statusName,
        ?Tenant $tenant = null,
        ?array $company = null,
        ?string $entityUrl = null,
    ) {
        $this->entity = $entity;
        $this->status = $status;
        $this->statusName = $statusName;
        $this->tenant = $tenant;
        $this->company = $company ?? [];
        $this->entityUrl = $entityUrl;
    }

    /**
     * Define o envelope do e-mail (assunto).
     */
    public function envelope(): Envelope
    {
        $statusPrefix = match ($this->status) {
            'approved', 'confirmed' => '✅ Confirmado',
            'cancelled', 'rejected' => '❌ Cancelado',
            'completed', 'finished' => '🏁 Concluído',
            'pending' => '⏳ Pendente',
            default => '🔔 Atualização',
        };

        return new Envelope(
            subject: $statusPrefix.': '.$this->getEntityTitle().' #'.$this->getEntityCode(),
        );
    }

    /**
     * Define o conteúdo do e-mail.
     */
    public function content(): Content
    {
        $statusDescription = $this->statusName;
        
        // Tentar obter a descrição completa do enum baseado no status capturado no evento
        try {
            $entityType = $this->getEntityType();
            $enumClass = match ($entityType) {
                'budget' => \App\Enums\BudgetStatus::class,
                'invoice' => \App\Enums\InvoiceStatus::class,
                'service' => \App\Enums\ServiceStatus::class,
                'schedule' => \App\Enums\ScheduleStatus::class,
                default => null,
            };

            if ($enumClass && method_exists($enumClass, 'tryFrom')) {
                $statusEnum = $enumClass::tryFrom($this->status);
                if ($statusEnum && method_exists($statusEnum, 'getDescription')) {
                    $statusDescription = $statusEnum->getDescription();
                }
            }
        } catch (\Exception $e) {
            // Mantém o statusName original em caso de erro
        }

        $serviceStatus = null;
        $serviceStatusColor = null;
        if ($this->getEntityType() === 'schedule' && isset($this->entity->service?->status)) {
            $serviceStatus = $this->entity->service->status->label();
            $serviceStatusColor = $this->entity->service->status->getColor();
        }

        return new Content(
            view: $this->getViewName(),
            with: [
                'emailData' => [
                    'first_name' => $this->getUserFirstName(),
                    'customer_name' => $this->getUserName(),
                    'service_code' => $this->getEntityCode(),
                    'service_status_name' => $this->statusName,
                    'service_status_description' => $statusDescription,
                    'service_description' => $this->getEntityDescription(),
                    'service_total' => $this->getEntityTotal(),
                    'link' => $this->entityUrl ?? $this->generateEntityUrl(),
                    'entity_type' => $this->getEntityTitle(),
                    'old_status' => $this->getEntityOldStatus(),
                    'new_status' => $this->status,
                    'status_changed_at' => now()->format('d/m/Y H:i'),
                    'related_service_status' => $serviceStatus,
                    'related_service_status_color' => $serviceStatusColor,
                ],
                'company' => $this->getCompanyData(),
                'urlSuporte' => config('app.url').'/support',
                'tenant' => $this->tenant,
                'entity' => $this->entity,
                'isSystemEmail' => false,
                'statusColor' => $this->getStatusColor(),
            ],
        );
    }

    /**
     * Determina o nome da view baseado no tipo de entidade e status.
     *
     * @return string Nome da view
     */
    private function getViewName(): string
    {
        $entityType = $this->getEntityType();

        return match ($entityType) {
            'schedule' => 'emails.schedule.status-update',
            'service' => 'emails.service.status-update',
            'budget' => 'emails.budget.budget-notification',
            default => 'emails.notification-status',
        };
    }

    /**
     * Obtém a cor do status da entidade.
     *
     * @return string Cor hexadecimal
     */
    private function getStatusColor(): string
    {
        // Tentar obter a cor do enum baseado no status capturado no evento (snapshot)
        try {
            $entityType = $this->getEntityType();
            $enumClass = match ($entityType) {
                'budget' => \App\Enums\BudgetStatus::class,
                'invoice' => \App\Enums\InvoiceStatus::class,
                'service' => \App\Enums\ServiceStatus::class,
                'schedule' => \App\Enums\ScheduleStatus::class,
                default => null,
            };

            if ($enumClass && method_exists($enumClass, 'tryFrom')) {
                $statusEnum = $enumClass::tryFrom($this->status);
                if ($statusEnum instanceof \App\Contracts\Interfaces\StatusEnumInterface) {
                    return $statusEnum->getColor();
                }
            }
        } catch (\Exception $e) {
            // Ignora erro e tenta fallback
        }

        // Fallback: se o status capturado não funcionar, tenta o status atual da entidade
        if (isset($this->entity->status) && $this->entity->status instanceof \App\Contracts\Interfaces\StatusEnumInterface) {
            return $this->entity->status->getColor();
        }

        return config('theme.colors.primary', '#093172');
    }

    /**
     * Define os anexos do e-mail (nenhum por padrão).
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Obtém o título da entidade para o assunto do e-mail.
     *
     * @return string Título da entidade
     */
    private function getEntityTitle(): string
    {
        $entityType = $this->getEntityType();

        return match ($entityType) {
            'budget' => 'Orçamento',
            'service' => 'Serviço',
            'invoice' => 'Fatura',
            'schedule' => 'Agendamento',
            default => 'Notificação',
        };
    }

    /**
     * Obtém o código da entidade.
     *
     * @return string Código da entidade
     */
    private function getEntityCode(): string
    {
        return match ($this->getEntityType()) {
            'budget' => $this->entity->code ?? 'N/A',
            'service' => $this->entity->code ?? 'N/A',
            'invoice' => $this->entity->code ?? 'N/A',
            'schedule' => $this->entity->service?->code ?? (string) $this->entity->id,
            default => (string) ($this->entity->id ?? 'N/A'),
        };
    }

    /**
     * Obtém a descrição da entidade.
     *
     * @return string Descrição da entidade
     */
    private function getEntityDescription(): string
    {
        $description = match ($this->getEntityType()) {
            'budget' => $this->entity->description,
            'service' => $this->entity->description,
            'invoice' => $this->entity->notes,
            'schedule' => $this->entity->service?->description,
            default => null,
        };

        return $description ?: 'Sem descrição detalhada';
    }

    /**
     * Obtém o valor total da entidade.
     *
     * @return string Valor total formatado
     */
    private function getEntityTotal(): string
    {
        $total = match ($this->getEntityType()) {
            'budget' => $this->entity->total ?? 0,
            'service' => $this->entity->total ?? 0,
            'invoice' => $this->entity->total ?? 0,
            default => 0,
        };

        // Garante que o valor seja numérico antes de formatar
        $total = is_numeric($total) ? (float) $total : 0;

        return number_format($total, 2, ',', '.');
    }

    /**
     * Obtém o tipo da entidade.
     *
     * @return string Tipo da entidade
     */
    private function getEntityType(): string
    {
        return strtolower(class_basename($this->entity));
    }

    /**
     * Obtém o status anterior da entidade (se disponível).
     *
     * @return string|null Status anterior
     */
    private function getEntityOldStatus(): ?string
    {
        // Esta informação precisaria ser passada adicionalmente ou buscada do histórico
        return null;
    }

    /**
     * Gera a URL para acessar a entidade.
     *
     * @return string URL da entidade
     */
    private function generateEntityUrl(): string
    {
        if ($this->entityUrl) {
            return $this->entityUrl;
        }

        // Tentar obter URL pública da entidade primeiro
        if (method_exists($this->entity, 'getPublicUrl')) {
            $publicUrl = $this->entity->getPublicUrl();
            if ($publicUrl) {
                return $publicUrl;
            }
        }

        $entityType = $this->getEntityType();

        return match ($entityType) {
            'budget' => config('app.url').'/budgets/'.$this->entity->id,
            'service' => config('app.url').'/services/'.$this->entity->id,
            'invoice' => config('app.url').'/invoices/'.$this->entity->id,
            'schedule' => config('app.url').'/schedules/'.$this->entity->id,
            default => config('app.url'),
        };
    }

    /**
     * Obtém o nome completo do usuário/cliente.
     *
     * @return string Nome completo
     */
    private function getUserName(): string
    {
        if (isset($this->entity->customer)) {
            return $this->entity->customer->name ?? $this->entity->customer->first_name ?? 'Cliente';
        }

        if (isset($this->entity->service->customer)) {
            return $this->entity->service->customer->name ?? $this->entity->service->customer->first_name ?? 'Cliente';
        }

        return 'Cliente';
    }

    /**
     * Obtém o nome amigável do usuário.
     *
     * @return string Nome do usuário
     */
    private function getUserFirstName(): string
    {
        if (isset($this->entity->customer)) {
            $name = $this->entity->customer->name ?? $this->entity->customer->first_name ?? 'Cliente';

            return explode(' ', $name)[0];
        }

        if (isset($this->entity->service->customer)) {
            $name = $this->entity->service->customer->name ?? $this->entity->service->customer->first_name ?? 'Cliente';

            return explode(' ', $name)[0];
        }

        return 'Cliente';
    }

    /**
     * Obtém dados da empresa para o template.
     *
     * @return array Dados da empresa
     */
    private function getCompanyData(): array
    {
        if (! empty($this->company)) {
            return $this->company;
        }

        // Tentar obter o provider com cautela para contexto de fila
        try {
            // Verificar se a entidade tem um relacionamento provider ou tenant
            $provider = null;

            if (method_exists($this->entity, 'provider')) {
                $provider = $this->entity->provider()
                    ->withoutGlobalScopes()
                    ->with([
                        'commonData' => fn ($q) => $q->withoutGlobalScopes(),
                        'contact' => fn ($q) => $q->withoutGlobalScopes(),
                        'address' => fn ($q) => $q->withoutGlobalScopes(),
                    ])
                    ->first();
            }

            if (! $provider && $this->tenant) {
                $provider = $this->tenant->provider()
                    ->withoutGlobalScopes()
                    ->with([
                        'commonData' => fn ($q) => $q->withoutGlobalScopes(),
                        'contact' => fn ($q) => $q->withoutGlobalScopes(),
                        'address' => fn ($q) => $q->withoutGlobalScopes(),
                    ])
                    ->first();
            }

            if ($provider) {
                $commonData = $provider->commonData;
                $contact = $provider->contact;
                $address = $provider->address;

                $addressLine1 = null;
                $addressLine2 = null;
                if ($address) {
                    $addressLine1 = "{$address->address}, {$address->address_number}";
                    if ($address->neighborhood) {
                        $addressLine1 .= " | {$address->neighborhood}";
                    }

                    $addressLine2 = "{$address->city}/{$address->state}";
                    if ($address->cep) {
                        $addressLine2 .= " - CEP: {$address->cep}";
                    }
                }

                $document = null;
                if ($commonData) {
                    $document = $commonData->cnpj
                        ? 'CNPJ: '.\App\Helpers\DocumentHelper::formatCnpj($commonData->cnpj)
                        : ($commonData->cpf ? 'CPF: '.\App\Helpers\DocumentHelper::formatCpf($commonData->cpf) : null);
                }

                return [
                    'company_name' => $commonData?->company_name ?: ($commonData ? trim($commonData->first_name.' '.$commonData->last_name) : ($this->tenant?->name ?? $this->entity->tenant?->name ?? 'Minha Empresa')),
                    'email' => $contact?->email_personal ?: $contact?->email_business,
                    'phone' => $contact?->phone_personal ?: $contact?->phone_business,
                    'address_line1' => $addressLine1,
                    'address_line2' => $addressLine2,
                    'document' => $document,
                ];
            }
        } catch (\Exception $e) {
            // Silenciosamente falha para o fallback se houver erro de DB na fila
        }

        // Fallback para o nome do tenant
        $tenantName = $this->tenant?->name ?? (method_exists($this->entity, 'tenant') ? $this->entity->tenant?->name : null);

        if ($tenantName) {
            return [
                'company_name' => $tenantName,
                'email' => null,
                'phone' => null,
                'address_line1' => null,
                'address_line2' => null,
                'document' => null,
            ];
        }

        return [
            'company_name' => config('app.name', 'Easy Budget'),
            'email' => null,
            'phone' => null,
            'address_line1' => null,
            'address_line2' => null,
            'document' => null,
        ];
    }
}
