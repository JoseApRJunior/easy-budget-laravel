<?php

declare(strict_types=1);

namespace App\DTOs\Webhook;

use App\DTOs\AbstractDTO;
use Carbon\Carbon;

readonly class WebhookRequestDTO extends AbstractDTO
{
    public function __construct(
        public string $request_id,
        public string $type,
        public array $payload,
        public bool $processed = false,
        public ?array $response = null,
        public ?string $error_message = null,
        public int $attempts = 0,
        public ?Carbon $last_attempt_at = null,
        public ?Carbon $processed_at = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            request_id: $data['request_id'],
            type: $data['type'],
            payload: is_string($data['payload'] ?? []) ? json_decode($data['payload'], true) : ($data['payload'] ?? []),
            processed: isset($data['processed']) ? (bool) $data['processed'] : false,
            response: is_string($data['response'] ?? null) ? json_decode($data['response'], true) : ($data['response'] ?? null),
            error_message: $data['error_message'] ?? null,
            attempts: isset($data['attempts']) ? (int) $data['attempts'] : 0,
            last_attempt_at: isset($data['last_attempt_at']) ? Carbon::parse($data['last_attempt_at']) : null,
            processed_at: isset($data['processed_at']) ? Carbon::parse($data['processed_at']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'request_id' => $this->request_id,
            'type' => $this->type,
            'payload' => $this->payload,
            'processed' => $this->processed,
            'response' => $this->response,
            'error_message' => $this->error_message,
            'attempts' => $this->attempts,
            'last_attempt_at' => $this->last_attempt_at?->toDateTimeString(),
            'processed_at' => $this->processed_at?->toDateTimeString(),
        ];
    }
}
