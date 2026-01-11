<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Budget;
use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable class para envio de notificações relacionadas a orçamentos.
 *
 * Esta classe implementa o padrão ShouldQueue para processamento assíncrono,
 * garantindo melhor performance e confiabilidade no envio de e-mails.
 * Usa templates Markdown profissionais com internacionalização.
 */
class BudgetNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Orçamento relacionado à notificação.
     */
    public Budget $budget;

    /**
     * Cliente do orçamento.
     */
    public Customer $customer;

    /**
     * Tipo de notificação (created, updated, approved, rejected, etc).
     */
    public string $notificationType;

    /**
     * Tenant do usuário (opcional, para contexto multi-tenant).
     */
    public ?Tenant $tenant;

    /**
     * Dados adicionais da empresa para o template.
     */
    public array $company;

    /**
     * URL pública para visualização do orçamento.
     */
    public ?string $publicUrl;

    /**
     * Mensagem personalizada para a notificação.
     */
    public ?string $customMessage;

    /**
     * Cria uma nova instância da mailable.
     *
     * @param  Budget  $budget  Orçamento relacionado
     * @param  Customer  $customer  Cliente do orçamento
     * @param  string  $notificationType  Tipo de notificação
     * @param  Tenant|null  $tenant  Tenant do usuário (opcional)
     * @param  array|null  $company  Dados da empresa (opcional)
     * @param  string|null  $publicUrl  URL pública do orçamento (opcional)
     * @param  string|null  $customMessage  Mensagem personalizada (opcional)
     * @param  string  $locale  Locale para internacionalização (opcional, padrão: pt-BR)
     */
    public function __construct(
        Budget $budget,
        Customer $customer,
        string $notificationType,
        ?Tenant $tenant = null,
        ?array $company = null,
        ?string $publicUrl = null,
        ?string $customMessage = null,
        string $locale = 'pt-BR',
    ) {
        $this->budget = $budget;
        $this->customer = $customer;
        $this->notificationType = $notificationType;
        $this->tenant = $tenant;
        $this->company = $company ?? [];
        $this->publicUrl = $publicUrl;
        $this->customMessage = $customMessage;

        if ($locale) {
            $this->locale($locale);
        }

        // Configurar locale para internacionalização
        app()->setLocale($this->locale ?? 'pt-BR');
    }

    /**
     * Define o envelope do e-mail (assunto e metadados).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->generateSubject(),
            tags: ['budget-notification', $this->notificationType],
            metadata: [
                'budget_id' => $this->budget->id,
                'budget_code' => $this->budget->code,
                'customer_id' => $this->customer->id,
                'tenant_id' => $this->tenant?->id,
                'locale' => $this->locale,
                'notification_type' => $this->notificationType,
            ],
        );
    }

    /**
     * Define o conteúdo do e-mail usando Markdown.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.budget-notification',
            with: [
                'budget' => $this->budget,
                'customer' => $this->customer,
                'notificationType' => $this->notificationType,
                'budgetUrl' => $this->generateBudgetUrl(),
                'tenant' => $this->tenant,
                'company' => $this->getCompanyData(),
                'locale' => $this->locale,
                'appName' => config('app.name', 'Easy Budget'),
                'supportEmail' => $this->getSupportEmail(),
                'isSystemEmail' => false,
                'customMessage' => $this->customMessage,
                'budgetData' => [
                    'code' => $this->budget->code,
                    'total' => number_format((float) $this->budget->total, 2, ',', '.'),
                    'discount' => number_format((float) $this->budget->discount, 2, ',', '.'),
                    'due_date' => $this->budget->due_date?->format('d/m/Y'),
                    'description' => $this->budget->description ?? 'Orçamento sem descrição',
                    'status' => $this->budget->status->getDescription(),
                    'customer_name' => $this->getCustomerName(),
                ],
            ],
        );
    }

    /**
     * Define os anexos do e-mail (PDF do orçamento se disponível).
     */
    public function attachments(): array
    {
        $attachments = [];

        // Adicionar PDF do orçamento se existir
        if ($this->budget->attachment && file_exists(storage_path('app/'.$this->budget->attachment))) {
            $attachments[] = [
                'path' => storage_path('app/'.$this->budget->attachment),
                'as' => 'orcamento-'.$this->budget->code.'.pdf',
                'mime' => 'application/pdf',
            ];
        }

        return $attachments;
    }

    /**
     * Gera o assunto do e-mail baseado no tipo de notificação.
     *
     * @return string Assunto do e-mail
     */
    private function generateSubject(): string
    {
        $budgetTitle = 'Orçamento '.$this->budget->code;

        return match ($this->notificationType) {
            'created' => __('emails.budget.subject.created', ['budget' => $budgetTitle], $this->locale),
            'updated' => __('emails.budget.subject.updated', ['budget' => $budgetTitle], $this->locale),
            'approved' => __('emails.budget.subject.approved', ['budget' => $budgetTitle], $this->locale),
            'rejected' => __('emails.budget.subject.rejected', ['budget' => $budgetTitle], $this->locale),
            'sent' => __('emails.budget.subject.sent', ['budget' => $budgetTitle], $this->locale),
            'expired' => __('emails.budget.subject.expired', ['budget' => $budgetTitle], $this->locale),
            default => __('emails.budget.subject.default', ['budget' => $budgetTitle], $this->locale),
        };
    }

    /**
     * Gera a URL para acesso ao orçamento.
     *
     * @return string URL do orçamento
     */
    private function generateBudgetUrl(): string
    {
        if ($this->publicUrl) {
            return $this->publicUrl;
        }

        if ($this->budget->pdf_verification_hash) {
            return config('app.url').'/budget/'.$this->budget->pdf_verification_hash;
        }

        return config('app.url').'/budgets/'.$this->budget->id;
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
            $provider = $this->budget->provider()
                ->withoutGlobalScopes()
                ->with([
                    'commonData' => fn($q) => $q->withoutGlobalScopes(),
                    'contact' => fn($q) => $q->withoutGlobalScopes(),
                    'address' => fn($q) => $q->withoutGlobalScopes(),
                ])
                ->first();

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
                    'company_name' => $commonData?->company_name ?: ($commonData ? trim($commonData->first_name.' '.$commonData->last_name) : ($this->tenant?->name ?? $this->budget->tenant?->name ?? 'Minha Empresa')),
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

        // Fallback para o nome do tenant se não houver provider
        $tenantName = $this->tenant?->name ?? $this->budget->tenant?->name;
        
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

    /**
     * Obtém o e-mail de suporte.
     *
     * @return string E-mail de suporte
     */
    private function getSupportEmail(): string
    {
        // Tentar obter e-mail de suporte do tenant
        if ($this->tenant && isset($this->tenant->settings['support_email'])) {
            return $this->tenant->settings['support_email'];
        }

        // E-mail padrão de suporte
        return config('mail.support_email', 'suporte@easybudget.net.br');
    }

    /**
     * Obtém o nome do cliente.
     *
     * @return string Nome do cliente
     */
    private function getCustomerName(): string
    {
        if ($this->customer->commonData) {
            return trim($this->customer->commonData->first_name.' '.$this->customer->commonData->last_name);
        }

        return 'Cliente';
    }
}
