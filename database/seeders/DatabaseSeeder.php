<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call( [
                // DefaultTenantSeeder::class,
                // Catálogos globais
            UnitSeeder::class,
            AreasOfActivitySeeder::class,
            ProfessionSeeder::class,
            CategorySeeder::class,
            PlanSeeder::class,
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
