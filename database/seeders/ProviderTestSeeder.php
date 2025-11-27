<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Helpers\DocumentGeneratorHelper;
use App\Models\Address;
use App\Models\AreaOfActivity;
use App\Models\BusinessData;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Profession;
use App\Models\Provider;
use App\Models\Role;
use App\Models\SystemSettings;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProviderTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏢 Criando prestadores de teste...');

        for ($i = 1; $i <= 2; $i++) {
            $provider = $this->createProvider('company', $i);
            $this->createCustomersForProvider($provider, $i);
        }

        for ($i = 3; $i <= 4; $i++) {
            $provider = $this->createProvider('individual', $i);
            $this->createCustomersForProvider($provider, $i);
        }

        $this->command->info('✅ Prestadores de teste criados com sucesso!');
    }

    private function createProvider(string $type, int $index): Provider
    {
        $tenant = Tenant::firstOrCreate(
            ['name' => $type === 'company' ? "Provider PJ {$index}" : "Provider PF {$index}"],
            ['is_active' => true],
        );

        $user = User::firstOrCreate(
            ['email' => "provider{$index}@test.com"],
            [
                'tenant_id'         => $tenant->id,
                'name'              => $type === 'company' ? "Provider PJ  {$index}" : "Provider PF {$index}",
                'password'          => Hash::make('Password1@'),
                'google_id'         => null,
                'avatar'            => null,
                'google_data'       => null,
                'is_active'         => true,
                'logo'              => null,
                'email_verified_at' => now(),
                'extra_links'       => null,
            ],
        );

        $provider = Provider::firstOrCreate(
            ['user_id' => $user->id, 'tenant_id' => $tenant->id],
            ['terms_accepted' => true],
        );

        CommonData::firstOrCreate(
            ['provider_id' => $provider->id],
            [
                'tenant_id'           => $tenant->id,
                'type'                => $type,
                'first_name'          => $type === 'individual' ? "Provider {$index}" : null,
                'last_name'           => $type === 'individual' ? "Teste" : null,
                'birth_date'          => $type === 'individual' ? '1985-01-01' : null,
                'cpf'                 => $type === 'individual' ? $this->generateCPF($index) : null,
                'company_name'        => $type === 'company' ? "Empresa Teste {$index} Ltda" : null,
                'cnpj'                => $type === 'company' ? $this->generateCNPJ($index) : null,
                'description'         => "Descrição do provider {$index}",
                'area_of_activity_id' => AreaOfActivity::first()?->id,
                'profession_id'       => Profession::first()?->id,
            ],
        );

        Contact::firstOrCreate(
            ['provider_id' => $provider->id],
            [
                'tenant_id'      => $tenant->id,
                'email_personal' => "provider{$index}@test.com",
                'phone_personal' => DocumentGeneratorHelper::generateValidPhone(),
                'email_business' => $type === 'company' ? "comercial{$index}@test.com" : null,
                'phone_business' => $type === 'company' ? DocumentGeneratorHelper::generateValidPhone() : null,
                'website'        => $type === 'company' ? "https://empresa{$index}.com" : null,
            ],
        );

        Address::firstOrCreate(
            ['provider_id' => $provider->id],
            [
                'tenant_id'      => $tenant->id,
                'address'        => "Rua Teste {$index}",
                'address_number' => (string) ($index * 100),
                'neighborhood'   => "Bairro {$index}",
                'city'           => 'São Paulo',
                'state'          => 'SP',
                'cep'            => sprintf('%08d', $index),
            ],
        );

        if ($type === 'company') {
            BusinessData::firstOrCreate(
                ['provider_id' => $provider->id],
                [
                    'tenant_id'              => $tenant->id,
                    'fantasy_name'           => "Empresa {$index}",
                    'founding_date'          => '2020-01-01',
                    'state_registration'     => "123456{$index}",
                    'municipal_registration' => "789{$index}",
                    'industry'               => 'Tecnologia',
                    'company_size'           => 'pequena',
                    'notes'                  => "Observações da empresa {$index}",
                ],
            );
        }

        $role = Role::where('name', 'Provider')->first();
        if ($role) {
            UserRole::firstOrCreate([
                'user_id'   => $user->id,
                'role_id'   => $role->id,
                'tenant_id' => $tenant->id,
            ]);
        }

        $plan = Plan::first();
        if ($plan) {
            PlanSubscription::firstOrCreate(
                ['provider_id' => $provider->id],
                [
                    'plan_id'            => $plan->id,
                    'tenant_id'          => $tenant->id,
                    'status'             => 'active',
                    'transaction_amount' => 0.00,
                    'start_date'         => now(),
                    'end_date'           => now()->addYear(),
                    'payment_method'     => 'trial',
                    'payment_id'         => "TEST_{$index}",
                    'public_hash'        => "HASH_{$index}",
                ],
            );
        }

        // Criar configurações do usuário
        UserSettings::firstOrCreate(
            ['user_id' => $user->id, 'tenant_id' => $tenant->id],
            [
                'avatar'                    => null,
                'full_name'                 => $user->name,
                'bio'                       => "Configurações do provider {$index}",
                'phone'                     => null,
                'birth_date'                => $type === 'individual' ? '1985-01-01' : null,
                'social_facebook'           => null,
                'social_twitter'            => null,
                'social_linkedin'           => null,
                'social_instagram'          => null,
                'theme'                     => 'auto',
                'primary_color'             => '#3B82F6',
                'layout_density'            => 'normal',
                'sidebar_position'          => 'left',
                'animations_enabled'        => true,
                'sound_enabled'             => true,
                'email_notifications'       => true,
                'transaction_notifications' => true,
                'weekly_reports'            => false,
                'security_alerts'           => true,
                'newsletter_subscription'   => false,
                'push_notifications'        => false,
                'custom_preferences'        => json_encode(['auto_save' => true, 'compact_mode' => false, 'show_tips' => true]),
            ],
        );

        // Recarregar provider com relacionamentos
        $provider->load(['commonData', 'contact', 'address']);
        $commonData = $provider->commonData;
        $contact    = $provider->contact;
        $address    = $provider->address;

        SystemSettings::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'company_name'                => $commonData->company_name ?? $commonData->first_name . ' ' . $commonData->last_name,
                'contact_email'               => $contact->email_business ?? $contact->email_personal,
                'phone'                       => $contact->phone_business ?? $contact->phone_personal,
                'website'                     => $contact->website,
                'logo'                        => null,
                'currency'                    => 'BRL',
                'timezone'                    => 'America/Sao_Paulo',
                'language'                    => 'pt-BR',
                'address_street'              => $address->address,
                'address_number'              => $address->address_number,
                'address_complement'          => null,
                'address_neighborhood'        => $address->neighborhood,
                'address_city'                => $address->city,
                'address_state'               => $address->state,
                'address_zip_code'            => $address->cep,
                'address_country'             => 'Brasil',
                'maintenance_mode'            => false,
                'maintenance_message'         => null,
                'registration_enabled'        => true,
                'email_verification_required' => true,
                'session_lifetime'            => 120,
                'max_login_attempts'          => 5,
                'lockout_duration'            => 15,
                'allowed_file_types'          => json_encode(['image/jpeg', 'image/png', 'image/gif', 'application/pdf']),
                'max_file_size'               => 2048,
                'system_preferences'          => json_encode(['auto_save' => true, 'compact_mode' => false, 'show_tips' => true]),
            ],
        );

        $this->command->info("   ✓ Provider {$index} ({$type}) criado");
        return $provider;
    }

    private function createCustomersForProvider(Provider $provider, int $providerIndex): void
    {
        for ($i = 3; $i <= 4; $i++) {
            $this->createCustomer($provider, 'company', $providerIndex, $i);
        }

        for ($i = 5; $i <= 6; $i++) {
            $this->createCustomer($provider, 'individual', $providerIndex, $i);
        }
    }

    private function createCustomer(Provider $provider, string $type, int $providerIndex, int $customerIndex): void
    {
        $customer = Customer::create([
            'tenant_id' => $provider->tenant_id,
            'status'    => 'active',
        ]);

        CommonData::create([
            'tenant_id'           => $provider->tenant_id,
            'customer_id'         => $customer->id,
            'type'                => $type,
            'first_name'          => $type === 'individual' ? "Cliente {$customerIndex}" : null,
            'last_name'           => $type === 'individual' ? "Teste" : null,
            'birth_date'          => $type === 'individual' ? '1990-01-01' : null,
            'cpf'                 => $type === 'individual' ? $this->generateCPF($providerIndex * 100 + $customerIndex) : null,
            'company_name'        => $type === 'company' ? "Cliente Empresa {$customerIndex} Ltda" : null,
            'cnpj'                => $type === 'company' ? $this->generateCNPJ($providerIndex * 100 + $customerIndex) : null,
            'description'         => "Cliente {$customerIndex} do provider {$providerIndex}",
            'area_of_activity_id' => AreaOfActivity::first()?->id,
            'profession_id'       => $type === 'individual' ? Profession::first()?->id : null,
        ]);

        Contact::create([
            'tenant_id'      => $provider->tenant_id,
            'customer_id'    => $customer->id,
            'email_personal' => "cliente{$providerIndex}_{$customerIndex}@test.com",
            'phone_personal' => DocumentGeneratorHelper::generateValidPhone(),
            'email_business' => $type === 'company' ? "empresa{$providerIndex}_{$customerIndex}@test.com" : null,
            'phone_business' => $type === 'company' ? DocumentGeneratorHelper::generateValidPhone() : null,
            'website'        => $type === 'company' ? "https://cliente{$customerIndex}.com" : null,
        ]);

        Address::create([
            'tenant_id'      => $provider->tenant_id,
            'customer_id'    => $customer->id,
            'address'        => "Rua Cliente {$customerIndex}",
            'address_number' => (string) ($customerIndex * 10),
            'neighborhood'   => "Bairro Cliente {$customerIndex}",
            'city'           => 'São Paulo',
            'state'          => 'SP',
            'cep'            => sprintf('%08d', $customerIndex + $providerIndex * 1000),
        ]);

        if ($type === 'company') {
            BusinessData::firstOrCreate(
                [
                    'tenant_id'   => $provider->tenant_id,
                    'customer_id' => $customer->id,
                ],
                [
                    'fantasy_name'           => "Cliente {$customerIndex}",
                    'founding_date'          => '2015-01-01',
                    'state_registration'     => "999{$customerIndex}{$providerIndex}",
                    'municipal_registration' => "888{$customerIndex}",
                    'industry'               => 'Comércio',
                    'company_size'           => 'micro',
                    'notes'                  => "Cliente empresa {$customerIndex}",
                ],
            );
        }
    }

    private function generateCPF(int $seed): string
    {
        // Gerar CPF único baseado no seed para evitar duplicatas
        // Garantir que seja de 11 dígitos e único para cada combinação
        $base = str_pad((string) $seed, 9, '0', STR_PAD_LEFT);

        // Adicionar dígitos verificadores simples
        $cpf = substr($base . '03', 0, 11); // Garantir 11 dígitos

        // Validar que não seja um CPF inválido (todos iguais)
        if (substr_count($cpf, $cpf[0]) === 11) {
            $cpf = '12345678901'; // CPF válido de fallback
        }

        return $cpf;
    }

    private function generateCNPJ(int $seed): string
    {
        // Gerar CNPJ único baseado no seed para evitar duplicatas
        // Garantir que seja de 14 dígitos e único para cada combinação
        $base = str_pad((string) $seed, 12, '0', STR_PAD_LEFT);

        // Adicionar dígitos verificadores simples
        $cnpj = substr($base . '91', 0, 14); // Garantir 14 dígitos

        return $cnpj;
    }
}
