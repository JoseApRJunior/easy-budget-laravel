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
     * Cria uma nova instância da mailable.
     *
     * @param Invoice $invoice Fatura a ser notificada
     * @param Customer $customer Cliente da fatura
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param array|null $company Dados da empresa (opcional)
     * @param string|null $publicLink Link público para visualização (opcional)
     */
    public function __construct(
        Invoice $invoice,
        Customer $customer,
        ?Tenant $tenant = null,
        ?array $company = null,
        ?string $publicLink = null,
    ) {
        $this->invoice    = $invoice;
        $this->customer   = $customer;
        $this->tenant     = $tenant;
        $this->company    = $company ?? [];
        $this->publicLink = $publicLink;
    }

    /**
     * Define o envelope do e-mail (assunto).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sua Fatura ' . $this->invoice->code,
        );
    }

    /**
     * Define o conteúdo do e-mail.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new-invoice',
            with: [
                'invoice'     => [
                    'code'           => $this->invoice->code,
                    'customer_name'  => $this->getCustomerName(),
                    'total'          => $this->invoice->total,
                    'due_date'       => $this->invoice->due_date,
                    'subtotal'       => $this->invoice->subtotal,
                    'discount'       => $this->invoice->discount,
                    'payment_method' => $this->invoice->payment_method,
                    'notes'          => $this->invoice->notes,
                ],
                'public_link' => $this->publicLink ?? $this->generatePublicLink(),
                'company'     => $this->getCompanyData(),
                'tenant'      => $this->tenant,
                'customer'    => $this->customer,
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
        if ( $this->invoice->attachment && file_exists( storage_path( 'app/' . $this->invoice->attachment ) ) ) {
            $attachments[] = [
                'path' => storage_path( 'app/' . $this->invoice->attachment ),
                'as'   => 'fatura-' . $this->invoice->code . '.pdf',
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
        if ( $this->customer->commonData ) {
            return trim( $this->customer->commonData->first_name . ' ' . $this->customer->commonData->last_name );
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
        if ( $this->publicLink ) {
            return $this->publicLink;
        }

        if ( $this->invoice->public_hash ) {
            return config( 'app.url' ) . '/invoice/' . $this->invoice->public_hash;
        }

        return config( 'app.url' ) . '/invoices/' . $this->invoice->id;
    }

    /**
     * Obtém dados da empresa para o template.
     *
     * @return array Dados da empresa
     */
    private function getCompanyData(): array
    {
        if ( !empty( $this->company ) ) {
            return $this->company;
        }

        // Tentar obter dados da empresa através do tenant
        if ( $this->tenant ) {
            return [
                'company_name'   => $this->tenant->name,
                'email'          => null,
                'email_business' => null,
                'phone'          => null,
                'phone_business' => null,
            ];
        }

        return [
            'company_name'   => 'Easy Budget',
            'email'          => null,
            'email_business' => null,
            'phone'          => null,
            'phone_business' => null,
        ];
    }

}
