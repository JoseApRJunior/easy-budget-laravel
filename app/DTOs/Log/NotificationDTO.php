<?php

declare(strict_types=1);

namespace App\DTOs\Log;

use App\DTOs\AbstractDTO;
use App\Helpers\DateHelper;
use Carbon\Carbon;

readonly class NotificationDTO extends AbstractDTO
{
    public function __construct(
        public string $type,
        public string $email,
        public string $message,
        public string $subject,
        public ?Carbon $sent_at = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            type: $data['type'],
            email: $data['email'],
            message: $data['message'],
            subject: $data['subject'],
            sent_at: DateHelper::toCarbon($data['sent_at'] ?? null),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'email' => $this->email,
            'message' => $this->message,
            'subject' => $this->subject,
            'sent_at' => $this->sent_at?->toDateTimeString(),
            'tenant_id' => $this->tenant_id,
        ];
    }
}
