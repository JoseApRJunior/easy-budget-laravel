<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $now   = now();
        $perms = [
            // Budgets
            [ 'name' => 'budgets.view', 'description' => 'Visualizar orçamentos', 'created_at' => $now ],
            [ 'name' => 'budgets.create', 'description' => 'Criar orçamentos', 'created_at' => $now ],
            [ 'name' => 'budgets.update', 'description' => 'Atualizar orçamentos', 'created_at' => $now ],
            [ 'name' => 'budgets.delete', 'description' => 'Excluir orçamentos', 'created_at' => $now ],
            // Services
            [ 'name' => 'services.view', 'description' => 'Visualizar serviços', 'created_at' => $now ],
            [ 'name' => 'services.create', 'description' => 'Criar serviços', 'created_at' => $now ],
            [ 'name' => 'services.update', 'description' => 'Atualizar serviços', 'created_at' => $now ],
            [ 'name' => 'services.delete', 'description' => 'Excluir serviços', 'created_at' => $now ],
            // Invoices
            [ 'name' => 'invoices.view', 'description' => 'Visualizar faturas', 'created_at' => $now ],
            [ 'name' => 'invoices.create', 'description' => 'Criar faturas', 'created_at' => $now ],
            [ 'name' => 'invoices.update', 'description' => 'Atualizar faturas', 'created_at' => $now ],
            [ 'name' => 'invoices.delete', 'description' => 'Excluir faturas', 'created_at' => $now ],
            // Users
            [ 'name' => 'users.view', 'description' => 'Visualizar usuários', 'created_at' => $now ],
            [ 'name' => 'users.create', 'description' => 'Criar usuários', 'created_at' => $now ],
            [ 'name' => 'users.update', 'description' => 'Atualizar usuários', 'created_at' => $now ],
            [ 'name' => 'users.delete', 'description' => 'Excluir usuários', 'created_at' => $now ],
            // Settings
            [ 'name' => 'settings.manage', 'description' => 'Gerenciar configurações', 'created_at' => $now ],
            // Categories
            [ 'name' => 'manage-categories', 'description' => 'Gerenciar categorias', 'created_at' => $now ],
            // Products
            [ 'name' => 'products.view', 'description' => 'Visualizar produtos', 'created_at' => $now ],
            [ 'name' => 'products.create', 'description' => 'Criar produtos', 'created_at' => $now ],
            [ 'name' => 'products.update', 'description' => 'Atualizar produtos', 'created_at' => $now ],
            [ 'name' => 'products.delete', 'description' => 'Excluir produtos', 'created_at' => $now ],
            // Customers
            [ 'name' => 'customers.view', 'description' => 'Visualizar clientes', 'created_at' => $now ],
            [ 'name' => 'customers.create', 'description' => 'Criar clientes', 'created_at' => $now ],
            [ 'name' => 'customers.update', 'description' => 'Atualizar clientes', 'created_at' => $now ],
            [ 'name' => 'customers.delete', 'description' => 'Excluir clientes', 'created_at' => $now ],
            // Reports
            [ 'name' => 'reports.view', 'description' => 'Visualizar relatórios', 'created_at' => $now ],
            [ 'name' => 'reports.create', 'description' => 'Criar relatórios', 'created_at' => $now ],
            [ 'name' => 'reports.manage', 'description' => 'Gerenciar relatórios', 'created_at' => $now ],
            // Activities
            [ 'name' => 'activities.view', 'description' => 'Visualizar atividades', 'created_at' => $now ],
            [ 'name' => 'activities.create', 'description' => 'Criar atividades', 'created_at' => $now ],
            [ 'name' => 'activities.update', 'description' => 'Atualizar atividades', 'created_at' => $now ],
            [ 'name' => 'activities.delete', 'description' => 'Excluir atividades', 'created_at' => $now ],
            // Support
            [ 'name' => 'support.view', 'description' => 'Visualizar suporte', 'created_at' => $now ],
            [ 'name' => 'support.create', 'description' => 'Criar tickets de suporte', 'created_at' => $now ],
            [ 'name' => 'support.update', 'description' => 'Atualizar tickets de suporte', 'created_at' => $now ],
            [ 'name' => 'support.delete', 'description' => 'Excluir tickets de suporte', 'created_at' => $now ],
            // Plans
            [ 'name' => 'plans.view', 'description' => 'Visualizar planos', 'created_at' => $now ],
            [ 'name' => 'plans.manage', 'description' => 'Gerenciar planos', 'created_at' => $now ],
            // Provider Management
            [ 'name' => 'provider.view', 'description' => 'Visualizar dados do fornecedor', 'created_at' => $now ],
            [ 'name' => 'provider.update', 'description' => 'Atualizar dados do fornecedor', 'created_at' => $now ],
        ];

        DB::table( 'permissions' )->upsert( $perms, [ 'name' ], [ 'description' ] );
    }

}
