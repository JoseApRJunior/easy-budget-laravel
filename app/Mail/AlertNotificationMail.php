<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\MonitoringAlertsHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

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
            view: 'emails.alert-notification',
            with: [
                'alert' => $this->alert,
                'severityColor' => $this->alert->getSeverityColor(),
                'severityLabel' => $this->alert->getSeverityLabel(),
                'alertTypeColor' => $this->alert->getAlertTypeColor(),
                'alertTypeLabel' => $this->alert->getAlertTypeLabel(),
                'tenant' => $this->alert->tenant,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
