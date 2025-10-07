<?php

namespace Database\Seeders;

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

        // Criar provider associado ao usuário de teste
        $provider = Provider::firstOrCreate(
            [
                'user_id'   => $user->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'user_id'        => $user->id,
                'tenant_id'      => $tenant->id,
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
                    'end_date'           => now()->addYears( 50 ), // ← 50 anos ao invés de 1 mês
                    'payment_method'     => 'credit_card',
                    'payment_id'         => 'TEST_' . uniqid(),
                    'public_hash'        => 'TEST_HASH_' . uniqid(),
                ],
            );
        }

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

}
