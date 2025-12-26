<?php

declare(strict_types=1);

namespace App\DTOs\Provider;

use App\DTOs\AbstractDTO;
use Carbon\Carbon;

readonly class ProviderCredentialDTO extends AbstractDTO
{
    public function __construct(
        public int $provider_id,
        public string $type,
        public ?string $access_token = null,
        public ?string $refresh_token = null,
        public ?string $client_id = null,
        public ?string $client_secret = null,
        public ?string $public_key = null,
        public ?Carbon $expires_at = null,
        public ?string $environment = 'sandbox',
        public ?array $metadata = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            provider_id: (int) $data['provider_id'],
            type: $data['type'],
            access_token: $data['access_token'] ?? null,
            refresh_token: $data['refresh_token'] ?? null,
            client_id: $data['client_id'] ?? null,
            client_secret: $data['client_secret'] ?? null,
            public_key: $data['public_key'] ?? null,
            expires_at: isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
            environment: $data['environment'] ?? 'sandbox',
            metadata: is_string($data['metadata'] ?? []) ? json_decode($data['metadata'], true) : ($data['metadata'] ?? []),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'provider_id'   => $this->provider_id,
            'type'          => $this->type,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'public_key'    => $this->public_key,
            'expires_at'    => $this->expires_at?->toDateTimeString(),
            'environment'   => $this->environment,
            'metadata'      => $this->metadata,
            'tenant_id'     => $this->tenant_id,
        ];
    }
}
