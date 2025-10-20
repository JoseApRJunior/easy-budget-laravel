<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Address;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder para criar dados de teste de provedores.
 *
 * Este seeder cria provedores de teste com dados fictícios completos
 * para desenvolvimento e demonstração do sistema.
 */
class ProviderTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏢 Criando provedores de teste...');

        // Criar primeiro provider com sua cadeia completa independente
        $this->createProviderWithFullChain(
            'Empresa Exemplo Ltda',
            'provider@easybudget.net.br',
            'João',
            'Silva',
            '12345678901',
            '12345678000190',
            'Av. Paulista',
            '1000',
            'Bela Vista',
            'São Paulo',
            'SP',
            '01310-100',
            'contato@empresa.net.br',
            '(11) 99999-9999',
            'comercial@empresa.net.br',
            '(11) 8888-8888',
            'https://empresa.net.br',
            'Empresa especializada em serviços de tecnologia',
        );

        // Criar segundo provider com sua cadeia completa independente
        $this->createProviderWithFullChain(
            'Empresa Demo Ltda',
            'provider2@easybudget.net.br',
            'Maria',
            'Santos',
            '98765432109',
            '98765432000110',
            'Rua da Quitanda',
            '50',
            'Centro',
            'Rio de Janeiro',
            'RJ',
            '20040-020',
            'contato2@empresa.net.br',
            '(21) 8888-8888',
            'comercial2@empresa.net.br',
            '(21) 7777-7777',
            'https://empresa2.net.br',
            'Empresa de demonstração para testes',
        );

        $this->command->info('✅ Provedores de teste criados com sucesso!');
    }

    private function createProviderWithFullChain(
        string $companyName,
        string $userEmail,
        string $firstName,
        string $lastName,
        string $cpf,
        string $cnpj,
        string $address,
        string $addressNumber,
        string $neighborhood,
        string $city,
        string $state,
        string $cep,
        string $contactEmail,
        string $phone,
        string $emailBusiness,
        string $phoneBusiness,
        string $website,
        string $description,
    ): void {
        // 1. Criar tenant independente para este provider
        $tenant = Tenant::firstOrCreate(
            [ 'name' => $companyName . ' Tenant' ],
            [
                'name'      => $companyName . ' Tenant',
                'is_active' => true,
            ],
        );

        // 2. Criar usuário exclusivo para este provider
        $user = User::firstOrCreate(
            [ 'email' => $userEmail ],
            [
                'tenant_id'         => $tenant->id,
                'email'             => $userEmail,
                'password'          => Hash::make( 'Password1@' ),
                'is_active'         => true,
                'email_verified_at' => now()
            ],
        );

        // 3. Criar endereço exclusivo para este provider
        $addressModel = Address::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cep'       => $cep,
            ],
            [
                'tenant_id'      => $tenant->id,
                'address'        => $address,
                'address_number' => $addressNumber,
                'neighborhood'   => $neighborhood,
                'city'           => $city,
                'state'          => $state,
                'cep'            => $cep,
            ],
        );

        // 4. Criar contato exclusivo para este provider
        $contact = Contact::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email'     => $contactEmail,
            ],
            [
                'tenant_id'      => $tenant->id,
                'email'          => $contactEmail,
                'phone'          => $phone,
                'email_business' => $emailBusiness,
                'phone_business' => $phoneBusiness,
                'website'        => $website,
            ],
        );

        // Criar área de atividade e profissão (tabelas globais)
        $areaOfActivity = \App\Models\AreaOfActivity::firstOrCreate(
            [ 'slug' => 'tecnologia' ],
            [
                'slug'      => 'tecnologia',
                'name'      => 'Tecnologia da Informação',
                'is_active' => true,
            ],
        );

        $profession = \App\Models\Profession::firstOrCreate(
            [ 'slug' => 'desenvolvedor' ],
            [
                'slug'      => 'desenvolvedor',
                'name'      => 'Desenvolvedor de Software',
                'is_active' => true,
            ],
        );

        // 5. Criar dados pessoais/empresariais exclusivos para este provider
        $commonData = CommonData::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cpf'       => $cpf,
            ],
            [
                'tenant_id'           => $tenant->id,
                'first_name'          => $firstName,
                'last_name'           => $lastName,
                'birth_date'          => '1985-05-15',
                'cpf'                 => $cpf,
                'cnpj'                => $cnpj,
                'company_name'        => $companyName,
                'description'         => $description,
                'area_of_activity_id' => $areaOfActivity->id,
                'profession_id'       => $profession->id,
            ],
        );

        // 6. Criar provider com todos os dados relacionados
        $provider = Provider::firstOrCreate(
            [
                'user_id'   => $user->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'user_id'        => $user->id,
                'tenant_id'      => $tenant->id,
                'common_data_id' => $commonData->id,
                'contact_id'     => $contact->id,
                'address_id'     => $addressModel->id,
                'terms_accepted' => true,
            ],
        );

        // 7. Associar role provider ao usuário
        $providerRole = Role::firstOrCreate(
            [ 'name' => 'Provider' ],
            [
                'name'        => 'Provider',
                'description' => 'Provedor de serviços - acesso completo'
            ],
        );

        UserRole::firstOrCreate(
            [
                'user_id'   => $user->id,
                'role_id'   => $providerRole->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'user_id'   => $user->id,
                'role_id'   => $providerRole->id,
                'tenant_id' => $tenant->id,
            ],
        );

        // 8. Criar assinatura de plano para este provider
        $plan = Plan::first();
        if ( $plan ) {
            PlanSubscription::firstOrCreate(
                [
                    'provider_id' => $provider->id,
                    'plan_id'     => $plan->id,
                    'tenant_id'   => $tenant->id,
                ],
                [
                    'provider_id'        => $provider->id,
                    'plan_id'            => $plan->id,
                    'tenant_id'          => $tenant->id,
                    'status'             => 'active',
                    'transaction_amount' => 00.00,
                    'start_date'         => now(),
                    'end_date'           => date( 'Y-m-d H:i:s', strtotime( '+1 year' ) ),
                    'payment_method'     => 'trial',
                    'payment_id'         => 'TEST_' . uniqid(),
                    'public_hash'        => 'TEST_HASH_' . uniqid(),
                ],
            );
        }

        // 9. Criar customer de teste para este provider
        $this->createCustomerForProvider( $tenant, $provider, $addressModel, $contact );

        // 10. Criar configurações do sistema para este provider
        $this->createSystemSettingsForProvider( $tenant, $provider, $commonData, $addressModel, $contact );

        // 11. Criar configurações do usuário para este provider
        $this->createUserSettingsForProvider( $tenant, $user );

        $this->command->info("✅ Provider '{$companyName}' criado com sucesso!");
    }

    private function createCustomerForProvider( Tenant $tenant, Provider $provider, Address $address, Contact $contact ): void
    {
        // Criar dados pessoais para o customer (CPF único por empresa)
        $customerCpf        = '111222333' . $tenant->id . '4';
        $customerCommonData = CommonData::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cpf'       => $customerCpf,
            ],
            [
                'tenant_id'    => $tenant->id,
                'first_name'   => 'Cliente',
                'last_name'    => 'Teste',
                'birth_date'   => '1990-03-10',
                'cpf'          => $customerCpf,
                'cnpj'         => null,
                'company_name' => null,
                'description'  => 'Cliente de teste para demonstração',
            ],
        );

        // Criar contato específico para o customer (email único por empresa)
        $customerEmail   = 'cliente' . $tenant->id . '@teste.net.br';
        $customerContact = Contact::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email'     => $customerEmail,
            ],
            [
                'tenant_id'      => $tenant->id,
                'email'          => $customerEmail,
                'phone'          => '(11) 7777-7777',
                'email_business' => null,
                'phone_business' => null,
                'website'        => null,
            ],
        );

        // Criar endereço específico para o customer
        $customerAddress = Address::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cep'       => '04567-890',
            ],
            [
                'tenant_id'      => $tenant->id,
                'address'        => 'Rua Augusta',
                'address_number' => '500',
                'neighborhood'   => 'Consolação',
                'city'           => 'São Paulo',
                'state'          => 'SP',
                'cep'            => '04567-890',
            ],
        );

        // Criar customer vinculado ao provider
        $customer = \App\Models\Customer::firstOrCreate(
            [
                'tenant_id'      => $tenant->id,
                'common_data_id' => $customerCommonData->id,
            ],
            [
                'tenant_id'      => $tenant->id,
                'common_data_id' => $customerCommonData->id,
                'contact_id'     => $customerContact->id,
                'address_id'     => $customerAddress->id,
                'status'         => 'active',
            ],
        );

        $this->command->info("   📋 Cliente de teste criado: {$customerEmail}");
    }

    private function createSystemSettingsForProvider( Tenant $tenant, Provider $provider, CommonData $commonData, Address $address, Contact $contact ): void
    {
        \App\Models\SystemSettings::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
            ],
            [
                'tenant_id'                   => $tenant->id,
                'company_name'                => $commonData->company_name,
                'contact_email'               => $contact->email_business ?: $contact->email,
                'phone'                       => $contact->phone_business ?: $contact->phone,
                'website'                     => $contact->website,
                'logo'                        => null,
                'currency'                    => 'BRL',
                'timezone'                    => 'America/Sao_Paulo',
                'language'                    => 'pt-BR',
                'address_street'              => $address->address,
                'address_number'              => $address->address_number,
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
                'allowed_file_types'          => json_encode( [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'application/pdf',
                    'text/plain',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ] ),
                'max_file_size'               => 2048,
                'system_preferences'          => json_encode( [
                    'auto_save'    => true,
                    'compact_mode' => false,
                    'show_tips'    => true,
                ] ),
            ],
        );

        $this->command->info("   ⚙️  Configurações do sistema criadas para: {$commonData->company_name}");
    }

    private function createUserSettingsForProvider( Tenant $tenant, User $user ): void
    {
        \App\Models\UserSettings::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id'   => $user->id,
            ],
            [
                'tenant_id'                 => $tenant->id,
                'user_id'                   => $user->id,
                'avatar'                    => null,
                'full_name'                 => $user->name ?? 'Usuário Teste',
                'bio'                       => 'Configurações padrão do usuário',
                'phone'                     => null,
                'birth_date'                => null,
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
                'custom_preferences'        => json_encode( [
                    'auto_save'    => true,
                    'compact_mode' => false,
                    'show_tips'    => true,
                ] ),
            ],
        );

        $this->command->info("   👤 Configurações do usuário criadas para: {$user->email}");
    }
}