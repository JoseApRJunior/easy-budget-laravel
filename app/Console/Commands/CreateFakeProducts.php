<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CreateFakeProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:create-fake
                            {tenant? : ID do tenant (opcional)}
                            {--quantity=5 : Quantidade de produtos a criar}
                            {--all : Criar produtos para todos os tenants}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria produtos fake para teste em tenants específicos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId   = (int) $this->argument( 'tenant' );
        $quantity   = (int) $this->option( 'quantity' );
        $allTenants = $this->option( 'all' );

        if ( $allTenants ) {
            return $this->createForAllTenants( $quantity );
        }

        if ( $tenantId ) {
            return $this->createForSpecificTenant( $tenantId, $quantity );
        }

        return $this->createForCurrentTenant( $quantity );
    }

    /**
     * Cria produtos para todos os tenants
     */
    private function createForAllTenants( int $quantity ): int
    {
        $tenants = Tenant::all();

        if ( $tenants->isEmpty() ) {
            $this->error( '❌ Nenhum tenant encontrado' );
            return self::FAILURE;
        }

        $this->info( "📦 Criando {$quantity} produtos para cada um dos {$tenants->count()} tenants..." );

        foreach ( $tenants as $tenant ) {
            $this->createProductsForTenant( $tenant->id, $tenant->name, $quantity );
        }

        $this->info( "✅ Produtos fake criados para todos os tenants!" );
        return self::SUCCESS;
    }

    /**
     * Cria produtos para um tenant específico
     */
    private function createForSpecificTenant( int $tenantId, int $quantity ): int
    {
        $tenant = Tenant::find( $tenantId );

        if ( !$tenant ) {
            $this->error( "❌ Tenant {$tenantId} não encontrado" );
            return self::FAILURE;
        }

        $this->createProductsForTenant( $tenantId, $tenant->name, $quantity );
        return self::SUCCESS;
    }

    /**
     * Cria produtos para o tenant atual (padrão)
     */
    private function createForCurrentTenant( int $quantity ): int
    {
        $defaultTenantId = 1;
        $tenant          = Tenant::find( $defaultTenantId );

        if ( !$tenant ) {
            $this->error( "❌ Tenant padrão (ID: {$defaultTenantId}) não encontrado" );
            return self::FAILURE;
        }

        $this->createProductsForTenant( $defaultTenantId, $tenant->name, $quantity );
        return self::SUCCESS;
    }

    /**
     * Cria produtos para um tenant específico
     */
    private function createProductsForTenant( int $tenantId, string $tenantName, int $quantity ): void
    {
        $this->info( "📦 Criando {$quantity} produtos para o tenant: {$tenantName} (ID: {$tenantId})" );

        // Chama diretamente o método estático do seeder para criar produtos para o tenant específico
        $seeder = new \Database\Seeders\FakeProductSeeder();
        $seeder::createForTenant( $tenantId, $quantity );
    }

}
