<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Criar tenant padrão se não existir
        $tenant = \App\Models\Tenant::firstOrCreate(
            [ 'name' => 'Default Tenant' ],
            [
                'name'      => 'Default Tenant',
                'is_active' => true,
            ],
        );

        // Criar usuário admin para testes
        User::firstOrCreate(
            [ 'email' => 'admin@easybudget.com' ],
            [
                'tenant_id' => $tenant->id,
                'email'     => 'admin@easybudget.com',
                'password'  => Hash::make( 'password' ),
                'is_active' => true,
            ],
        );

        // Criar planos básicos para testes
        $plans = [
            [
                'name'        => 'Plano Básico',
                'slug'        => 'plano-basico',
                'price'       => 29.90,
                'max_budgets' => 10,
                'max_clients' => 5,
                'status'      => true,
            ],
            [
                'name'        => 'Plano Profissional',
                'slug'        => 'plano-profissional',
                'price'       => 59.90,
                'max_budgets' => 50,
                'max_clients' => 25,
                'status'      => true,
            ],
            [
                'name'        => 'Plano Enterprise',
                'slug'        => 'plano-enterprise',
                'price'       => 99.90,
                'max_budgets' => -1, // ilimitado
                'max_clients' => -1, // ilimitado
                'status'      => true,
            ]
        ];

        foreach ( $plans as $planData ) {
            Plan::firstOrCreate(
                [ 'slug' => $planData[ 'slug' ] ],
                $planData,
            );
        }

        // Executar seeders existentes se necessário
        $this->call( [
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
