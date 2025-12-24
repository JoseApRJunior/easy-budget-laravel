<?php

declare(strict_types=1);

namespace App\DTOs\Common;

use App\DTOs\AbstractDTO;
use Carbon\Carbon;

readonly class CommonDataDTO extends AbstractDTO
{
    public function __construct(
        public string $type,
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?Carbon $birth_date = null,
        public ?string $cpf = null,
        public ?string $company_name = null,
        public ?string $cnpj = null,
        public ?string $description = null,
        public ?int $area_of_activity_id = null,
        public ?int $profession_id = null,
        public ?int $customer_id = null,
        public ?int $provider_id = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            type: $data['type'] ?? 'individual',
            first_name: $data['first_name'] ?? null,
            last_name: $data['last_name'] ?? null,
            birth_date: isset($data['birth_date']) ? Carbon::parse($data['birth_date']) : null,
            cpf: isset($data['cpf']) ? preg_replace('/[^0-9]/', '', (string) $data['cpf']) : null,
            company_name: $data['company_name'] ?? null,
            cnpj: isset($data['cnpj']) ? preg_replace('/[^0-9]/', '', (string) $data['cnpj']) : null,
            description: $data['description'] ?? null,
            area_of_activity_id: isset($data['area_of_activity_id']) ? (int) $data['area_of_activity_id'] : null,
            profession_id: isset($data['profession_id']) ? (int) $data['profession_id'] : null,
            customer_id: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            provider_id: isset($data['provider_id']) ? (int) $data['provider_id'] : null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'type'                => $this->type,
            'first_name'          => $this->first_name,
            'last_name'           => $this->last_name,
            'birth_date'          => $this->birth_date?->toDateString(),
            'cpf'                 => $this->cpf,
            'company_name'        => $this->company_name,
            'cnpj'                => $this->cnpj,
            'description'         => $this->description,
            'area_of_activity_id' => $this->area_of_activity_id,
            'profession_id'       => $this->profession_id,
            'customer_id'         => $this->customer_id,
            'provider_id'         => $this->provider_id,
            'tenant_id'           => $this->tenant_id,
        ];
    }
}
