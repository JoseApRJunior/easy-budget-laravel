<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable class para envio de notificações de faturas.
 *
 * Esta classe implementa o padrão ShouldQueue para processamento assíncrono,
 * garantindo melhor performance e confiabilidade no envio de e-mails.
 */
class InvoiceNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Fatura que será notificada.
     */
    public Invoice $invoice;

    /**
     * Cliente da fatura.
     */
    public Customer $customer;

    /**
     * Tenant do usuário (opcional, para contexto multi-tenant).
     */
    public ?Tenant $tenant;

    /**
     * Dados adicionais da empresa para o template.
     */
    public array $company;

    /**
     * Link público para visualização da fatura.
     */
    public ?string $publicLink;

    /**
     * Mensagem personalizada para a notificação.
     */
    public ?string $customMessage;

    /**
     * Locale para internacionalização (pt-BR, en, etc).
     */
    public string $locale;

    /**
     * Cria uma nova instância da mailable.
     *
     * @param  Invoice  $invoice  Fatura a ser notificada
     * @param  Customer  $customer  Cliente da fatura
     * @param  Tenant|null  $tenant  Tenant do usuário (opcional)
     * @param  array|null  $company  Dados da empresa (opcional)
     * @param  string|null  $publicLink  Link público para visualização (opcional)
     * @param  string|null  $customMessage  Mensagem personalizada (opcional)
     * @param  string  $locale  Locale para internacionalização (opcional, padrão: pt-BR)
     */
    public function __construct(
        Invoice $invoice,
        Customer $customer,
        ?Tenant $tenant = null,
        ?array $company = null,
        ?string $publicLink = null,
        ?string $customMessage = null,
        string $locale = 'pt-BR',
    ) {
        $this->invoice = $invoice;
        $this->customer = $customer;
        $this->tenant = $tenant;
        $this->company = $company ?? [];
        $this->publicLink = $publicLink;
        $this->customMessage = $customMessage;
        $this->locale = $locale;

        // Configurar locale para internacionalização
        app()->setLocale($this->locale);
    }

    /**
     * Define o envelope do e-mail (assunto e metadados).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.invoice.subject', [
                'invoice_code' => $this->invoice->code,
            ], $this->locale),
            tags: ['invoice-notification', 'billing'],
            metadata: [
                'invoice_id' => $this->invoice->id,
                'invoice_code' => $this->invoice->code,
                'customer_id' => $this->customer->id,
                'tenant_id' => $this->tenant?->id,
                'locale' => $this->locale,
                'total_amount' => $this->invoice->total,
            ],
        );
    }

    /**
     * Define o conteúdo do e-mail usando Markdown.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-notification',
            with: [
                'invoice' => $this->invoice,
                'customer' => $this->customer,
                'tenant' => $this->tenant,
                'company' => $this->getCompanyData(),
                'locale' => $this->locale,
                'appName' => config('app.name', 'Easy Budget'),
                'supportEmail' => $this->getSupportEmail(),
                'isSystemEmail' => false,
                'statusColor' => $this->invoice->status->getColor(),
                'customMessage' => $this->customMessage,
                'publicLink' => $this->publicLink ?? $this->generatePublicLink(),
                'invoiceData' => [
                    'code' => $this->invoice->code,
                    'total' => number_format($this->invoice->total, 2, ',', '.'),
                    'subtotal' => number_format($this->invoice->subtotal, 2, ',', '.'),
                    'discount' => number_format($this->invoice->discount, 2, ',', '.'),
                    'due_date' => $this->invoice->due_date?->format('d/m/Y'),
                    'payment_method' => $this->invoice->payment_method,
                    'notes' => $this->invoice->notes,
                    'customer_name' => $this->getCustomerName(),
                ],
            ],
        );
    }

    /**
     * Define os anexos do e-mail (PDF da fatura se disponível).
     */
    public function attachments(): array
    {
        $attachments = [];

        // Adicionar PDF da fatura se existir
        if ($this->invoice->attachment && file_exists(storage_path('app/'.$this->invoice->attachment))) {
            $attachments[] = [
                'path' => storage_path('app/'.$this->invoice->attachment),
                'as' => 'fatura-'.$this->invoice->code.'.pdf',
                'mime' => 'application/pdf',
            ];
        }

        return $attachments;
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

    /**
     * Gera o link público para visualização da fatura.
     *
     * @return string URL pública da fatura
     */
    private function generatePublicLink(): string
    {
        if ($this->publicLink) {
            return $this->publicLink;
        }

        if ($this->invoice->public_hash) {
            return config('app.url').'/invoice/'.$this->invoice->public_hash;
        }

        return config('app.url').'/invoices/'.$this->invoice->id;
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
            $provider = $this->invoice->provider()
                ->withoutGlobalScopes()
                ->with([
                    'commonData' => fn ($q) => $q->withoutGlobalScopes(),
                    'contact' => fn ($q) => $q->withoutGlobalScopes(),
                    'address' => fn ($q) => $q->withoutGlobalScopes(),
                ])
                ->first();

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
                    'company_name' => $commonData?->company_name ?: ($commonData ? trim($commonData->first_name.' '.$commonData->last_name) : ($this->tenant?->name ?? $this->invoice->tenant?->name ?? 'Minha Empresa')),
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
        $tenantName = $this->tenant?->name ?? $this->invoice->tenant?->name;

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
}
