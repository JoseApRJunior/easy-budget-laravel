<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal do sistema Easy Budget.
 *
 * Organiza a execução de todos os seeders necessários para inicializar
 * o sistema com dados básicos e de demonstração.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info( '🚀 Iniciando seed do Easy Budget...' );

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

        // 4. Criar provedores de teste (opcional - apenas em desenvolvimento)
        if ( app()->environment( [ 'local', 'testing' ] ) ) {
            $this->command->info( '🏢 Criando dados de teste...' );
            $this->call( [
                ProviderTestSeeder::class,
            ] );
        } else {
            $this->command->info( '⚠️  Dados de teste ignorados (ambiente: ' . app()->environment() . ')' );
        }

        $this->command->info( '✅ Seed do Easy Budget concluído com sucesso!' );
        $this->command->info( '' );
        $this->command->info( '📋 Resumo:' );
        $this->command->info( '   • Public Tenant criado (ID: 1) - Para dados públicos' );
        $this->command->info( '   • Admin Tenant criado (ID: 2) - Para administração' );
        $this->command->info( '   • Admin login: admin@easybudget.net.br (ID: 3)' );
        $this->command->info( '   • Senha admin: AdminPassword1@' );

        if ( app()->environment( [ 'local', 'testing' ] ) ) {
            $this->command->info( '   • 10 Provedores de teste criados (5 PJ + 5 PF)' );
            $this->command->info( '   • 200 Clientes de teste criados (100 PF + 100 PJ)' );
            $this->command->info( '   • Login: provider1@test.com até provider10@test.com' );
            $this->command->info( '   • Senha padrão: Password1@' );
        }

        $this->command->info( '' );
        $this->command->info( '🎯 Sistema pronto para uso!' );
    }

}
