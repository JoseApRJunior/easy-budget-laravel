<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class ProviderCredentialEntity extends Entity
{
    public function __construct(
        public readonly int $provider_id,
        public readonly int $tenant_id,
        public readonly string $payment_gateway,
        public readonly string $access_token_encrypted,
        public readonly string $refresh_token_encrypted,
        public readonly ?string $public_key = null,
        public readonly ?string $user_id_gateway = null,
        public readonly ?int $expires_in = null,
        public readonly ?int $id = null,
        public readonly ?DateTime $created_at = new DateTime(),
        public readonly ?DateTime $updated_at = new DateTime(),
    ) {
    }

}
