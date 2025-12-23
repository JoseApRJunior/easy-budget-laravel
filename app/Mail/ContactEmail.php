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
 * Mailable class para envio de emails de contato.
 *
 * Esta classe implementa o padrão ShouldQueue para processamento assíncrono,
 * garantindo melhor performance e confiabilidade no envio de e-mails de contato.
 */
class ContactEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Dados do contato.
     */
    public array $contactData;

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
     * @param  array  $contactData  Dados do contato
     * @param  Tenant|null  $tenant  Tenant do usuário (opcional)
     * @param  array|null  $company  Dados da empresa (opcional)
     */
    public function __construct(
        array $contactData,
        ?Tenant $tenant = null,
        ?array $company = null,
    ) {
        $this->contactData = $contactData;
        $this->tenant = $tenant;
        $this->company = $company ?? [];
    }

    /**
     * Obtém o envelope da mensagem.
     */
    public function envelope(): Envelope
    {
        $subject = 'Nova mensagem de contato';

        if (isset($this->contactData['subject'])) {
            $subject .= ': '.$this->contactData['subject'];
        }

        return new Envelope(
            to: $this->getSupportEmail(),
            subject: $subject,
            replyTo: $this->contactData['email'] ?? null,
        );
    }

    /**
     * Obtém a definição do conteúdo da mensagem.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
            with: [
                'contactData' => $this->contactData,
                'tenant' => $this->tenant,
                'company' => $this->company,
                'supportEmail' => $this->getSupportEmail(),
                'supportUrl' => $this->getSupportUrl(),
                'appName' => config('app.name', 'Easy Budget'),
                'appUrl' => config('app.url'),
            ],
        );
    }

    /**
     * Obtém os anexos da mensagem.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Obtém o email de suporte baseado no tenant ou configuração global.
     *
     * @return string Email de suporte
     */
    private function getSupportEmail(): string
    {
        // Prioridade: tenant settings -> config global
        if ($this->tenant && isset($this->tenant->settings['support_email']) && ! empty($this->tenant->settings['support_email'])) {
            return $this->tenant->settings['support_email'];
        }

        // Fallback para email de contato do tenant
        if ($this->tenant && isset($this->tenant->settings['contact_email']) && ! empty($this->tenant->settings['contact_email'])) {
            return $this->tenant->settings['contact_email'];
        }

        // Fallback para configuração global
        return config('mail.support_email', 'suporte@easybudget.net.br');
    }

    /**
     * Obtém a URL da página de suporte.
     *
     * @return string URL da página de suporte
     */
    private function getSupportUrl(): string
    {
        return config('app.url').'/support';
    }

    /**
     * Obtém a URL para visualizar um ticket específico.
     *
     * @param  int|null  $ticketId  ID do ticket
     * @return string URL do ticket
     */
    private function getTicketUrl(?int $ticketId = null): string
    {
        if ($ticketId) {
            return config('app.url').'/support/ticket/'.$ticketId;
        }

        return $this->getSupportUrl();
    }

    /**
     * Obtém o nome completo do contato.
     *
     * @return string Nome completo
     */
    public function getContactName(): string
    {
        $firstName = $this->contactData['first_name'] ?? '';
        $lastName = $this->contactData['last_name'] ?? '';

        $fullName = trim($firstName.' '.$lastName);

        return ! empty($fullName) ? $fullName : 'Usuário';
    }

    /**
     * Obtém informações formatadas do contato para o template.
     *
     * @return array Informações do contato
     */
    public function getContactInfo(): array
    {
        return [
            'name' => $this->getContactName(),
            'email' => $this->contactData['email'] ?? 'N/A',
            'subject' => $this->contactData['subject'] ?? 'Sem assunto',
            'message' => $this->contactData['message'] ?? '',
            'created_at' => now()->format('d/m/Y H:i:s'),
        ];
    }
}
