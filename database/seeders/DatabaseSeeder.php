<?php

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

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Criar tenant padrão se não existir
        $tenant = Tenant::firstOrCreate(
            [ 'name' => 'Test Tenant' ],
            [
                'name'      => 'Test Tenant',
                'is_active' => true,
            ],
        );

        // Criar usuário admin para testes
        $user = User::firstOrCreate(
            [ 'email' => 'provider@easybudget.com' ],
            [
                'tenant_id' => $tenant->id,
                'email'     => 'provider@easybudget.com',
                'password'  => Hash::make( 'Password1@' ),
                'is_active' => true,
            ],
        );

        // Criar dados complementares para o provider (endereço, contato, dados pessoais/empresariais)
        $address = Address::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cep'       => '01310-100',
            ],
            [
                'tenant_id'      => $tenant->id,
                'address'        => 'Av. Paulista',
                'address_number' => '1000',
                'neighborhood'   => 'Bela Vista',
                'city'           => 'São Paulo',
                'state'          => 'SP',
                'cep'            => '01310-100',
            ],
        );

        $contact = Contact::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email'     => 'contato@empresa.com',
            ],
            [
                'tenant_id'      => $tenant->id,
                'email'          => 'contato@empresa.com',
                'phone'          => '(11) 99999-9999',
                'email_business' => 'comercial@empresa.com',
                'phone_business' => '(11) 8888-8888',
                'website'        => 'https://empresa.com.br',
            ],
        );

        $commonData = CommonData::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cpf'       => '12345678901',
            ],
            [
                'tenant_id'    => $tenant->id,
                'first_name'   => 'João',
                'last_name'    => 'Silva',
                'birth_date'   => '1985-05-15',
                'cpf'          => '12345678901',
                'cnpj'         => '12345678000190',
                'company_name' => 'Empresa Exemplo Ltda',
                'description'  => 'Empresa especializada em serviços de tecnologia',
            ],
        );

        // Criar provider associado ao usuário de teste com dados completos
        // Agora o provider terá todos os campos opcionais preenchidos:
        // - common_data_id: dados pessoais e empresariais
        // - contact_id: informações de contato
        // - address_id: endereço completo
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
                'address_id'     => $address->id,
                'terms_accepted' => true,
            ],
        );

        // Criar role provider se não existir
        $providerRole = Role::firstOrCreate(
            [ 'name' => 'Provider' ],
            [
                'name'        => 'Provider',
                'description' => 'Provedor de serviços - acesso completo'
            ],
        );

        // Associar usuário à role provider no tenant
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

        // Criar assinatura de plano para o provider de teste
        $plan = Plan::first(); // Pega o primeiro plano disponível
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
                    'transaction_amount' => 29.90,
                    'start_date'         => now(),
                    'end_date'           => date( 'Y-m-d H:i:s', strtotime( '+1 year' ) ), // ← 1 ano de teste
                    'payment_method'     => 'credit_card',
                    'payment_id'         => 'TEST_' . uniqid(),
                    'public_hash'        => 'TEST_HASH_' . uniqid(),
                ],
            );
        }

        // Exemplo: Criar provider adicional para demonstração (opcional)
        $this->createAdditionalProvider( $tenant );

        // Executar seeders existentes se necessário
        $this->call( [
            PlanSeeder::class,
                // DefaultTenantSeeder::class,
                // Catálogos globais
            UnitSeeder::class,
            AreasOfActivitySeeder::class,
            ProfessionSeeder::class,
            CategorySeeder::class,
                // Statuses
            BudgetStatusSeeder::class,
            ServiceStatusSeeder::class,
            InvoiceStatusSeeder::class,
                // RBAC
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
        ] );
    }

    /**
     * Criar provider adicional para demonstração
     * Exemplo de como criar múltiplos providers com dados diferentes
     */
    private function createAdditionalProvider( Tenant $tenant ): void
    {
        // Criar segundo usuário para demonstração
        $user2 = User::firstOrCreate(
            [ 'email' => 'provider2@easybudget.com' ],
            [
                'tenant_id' => $tenant->id,
                'email'     => 'provider2@easybudget.com',
                'password'  => Hash::make( 'Password1@' ),
                'is_active' => true,
            ],
        );

        // Criar dados complementares diferentes para o segundo provider
        $address2 = Address::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cep'       => '20040-020',
            ],
            [
                'tenant_id'      => $tenant->id,
                'address'        => 'Rua da Quitanda',
                'address_number' => '50',
                'neighborhood'   => 'Centro',
                'city'           => 'Rio de Janeiro',
                'state'          => 'RJ',
                'cep'            => '20040-020',
            ],
        );

        $contact2 = Contact::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email'     => 'contato2@empresa.com',
            ],
            [
                'tenant_id'      => $tenant->id,
                'email'          => 'contato2@empresa.com',
                'phone'          => '(21) 8888-8888',
                'email_business' => 'comercial2@empresa.com',
                'phone_business' => '(21) 7777-7777',
                'website'        => 'https://empresa2.com.br',
            ],
        );

        $commonData2 = CommonData::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cpf'       => '98765432109',
            ],
            [
                'tenant_id'    => $tenant->id,
                'first_name'   => 'Maria',
                'last_name'    => 'Santos',
                'birth_date'   => '1990-08-20',
                'cpf'          => '98765432109',
                'cnpj'         => '98765432000110',
                'company_name' => 'Empresa Demo Ltda',
                'description'  => 'Empresa de demonstração para testes',
            ],
        );

        // Criar segundo provider com dados diferentes
        $provider2 = Provider::firstOrCreate(
            [
                'user_id'   => $user2->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'user_id'        => $user2->id,
                'tenant_id'      => $tenant->id,
                'common_data_id' => $commonData2->id,
                'contact_id'     => $contact2->id,
                'address_id'     => $address2->id,
                'terms_accepted' => true,
            ],
        );

        // Buscar role provider para associar ao segundo usuário
        $providerRole2 = Role::where( 'name', 'Provider' )->first();

        if ( $providerRole2 ) {
            UserRole::firstOrCreate(
                [
                    'user_id'   => $user2->id,
                    'role_id'   => $providerRole2->id,
                    'tenant_id' => $tenant->id,
                ],
                [
                    'user_id'   => $user2->id,
                    'role_id'   => $providerRole2->id,
                    'tenant_id' => $tenant->id,
                ],
            );
        }
    }

}
