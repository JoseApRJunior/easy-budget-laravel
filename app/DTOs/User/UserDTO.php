<?php

declare(strict_types=1);

namespace App\DTOs\User;

use App\DTOs\AbstractDTO;

readonly class UserDTO extends AbstractDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        public bool $is_active = true,
        public ?int $tenant_id = null,
        public ?string $avatar = null,
        public ?string $logo = null,
        public ?array $extra_links = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'] ?? null,
            is_active: (bool) ($data['is_active'] ?? true),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            avatar: $data['avatar'] ?? null,
            logo: $data['logo'] ?? null,
            extra_links: $data['extra_links'] ?? null
        );
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'tenant_id' => $this->tenant_id,
            'avatar' => $this->avatar,
            'logo' => $this->logo,
            'extra_links' => $this->extra_links,
        ];

        if ($this->password) {
            $data['password'] = $this->password;
        }

        return $data;
    }
}
