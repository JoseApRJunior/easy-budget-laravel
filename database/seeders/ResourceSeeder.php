<?php

namespace Database\Seeders;

use App\Models\Resource;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'name' => 'IA Analytics',
                'slug' => 'analytics',
                'status' => Resource::STATUS_ACTIVE,
                'in_dev' => false,
            ],
            [
                'name' => 'Gestão de Planos',
                'slug' => 'plans',
                'status' => Resource::STATUS_ACTIVE,
                'in_dev' => false,
            ],
            [
                'name' => 'Categorias',
                'slug' => 'categories',
                'status' => Resource::STATUS_ACTIVE,
                'in_dev' => false,
            ],
            [
                'name' => 'Clientes',
                'slug' => 'customers',
                'status' => Resource::STATUS_ACTIVE,
                'in_dev' => false,
            ],
            [
                'name' => 'Produtos',
                'slug' => 'products',
                'status' => Resource::STATUS_ACTIVE,
                'in_dev' => false,
            ],
            [
                'name' => 'Serviços',
                'slug' => 'services',
                'status' => Resource::STATUS_ACTIVE,
                'in_dev' => false,
            ],
            [
                'name' => 'Agendamentos',
                'slug' => 'schedules',
                'status' => Resource::STATUS_ACTIVE,
                'in_dev' => false,
            ],
            [
                'name' => 'Orçamentos',
                'slug' => 'budgets',
                'status' => Resource::STATUS_ACTIVE,
                'in_dev' => false,
            ],
            [
                'name' => 'Financeiro',
                'slug' => 'financial',
                'status' => Resource::STATUS_ACTIVE,
                'in_dev' => false,
            ],
            [
                'name' => 'Faturas',
                'slug' => 'invoices',
                'status' => Resource::STATUS_ACTIVE,
                'in_dev' => false,
            ],
            [
                'name' => 'Estoque',
                'slug' => 'inventory',
                'status' => Resource::STATUS_ACTIVE,
                'in_dev' => false,
            ],
            [
                'name' => 'QR Code',
                'slug' => 'qrcode',
                'status' => Resource::STATUS_ACTIVE,
                'in_dev' => false,
            ],
            [
                'name' => 'Relatórios',
                'slug' => 'reports',
                'status' => Resource::STATUS_ACTIVE,
                'in_dev' => false,
            ],
        ];

        foreach ($modules as $module) {
            Resource::updateOrCreate(
                ['slug' => $module['slug']],
                $module
            );
        }
    }
}
