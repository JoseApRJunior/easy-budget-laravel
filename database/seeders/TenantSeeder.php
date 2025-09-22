<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tenant::updateOrCreate(
            [ 'slug' => 'default' ],
            [ 
                'name'        => 'Tenant Padrão',
                'description' => 'Tenant padrão para o sistema',
                'domain'      => 'localhost',
                'is_active'   => true,
            ],
        );
    }

}
