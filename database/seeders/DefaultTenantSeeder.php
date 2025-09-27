<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;

class DefaultTenantSeeder extends Seeder
{
    public function run(): void
    {
        // // Garante ao menos um tenant padrão
        // $tenant = Tenant::query()->first();
        // if (!$tenant) {
        //     Tenant::create(['name' => 'Default Tenant']);
        // }
    }

}
