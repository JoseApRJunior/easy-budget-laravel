<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable class para envio de respostas de suporte.
 *
 * Esta classe implementa o padrão ShouldQueue para processamento assíncrono,
 * garantindo melhor performance e confiabilidade no envio de e-mails.
 */
class SupportResponse extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Dados do ticket de suporte.
     */
    public array $ticket;

    /**
     * Resposta do suporte.
     */
    public string $response;

    /**
     * Tenant do usuário (opcional, para contexto multi-tenant).
     */
    public ?Tenant $tenant;

    /**
     * Dados adicionais da empresa para o template.
     */
    public array $company;

    /**
     * Cria uma nova instância da mailable.
     *
     * @param  array  $ticket  Dados do ticket de suporte
     * @param  string  $response  Resposta do suporte
     * @param  Tenant|null  $tenant  Tenant do usuário (opcional)
     * @param  array|null  $company  Dados da empresa (opcional)
     */
    public function __construct(
        array $ticket,
        string $response,
        ?Tenant $tenant = null,
        ?array $company = null,
    ) {
        $this->ticket = $ticket;
        $this->response = $response;
        $this->tenant = $tenant;
        $this->company = $company ?? [];
    }

    /**
     * Define o envelope do e-mail (assunto).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Resposta do Suporte - '.($this->ticket['subject'] ?? 'Ticket #'.($this->ticket['id'] ?? '')),
        );
    }

    /**
     * Define o conteúdo do e-mail.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.support',
            with: [
                'ticket' => [
                    'id' => $this->ticket['id'] ?? null,
                    'subject' => $this->ticket['subject'] ?? 'Sem assunto',
                    'status' => $this->ticket['status'] ?? 'open',
                    'priority' => $this->ticket['priority'] ?? 'normal',
                    'created_at' => $this->ticket['created_at'] ?? now()->format('d/m/Y H:i:s'),
                    'updated_at' => $this->ticket['updated_at'] ?? now()->format('d/m/Y H:i:s'),
                    'category' => $this->ticket['category'] ?? 'Geral',
                    'first_name' => $this->ticket['first_name'] ?? 'Usuário',
                    'last_name' => $this->ticket['last_name'] ?? '',
                    'email' => $this->ticket['email'] ?? '',
                    'original_message' => $this->ticket['message'] ?? '',
                ],
                'response' => $this->response,
                'response_date' => now()->format('d/m/Y H:i:s'),
                'company' => $this->getCompanyData(),
                'tenant' => $this->tenant,
                'support_email' => $this->getSupportEmail(),
                'ticket_url' => $this->generateTicketUrl(),
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
     * Gera a URL do ticket para acesso direto.
     *
     * @return string URL do ticket
     */
    private function generateTicketUrl(): string
    {
        $ticketId = $this->ticket['id'] ?? null;

        if (! $ticketId) {
            return config('app.url').'/support';
        }

        return config('app.url').'/support/ticket/'.$ticketId;
    }
}
