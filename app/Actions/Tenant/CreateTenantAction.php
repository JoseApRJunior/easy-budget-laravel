<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\DTOs\Tenant\TenantDTO;
use App\Models\Tenant;
use App\Repositories\TenantRepository;
use Illuminate\Support\Str;

class CreateTenantAction
{
    public function __construct(
        private TenantRepository $tenantRepository
    ) {}

    /**
     * Cria um novo tenant com nome único.
     */
    public function execute(string $firstName, string $lastName): Tenant
    {
        $tenantName = $this->generateUniqueTenantName($firstName, $lastName);

        return $this->tenantRepository->createFromDTO(new TenantDTO(
            name: $tenantName,
            is_active: true
        ));
    }

    /**
     * Gera um nome único para o tenant.
     */
    private function generateUniqueTenantName(string $firstName, string $lastName): string
    {
        $baseName = Str::slug($firstName.'-'.$lastName);
        $tenantName = $baseName;
        $counter = 1;

        while ($this->tenantRepository->findByName($tenantName)) {
            $tenantName = $baseName.'-'.$counter;
            $counter++;
        }

        return $tenantName;
    }
}
