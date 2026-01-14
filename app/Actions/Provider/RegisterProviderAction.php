<?php

declare(strict_types=1);

namespace App\Actions\Provider;

use App\DTOs\Provider\ProviderRegistrationDTO;
use App\Models\Tenant;
use App\Models\User;
use App\Actions\Tenant\CreateTenantAction;
use App\Actions\User\CreateUserAction;
use App\Actions\Provider\CreateProviderAction;
use Illuminate\Support\Facades\DB;
use Exception;

class RegisterProviderAction
{
    public function __construct(
        private CreateTenantAction $createTenantAction,
        private CreateUserAction $createUserAction,
        private CreateProviderAction $createProviderAction
    ) {}

    /**
     * Executa o fluxo completo de registro de um novo provedor.
     *
     * @param ProviderRegistrationDTO $dto
     * @return array
     * @throws Exception
     */
    public function execute(ProviderRegistrationDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $userData = $dto->toArray();

            // 1. Criar Tenant
            $tenant = $this->createTenantAction->execute(
                $userData['first_name'],
                $userData['last_name']
            );

            // 2. Criar Usuário
            $user = $this->createUserAction->execute($userData, $tenant);

            // 3. Criar Provider e dados relacionados
            $providerData = $this->createProviderAction->execute($userData, $user, $tenant);

            return [
                'user' => $user,
                'tenant' => $tenant,
                'provider' => $providerData['provider'],
                'plan' => $providerData['plan'],
                'subscription' => $providerData['subscription'],
            ];
        });
    }
}
