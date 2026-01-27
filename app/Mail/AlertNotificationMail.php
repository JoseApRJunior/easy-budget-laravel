<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\MonitoringAlertsHistory;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    private ?array $companyData = null;

    public function __construct(
        public MonitoringAlertsHistory $alert,
        public string $subject
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.system.alert-notification',
            with: [
                'alert' => $this->alert,
                'severityColor' => $this->alert->getSeverityColor(),
                'severityLabel' => $this->alert->getSeverityLabel(),
                'alertTypeColor' => $this->alert->getAlertTypeColor(),
                'alertTypeLabel' => $this->alert->getAlertTypeLabel(),
                'tenant' => $this->alert->tenant,
                'company' => $this->getCompanyData(),
                'isSystemEmail' => true,
                'statusColor' => $this->alert->getSeverityColor(),
                'appName' => config('app.name', 'Easy Budget'),
                'appUrl' => config('app.url'),
            ]
        );
    }

    private function getCompanyData(): array
    {
        if ($this->companyData !== null) {
            return $this->companyData;
        }

        $tenant = $this->alert->tenant;

        if ($tenant) {
            try {
                $tenantData = Tenant::withoutGlobalScopes()
                    ->with(['provider.commonData', 'provider.contact'])
                    ->find($tenant->id);

                if ($tenantData && $tenantData->provider && $tenantData->provider->commonData) {
                    $common = $tenantData->provider->commonData;
                    $contact = $tenantData->provider->contact;

                    $this->companyData = [
                        'company_name' => $common->company_name ?? $tenantData->name,
                        'address_line1' => $common->address_line1,
                        'address_line2' => $common->address_line2,
                        'city' => $common->city,
                        'state' => $common->state,
                        'postal_code' => $common->postal_code,
                        'phone' => $contact?->phone_business ?? $contact?->phone_personal,
                        'email' => $contact?->email_business ?? $contact?->email_personal,
                    ];

                    return $this->companyData;
                }
            } catch (\Exception $e) {
                // Fallback silencioso
            }

            return [
                'company_name' => $tenant->name,
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

    public function attachments(): array
    {
        return [];
    }
}
