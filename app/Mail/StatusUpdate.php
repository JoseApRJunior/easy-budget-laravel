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
            subject: 'Atualização de Status - '.$this->getEntityTitle(),
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
                'urlSuporte' => config('app.url').'/support',
                'tenant' => $this->tenant,
                'entity' => $this->entity,
            ],
        );
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
            'budget' => 'Orçamento '.$this->getEntityCode(),
            'service' => 'Serviço '.$this->getEntityCode(),
            'invoice' => 'Fatura '.$this->getEntityCode(),
            default => ucfirst($entityType).' '.$this->getEntityCode(),
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
            default => $this->entity->id ?? 'N/A',
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
            'budget' => config('app.url').'/budgets/'.$this->entity->id,
            'service' => config('app.url').'/services/'.$this->entity->id,
            'invoice' => config('app.url').'/invoices/'.$this->entity->id,
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

        // Tentar obter dados da empresa através do tenant
        if ($this->tenant) {
            return [
                'company_name' => $this->tenant->name,
                'email' => null,
                'email_business' => null,
                'phone' => null,
                'phone_business' => null,
            ];
        }

        return [
            'company_name' => 'Easy Budget',
            'email' => null,
            'email_business' => null,
            'phone' => null,
            'phone_business' => null,
        ];
    }
}
