<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal do sistema Easy Budget - Ambiente de Produção
 *
 * Contém apenas os dados básicos necessários para o funcionamento
 * do sistema em ambiente de produção.
 *
 * Para dados de teste completos, use DatabaseCleanerAndSeeder
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info( '🚀 Iniciando seed do Easy Budget (Ambiente de Produção)...' );

        // Dados básicos necessários para funcionamento do sistema
        $this->command->info( '📊 Criando dados básicos do sistema...' );
        $this->call( [
            PlanSeeder::class,
            UnitSeeder::class,
            AreasOfActivitySeeder::class,
            ProfessionSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
        ] );

        // Tenant público com dados completos
        $this->command->info( '🌐 Criando tenant público...' );
        $this->call( [
            PublicTenantSeeder::class,
        ] );

        // Tenant admin com dados completos
        $this->command->info( '👑 Criando tenant admin...' );
        $this->call( [
            AdminTenantSeeder::class,
        ] );

        // Categorias após tenants existirem
        $this->command->info( '🏷️ Criando categorias padrão...' );
        $this->call( [
            CategorySeeder::class,
        ] );

        $this->command->info( '✅ Seed do Easy Budget concluído com sucesso!' );
        $this->command->info( '' );
        $this->command->info( '📋 Resumo:' );
        $this->command->info( '   • Public Tenant criado (ID: 1) - Para dados públicos' );
        $this->command->info( '   • Admin Tenant criado (ID: 2) - Para administração' );
        $this->command->info( '   • Admin login: admin@easybudget.net.br (ID: 3)' );
        $this->command->info( '   • Senha admin: AdminPassword1@' );
        $this->command->info( '   • Dados de teste ignorados (uso DatabaseCleanerAndSeeder)' );
        $this->command->info( '' );
        $this->command->info( '💡 Para dados de teste completos:' );
        $this->command->info( '    php artisan db:seed --class=DatabaseCleanerAndSeeder' );
        $this->command->info( '' );
        $this->command->info( '🎯 Sistema de produção pronto para uso!' );
    }

}
