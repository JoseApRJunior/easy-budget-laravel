<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder completo com dados de teste para desenvolvimento
 *
 * Limpa o banco de dados e executa o seed completo incluindo:
 * - Dados básicos do sistema
 * - Prestadores de teste
 * - Clientes de teste
 * - Orçamentos, serviços e faturas de teste
 *
 * Use sempre que precisar resetar o banco para estado inicial de desenvolvimento
 */
class DatabaseCleanerAndSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info( '🧹 Limpando banco de dados (incluindo dados de teste)...' );

        // Desabilitar verificações de foreign key
        DB::statement( 'SET FOREIGN_KEY_CHECKS=0;' );

        // Tabelas na ordem correta para evitar problemas de dependência
        $tables = [
            'service_items',
            'services',
            'invoices',
            'invoice_items',
            'budgets',
            'budget_items',
            'product_inventory',
            'products',
            'customers',
            'providers',
            'provider_credentials',
            'schedules',
            'payment_mercado_pago_invoices',
            'merchant_orders_mercado_pago',
            'payment_mercado_pago_plans',
            'plan_subscriptions',
            'support_requests',
            'supports',
            'reports',
            'notifications',
            'audit_logs',
            'activities',
            'middleware_metrics_history',
            'monitoring_alerts_history',
            'alert_settings',
            'resources',
            'user_settings',
            'user_confirmation_tokens',
            'system_settings',
            'plan_subscriptions',
            'users',
            'common_datas',
            'contacts',
            'addresses',
            'permissions',
            'role_permissions',
            'roles',
            'categories',
            'professions',
            'areas_of_activity',
            'units',
            'tenants',
            'cache',
            'cache_locks',
            'jobs',
            'failed_jobs',
            'password_reset_tokens',
            'sessions',
        ];

        foreach ( $tables as $table ) {
            try {
                if ( DB::getSchemaBuilder()->hasTable( $table ) ) {
                    DB::table( $table )->truncate();
                    $this->command->info( "   ✅ Tabela {$table} limpa" );
                }
            } catch ( \Exception $e ) {
                $this->command->warn( "   ⚠️  Erro ao limpar tabela {$table}: " . $e->getMessage() );
            }
        }

        // Reabilitar verificações de foreign key
        DB::statement( 'SET FOREIGN_KEY_CHECKS=1;' );

        $this->command->info( '✅ Banco de dados limpo com sucesso!' );
        $this->command->info( '🚀 Iniciando seed completo com dados de teste...' );

        // 1. Executar seeders de dados básicos/globais
        $this->command->info( '📊 Criando dados básicos do sistema...' );
        $this->call( [
            PlanSeeder::class,
            UnitSeeder::class,
            AreasOfActivitySeeder::class,
            ProfessionSeeder::class,
            CategorySeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
        ] );

        // 2. Criar tenant público com dados completos
        $this->command->info( '🌐 Criando tenant público...' );
        $this->call( [
            PublicTenantSeeder::class,
        ] );

        // 3. Criar tenant admin com dados completos
        $this->command->info( '👑 Criando tenant admin...' );
        $this->call( [
            AdminTenantSeeder::class,
        ] );

        // 4. Criar Prestadores de teste completos
        $this->command->info( '🏢 Criando prestadores de teste (10 providers + 200 clientes)...' );
        $this->call( [
            ProviderTestSeeder::class,
        ] );

        // 5. Criar dados de teste de budgets (orçamentos, serviços, faturas)
        $this->command->info( '📊 Criando dados de teste de budgets (50 orçamentos, 250 serviços)...' );
        $this->call( [
            BudgetTestSeeder::class,
        ] );

        $this->command->info( '✅ DatabaseCleanerAndSeeder concluído com sucesso!' );
        $this->command->info( '' );
        $this->command->info( '📋 Resumo Completo:' );
        $this->command->info( '   • Public Tenant criado (ID: 1) - Para dados públicos' );
        $this->command->info( '   • Admin Tenant criado (ID: 2) - Para administração' );
        $this->command->info( '   • Admin login: admin@easybudget.net.br (ID: 3)' );
        $this->command->info( '   • Senha admin: AdminPassword1@' );
        $this->command->info( '   • 10 Prestadores de teste criados (5 PJ + 5 PF)' );
        $this->command->info( '   • 200 Clientes de teste criados (100 PF + 100 PJ)' );
        $this->command->info( '   • 50 Orçamentos de teste criados (5 por provider)' );
        $this->command->info( '   • 250 Serviços de teste criados (5 por orçamento, com status variados)' );
        $this->command->info( '   • 1250 Itens de serviço criados (5 produtos por serviço)' );
        $this->command->info( '   • Faturas geradas para serviços finalizados' );
        $this->command->info( '   • Login: provider1@test.com até provider10@test.com' );
        $this->command->info( '   • Senha padrão: Password1@' );
        $this->command->info( '' );
        $this->command->info( '💡 Use apenas: php artisan db:seed --class=DatabaseCleanerAndSeeder' );
        $this->command->info( '🎯 Sistema completo com dados de teste pronto para uso!' );
    }

}
