<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\AreaOfActivitySeeder;
use Database\Seeders\BudgetStatusSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\InvoiceStatusSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\ProfessionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SampleDataSeeder;
use Database\Seeders\ServiceStatusSeeder;
use Database\Seeders\TenantSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Se não há TenantSeeder, criar default tenant logicamente
        if ( !class_exists( TenantSeeder::class) ) {
            $this->command->info( 'Criando default tenant se não existir...' );
            if ( \App\Models\Tenant::count() === 0 ) {
                \App\Models\Tenant::create( [ 
                    'name'      => 'Default',
                    'slug'      => 'default',
                    'is_active' => true,
                ] );
            }
        } else {
            $this->call( TenantSeeder::class);
        }

        // Ordem lógica: tenants -> RBAC -> lookup tables (statuses, units, areas) -> domain data
        $this->call( [ 
            RolePermissionSeeder::class,
            ServiceStatusSeeder::class,
            InvoiceStatusSeeder::class,
            BudgetStatusSeeder::class,
            AreaOfActivitySeeder::class,
            ProfessionSeeder::class,
            UnitSeeder::class,
            CategorySeeder::class,
            PlanSeeder::class,
        ] );

        // SampleDataSeeder apenas em ambientes de desenvolvimento/teste
        if ( app()->environment( [ 'local', 'testing' ] ) ) {
            $this->call( SampleDataSeeder::class);
        }
    }

}
