<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $perms = [
            // Budgets
            ['name' => 'budgets.view',   'description' => 'Visualizar orçamentos',          'created_at' => $now],
            ['name' => 'budgets.create', 'description' => 'Criar orçamentos',               'created_at' => $now],
            ['name' => 'budgets.update', 'description' => 'Atualizar orçamentos',           'created_at' => $now],
            ['name' => 'budgets.delete', 'description' => 'Excluir orçamentos',             'created_at' => $now],
            // Services
            ['name' => 'services.view',   'description' => 'Visualizar serviços',           'created_at' => $now],
            ['name' => 'services.create', 'description' => 'Criar serviços',                'created_at' => $now],
            ['name' => 'services.update', 'description' => 'Atualizar serviços',            'created_at' => $now],
            ['name' => 'services.delete', 'description' => 'Excluir serviços',              'created_at' => $now],
            // Invoices
            ['name' => 'invoices.view',   'description' => 'Visualizar faturas',            'created_at' => $now],
            ['name' => 'invoices.create', 'description' => 'Criar faturas',                 'created_at' => $now],
            ['name' => 'invoices.update', 'description' => 'Atualizar faturas',             'created_at' => $now],
            ['name' => 'invoices.delete', 'description' => 'Excluir faturas',               'created_at' => $now],
            // Users
            ['name' => 'users.view',   'description' => 'Visualizar usuários',               'created_at' => $now],
            ['name' => 'users.create', 'description' => 'Criar usuários',                    'created_at' => $now],
            ['name' => 'users.update', 'description' => 'Atualizar usuários',                'created_at' => $now],
            ['name' => 'users.delete', 'description' => 'Excluir usuários',                  'created_at' => $now],
            // Settings
            ['name' => 'settings.manage', 'description' => 'Gerenciar configurações',       'created_at' => $now],
        ];

        DB::table('permissions')->upsert($perms, ['name'], ['description']);
    }
}
