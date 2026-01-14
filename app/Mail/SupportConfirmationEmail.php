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
 * Mailable para envio de email de confirmação de contato para o usuário.
 *
 * Esta classe envia um email automático confirmando que a mensagem
 * de contato foi recebida e será processada em breve.
 */
class SupportConfirmationEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Dados da confirmação.
     */
    public array $confirmationData;

    /**
     * Tenant do usuário (opcional).
     */
    public ?Tenant $tenant;

    /**
     * Cria uma nova instância da mailable.
     *
     * @param  array  $confirmationData  Dados da confirmação
     * @param  Tenant|null  $tenant  Tenant do usuário (opcional)
     */
    public function __construct(
        array $confirmationData,
        ?Tenant $tenant = null,
    ) {
        $this->confirmationData = $confirmationData;
        $this->tenant = $tenant;
    }

    /**
     * Obtém o envelope da mensagem.
     */
    public function envelope(): Envelope
    {
        $subject = 'Confirmação de recebimento - '.($this->confirmationData['subject'] ?? 'Mensagem de contato');

        return new Envelope(
            to: $this->confirmationData['email'],
            subject: $subject,
        );
    }

    /**
     * Obtém a definição do conteúdo da mensagem.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.support_confirmation',
            with: [
                'confirmationData' => $this->confirmationData,
                'tenant' => $this->tenant,
                'company' => $this->getCompanyData(),
                'isSystemEmail' => false,
                'statusColor' => '#0d6efd',
                'appName' => config('app.name', 'Easy Budget'),
                'appUrl' => config('app.url'),
            ],
        );
    }

    /**
     * Obtém dados da empresa para o template.
     */
    private function getCompanyData(): array
    {
        // Tentar obter dados da empresa através do tenant com carregamento antecipado
        if ($this->tenant) {
            try {
                // Carregar relações necessárias sem scopes globais para evitar problemas em filas
                $tenantData = Tenant::withoutGlobalScopes()
                    ->with(['provider.commonData', 'provider.contact'])
                    ->find($this->tenant->id);

                if ($tenantData && $tenantData->provider && $tenantData->provider->commonData) {
                    $common = $tenantData->provider->commonData;
                    $contact = $tenantData->provider->contact;

                    return [
                        'company_name' => $common->company_name ?? $tenantData->name,
                        'address_line1' => $common->address_line1,
                        'address_line2' => $common->address_line2,
                        'city' => $common->city,
                        'state' => $common->state,
                        'postal_code' => $common->postal_code,
                        'phone' => $contact?->phone_business ?? $contact?->phone_personal,
                        'email' => $contact?->email_business ?? $contact?->email_personal,
                    ];
                }
            } catch (\Exception $e) {
                // Fallback silencioso em caso de erro no banco
            }

            return [
                'company_name' => $this->tenant->name,
                'email' => null,
                'phone' => null,
            ];
        }

        return [
            'company_name' => config('app.name', 'Easy Budget'),
            'email' => null,
            'phone' => null,
        ];
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
}
