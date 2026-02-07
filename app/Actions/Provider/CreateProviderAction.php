<?php

declare(strict_types=1);

namespace App\Actions\Provider;

use App\DTOs\Common\AddressDTO;
use App\DTOs\Common\CommonDataDTO;
use App\DTOs\Common\ContactDTO;
use App\DTOs\Provider\ProviderDTO;
use App\DTOs\Tenant\PlanSubscriptionDTO;
use App\Models\CommonData;
use App\Models\Provider;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\AddressRepository;
use App\Repositories\CommonDataRepository;
use App\Repositories\ContactRepository;
use App\Repositories\PlanRepository;
use App\Repositories\PlanSubscriptionRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\RoleRepository;
use Exception;

class CreateProviderAction
{
    private const ROLE_PROVIDER = 'provider';

    private const PLAN_SLUG_TRIAL = 'trial';

    private const SUBSCRIPTION_STATUS_ACTIVE = 'active';

    private const PAYMENT_METHOD_TRIAL = 'trial';

    private const TRIAL_DAYS = 7;

    public function __construct(
        private ProviderRepository $providerRepository,
        private CommonDataRepository $commonDataRepository,
        private ContactRepository $contactRepository,
        private AddressRepository $addressRepository,
        private RoleRepository $roleRepository,
        private PlanRepository $planRepository,
        private PlanSubscriptionRepository $planSubscriptionRepository
    ) {}

    /**
     * Cria um provider com todos os dados relacionados.
     *
     * @throws Exception
     */
    public function execute(array $userData, User $user, Tenant $tenant): array
    {
        // 1. Criar Provider
        $provider = $this->providerRepository->createFromDTO(new ProviderDTO(
            user_id: $user->id,
            terms_accepted: $userData['terms_accepted'],
            tenant_id: $tenant->id
        ));

        // 2. Criar CommonData vinculado ao Provider
        $this->commonDataRepository->createFromDTO(new CommonDataDTO(
            type: CommonData::TYPE_INDIVIDUAL,
            first_name: $userData['first_name'],
            last_name: $userData['last_name'],
            provider_id: $provider->id,
            tenant_id: $tenant->id
        ));

        // 3. Criar Contact vinculado ao Provider
        $this->contactRepository->createFromDTO(new ContactDTO(
            email_personal: $userData['email_personal'] ?? $userData['email'],
            phone_personal: $userData['phone_personal'] ?? $userData['phone'] ?? null,
            provider_id: $provider->id,
            tenant_id: $tenant->id
        ));

        // 4. Criar Address vinculado ao Provider
        $this->addressRepository->createFromDTO(new AddressDTO(
            provider_id: $provider->id,
            tenant_id: $tenant->id
        ));

        // 5. Atribuir Role de Provider
        $this->assignProviderRole($user, $tenant);

        // 6. Configurar Plano Trial
        $planData = $this->setupTrialSubscription($provider, $tenant);

        return [
            'provider' => $provider,
            'plan' => $planData['plan'],
            'subscription' => $planData['subscription'],
        ];
    }

    /**
     * Atribui a role de provider ao usuário.
     */
    private function assignProviderRole(User $user, Tenant $tenant): void
    {
        $providerRole = $this->roleRepository->findByName(self::ROLE_PROVIDER);

        if (! $providerRole) {
            throw new Exception('Role provider não encontrado.');
        }

        $user->roles()->syncWithoutDetaching([
            $providerRole->id => [
                'tenant_id' => $tenant->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Configura a assinatura do plano trial.
     */
    private function setupTrialSubscription(Provider $provider, Tenant $tenant): array
    {
        $plan = $this->planRepository->findBySlug(self::PLAN_SLUG_TRIAL)
                ?? $this->planRepository->findFreeActive();

        if (! $plan) {
            throw new Exception('Plano trial não encontrado.');
        }

        $subscription = $this->planSubscriptionRepository->createFromDTO(new PlanSubscriptionDTO(
            provider_id: $provider->id,
            plan_id: $plan->id,
            status: self::SUBSCRIPTION_STATUS_ACTIVE,
            transaction_amount: (float) ($plan->price ?? 0.00),
            start_date: now(),
            end_date: now()->addDays(self::TRIAL_DAYS),
            transaction_date: now(),
            payment_method: self::PAYMENT_METHOD_TRIAL,
            payment_id: 'TRIAL_'.uniqid(),
            public_hash: 'TRIAL_HASH_'.uniqid(),
            tenant_id: $tenant->id
        ));

        return [
            'plan' => $plan,
            'subscription' => $subscription,
        ];
    }
}
