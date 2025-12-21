<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class FakeProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Cria produtos fake para um tenant específico.
     *
     * Uso:
     * - php artisan tinker FakeProductSeeder::createForTenant(1, 10)  // Cria 10 produtos para tenant 1
     * - php artisan tinker FakeProductSeeder::createForTenant(2)      // Cria 5 produtos para tenant 2 (padrão)
     * - php artisan tinker FakeProductSeeder::createForAllTenants(5)  // Cria 5 produtos para cada tenant
     */
    public function run(): void
    {
        $this->command->info( '📦 Criando produtos fake para todos os tenants...' );

        // Cria 3 produtos para cada tenant existente
        $tenants = Tenant::all();

        foreach ( $tenants as $tenant ) {
            $this->createForTenant( $tenant->id, 3 );
        }

        $this->command->info( '✅ Produtos fake criados com sucesso!' );
    }

    /**
     * Cria produtos para um tenant específico
     */
    public static function createForTenant( int $tenantId, int $quantity = 5 ): void
    {
        $tenant = Tenant::find( $tenantId );

        if ( !$tenant ) {
            echo "❌ Tenant {$tenantId} não encontrado\n";
            return;
        }

        echo "📦 Criando {$quantity} produtos para o tenant: {$tenant->name} (ID: {$tenantId})\n";

        // Cria produtos manualmente para evitar problemas com a factory
        $products = [];

        for ( $i = 0; $i < $quantity; $i++ ) {
            // Obtém a primeira categoria existente no tenant
            $category = \App\Models\Category::where( 'tenant_id', $tenantId )
                ->first();

            if ( !$category ) {
                // Se não houver categorias, cria uma categoria padrão
                $category = \App\Models\Category::create( [
                    'tenant_id' => $tenantId,
                    'name'      => 'Categoria Padrão',
                    'slug'      => 'categoria-padrao',
                    'is_active' => true,
                ] );
            }

            // Gera SKU único
            $service    = app( \App\Services\Domain\ProductService::class);
            $reflection = new \ReflectionClass( $service );
            $method     = $reflection->getMethod( 'generateUniqueSku' );
            $method->setAccessible( true );
            $sku = $method->invoke( $service, $tenantId );

            // Cria produto manualmente
            $product = Product::create( [
                'tenant_id'   => $tenantId,
                'category_id' => $category->id,
                'name'        => \Faker\Factory::create()->word,
                'description' => \Faker\Factory::create()->sentence,
                'sku'         => $sku,
                'price'       => \Faker\Factory::create()->randomFloat( 2, 10, 500 ),
                'unit'        => \Faker\Factory::create()->randomElement( [ 'un', 'h', 'm²' ] ),
                'active'      => true,
                'image'       => null,
            ] );

            $products[] = $product;
        }

        // Gera SKUs únicos para cada produto
        foreach ( $products as $product ) {
            // Gera SKU sequencial compatível com legado
            $service = app( \App\Services\Domain\ProductService::class);

            // Usa reflection para acessar o método privado generateUniqueSku
            $reflection = new \ReflectionClass( $service );
            $method     = $reflection->getMethod( 'generateUniqueSku' );
            $method->setAccessible( true );

            $sku = $method->invoke( $service, $tenantId );
            $product->update( [ 'sku' => $sku ] );

            echo "   ✓ Produto: {$product->name} (SKU: {$product->sku})\n";
        }

        echo "✅ {$quantity} produtos criados para o tenant {$tenant->name}\n";
    }

    /**
     * Cria produtos para todos os tenants
     */
    public static function createForAllTenants( int $quantity = 5 ): void
    {
        $tenants = Tenant::all();

        if ( $tenants->isEmpty() ) {
            echo "❌ Nenhum tenant encontrado\n";
            return;
        }

        echo "📦 Criando {$quantity} produtos para cada um dos {$tenants->count()} tenants...\n";

        foreach ( $tenants as $tenant ) {
            self::createForTenant( $tenant->id, $quantity );
        }

        echo "✅ Produtos fake criados para todos os tenants!\n";
    }

    /**
     * Cria produtos para o tenant atualmente autenticado
     */
    public static function createForCurrentTenant( int $quantity = 5 ): void
    {
        // Para uso em tinker, assume tenant 1 como padrão
        $currentTenantId = 1; // Pode ser modificado conforme necessário

        self::createForTenant( $currentTenantId, $quantity );
    }

}
