<?php

declare(strict_types=1);

namespace App\DTOs\Log;

use App\DTOs\AbstractDTO;
use Carbon\Carbon;

readonly class EmailLogDTO extends AbstractDTO
{
    public function __construct(
        public int $email_template_id,
        public string $recipient_email,
        public string $subject,
        public string $sender_email,
        public string $status,
        public ?string $recipient_name = null,
        public ?string $sender_name = null,
        public ?Carbon $sent_at = null,
        public ?Carbon $opened_at = null,
        public ?Carbon $clicked_at = null,
        public ?Carbon $bounced_at = null,
        public ?string $error_message = null,
        public ?array $metadata = null,
        public ?string $tracking_id = null,
        public ?string $ip_address = null,
        public ?string $user_agent = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            email_template_id: (int) $data['email_template_id'],
            recipient_email: $data['recipient_email'],
            subject: $data['subject'],
            sender_email: $data['sender_email'],
            status: $data['status'],
            recipient_name: $data['recipient_name'] ?? null,
            sender_name: $data['sender_name'] ?? null,
            sent_at: isset($data['sent_at']) ? Carbon::parse($data['sent_at']) : null,
            opened_at: isset($data['opened_at']) ? Carbon::parse($data['opened_at']) : null,
            clicked_at: isset($data['clicked_at']) ? Carbon::parse($data['clicked_at']) : null,
            bounced_at: isset($data['bounced_at']) ? Carbon::parse($data['bounced_at']) : null,
            error_message: $data['error_message'] ?? null,
            metadata: is_string($data['metadata'] ?? []) ? json_decode($data['metadata'], true) : ($data['metadata'] ?? []),
            tracking_id: $data['tracking_id'] ?? null,
            ip_address: $data['ip_address'] ?? null,
            user_agent: $data['user_agent'] ?? null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'email_template_id' => $this->email_template_id,
            'recipient_email'   => $this->recipient_email,
            'subject'           => $this->subject,
            'sender_email'      => $this->sender_email,
            'status'            => $this->status,
            'recipient_name'    => $this->recipient_name,
            'sender_name'       => $this->sender_name,
            'sent_at'           => $this->sent_at?->toDateTimeString(),
            'opened_at'         => $this->opened_at?->toDateTimeString(),
            'clicked_at'        => $this->clicked_at?->toDateTimeString(),
            'bounced_at'        => $this->bounced_at?->toDateTimeString(),
            'error_message'     => $this->error_message,
            'metadata'          => $this->metadata,
            'tracking_id'       => $this->tracking_id,
            'ip_address'        => $this->ip_address,
            'user_agent'        => $this->user_agent,
            'tenant_id'         => $this->tenant_id,
        ];
    }
}
