<?php

declare(strict_types=1);

namespace App\DTOs\Budget;

use App\DTOs\AbstractDTO;
use Carbon\Carbon;

readonly class BudgetNotificationDTO extends AbstractDTO
{
    public function __construct(
        public int $budget_id,
        public string $type,
        public string $channel,
        public string $recipient,
        public string $subject,
        public string $content,
        public string $status,
        public ?Carbon $sent_at = null,
        public ?string $error_message = null,
        public ?array $metadata = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            budget_id: (int) $data['budget_id'],
            type: $data['type'],
            channel: $data['channel'],
            recipient: $data['recipient'],
            subject: $data['subject'],
            content: $data['content'],
            status: $data['status'],
            sent_at: isset($data['sent_at']) ? Carbon::parse($data['sent_at']) : null,
            error_message: $data['error_message'] ?? null,
            metadata: is_string($data['metadata'] ?? []) ? json_decode($data['metadata'], true) : ($data['metadata'] ?? []),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'budget_id' => $this->budget_id,
            'type' => $this->type,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'subject' => $this->subject,
            'content' => $this->content,
            'status' => $this->status,
            'sent_at' => $this->sent_at?->toDateTimeString(),
            'error_message' => $this->error_message,
            'metadata' => $this->metadata,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
