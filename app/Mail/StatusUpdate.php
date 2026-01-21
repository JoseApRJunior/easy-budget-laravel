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
        return new Envelope(
            subject: 'Atualização de Status - ' . $this->getEntityTitle(),
        );
    }

    /**
     * Define o conteúdo do e-mail.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notification-status',
            with: [
                'emailData' => [
                    'first_name' => $this->getUserFirstName(),
                    'service_code' => $this->getEntityCode(),
                    'service_status_name' => $this->statusName,
                    'service_description' => $this->getEntityDescription(),
                    'service_total' => $this->getEntityTotal(),
                    'link' => $this->entityUrl ?? $this->generateEntityUrl(),
                    'entity_type' => $this->getEntityType(),
                    'old_status' => $this->getEntityOldStatus(),
                    'new_status' => $this->status,
                    'status_changed_at' => now()->format('d/m/Y H:i'),
                ],
                'company' => $this->getCompanyData(),
                'urlSuporte' => config('app.url') . '/support',
                'tenant' => $this->tenant,
                'entity' => $this->entity,
                'isSystemEmail' => false,
                'statusColor' => $this->getStatusColor(),
            ],
        );
    }

    /**
     * Obtém a cor do status da entidade.
     *
     * @return string Cor hexadecimal
     */
    private function getStatusColor(): string
    {
        // Se a entidade tem o status atual como enum
        if (isset($this->entity->status) && $this->entity->status instanceof \App\Contracts\Interfaces\StatusEnumInterface) {
            return $this->entity->status->getColor();
        }

        // Tentar obter do enum baseado no tipo da entidade e no valor da string de status
        try {
            $entityType = $this->getEntityType();
            $enumClass = match ($entityType) {
                'Orçamento' => \App\Enums\BudgetStatus::class,
                'Fatura' => \App\Enums\InvoiceStatus::class,
                'Serviço' => \App\Enums\ServiceStatus::class,
                'Agendamento' => \App\Enums\ScheduleStatus::class,
                default => null,
            };

            if ($enumClass && method_exists($enumClass, 'from')) {
                $statusEnum = $enumClass::from($this->status);
                if ($statusEnum instanceof \App\Contracts\Interfaces\StatusEnumInterface) {
                    return $statusEnum->getColor();
                }
            }
        } catch (\Exception $e) {
            // Ignora erro e usa padrão
        }

        return '#0d6efd';
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
            'budget' => 'Orçamento ' . $this->getEntityCode(),
            'service' => 'Serviço ' . $this->getEntityCode(),
            'invoice' => 'Fatura ' . $this->getEntityCode(),
            'schedule' => 'Agendamento ' . $this->getEntityCode(),
            default => ucfirst($entityType) . ' ' . $this->getEntityCode(),
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
        return match ($this->getEntityType()) {
            'budget' => $this->entity->description ?? 'Orçamento sem descrição',
            'service' => $this->entity->description ?? 'Serviço sem descrição',
            'invoice' => $this->entity->notes ?? 'Fatura sem observações',
            'schedule' => ($this->entity->start_date_time?->format('d/m/Y H:i') ?? 'N/A') . ' em ' . ($this->entity->location ?? 'Local não definido'),
            default => 'Entidade atualizada',
        };
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

        $entityType = $this->getEntityType();

        return match ($entityType) {
            'budget' => config('app.url') . '/budgets/' . $this->entity->id,
            'service' => config('app.url') . '/services/' . $this->entity->id,
            'invoice' => config('app.url') . '/invoices/' . $this->entity->id,
            default => config('app.url'),
        };
    }

    /**
     * Obtém o primeiro nome do usuário (se disponível através da entidade).
     *
     * @return string Nome do usuário ou padrão
     */
    private function getUserFirstName(): string
    {
        // Tentar obter o nome do usuário através de relacionamentos
        if (method_exists($this->entity, 'user') && $this->entity->user) {
            return $this->entity->user->name ?? 'Usuário';
        }

        if (method_exists($this->entity, 'customer') && $this->entity->customer) {
            return $this->entity->customer->first_name ?? 'Cliente';
        }

        return 'Usuário';
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
                        'commonData' => fn($q) => $q->withoutGlobalScopes(),
                        'contact' => fn($q) => $q->withoutGlobalScopes(),
                        'address' => fn($q) => $q->withoutGlobalScopes(),
                    ])
                    ->first();
            }

            if (! $provider && $this->tenant) {
                $provider = $this->tenant->provider()
                    ->withoutGlobalScopes()
                    ->with([
                        'commonData' => fn($q) => $q->withoutGlobalScopes(),
                        'contact' => fn($q) => $q->withoutGlobalScopes(),
                        'address' => fn($q) => $q->withoutGlobalScopes(),
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
                        ? 'CNPJ: ' . \App\Helpers\DocumentHelper::formatCnpj($commonData->cnpj)
                        : ($commonData->cpf ? 'CPF: ' . \App\Helpers\DocumentHelper::formatCpf($commonData->cpf) : null);
                }

                return [
                    'company_name' => $commonData?->company_name ?: ($commonData ? trim($commonData->first_name . ' ' . $commonData->last_name) : ($this->tenant?->name ?? $this->entity->tenant?->name ?? 'Minha Empresa')),
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
