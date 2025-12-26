<?php

declare(strict_types=1);

namespace App\DTOs\User;

use App\DTOs\AbstractDTO;

readonly class UserSettingsDTO extends AbstractDTO
{
    public function __construct(
        public int $user_id,
        public string $theme,
        public string $language,
        public bool $notifications_enabled,
        public ?string $timezone = null,
        public ?string $date_format = null,
        public ?array $dashboard_config = null,
        public ?array $preferences = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            user_id: (int) $data['user_id'],
            theme: $data['theme'] ?? 'light',
            language: $data['language'] ?? 'pt-BR',
            notifications_enabled: isset($data['notifications_enabled']) ? (bool) $data['notifications_enabled'] : true,
            timezone: $data['timezone'] ?? 'America/Sao_Paulo',
            date_format: $data['date_format'] ?? 'd/m/Y',
            dashboard_config: is_string($data['dashboard_config'] ?? []) ? json_decode($data['dashboard_config'], true) : ($data['dashboard_config'] ?? []),
            preferences: is_string($data['preferences'] ?? []) ? json_decode($data['preferences'], true) : ($data['preferences'] ?? []),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'theme' => $this->theme,
            'language' => $this->language,
            'notifications_enabled' => $this->notifications_enabled,
            'timezone' => $this->timezone,
            'date_format' => $this->date_format,
            'dashboard_config' => $this->dashboard_config,
            'preferences' => $this->preferences,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
