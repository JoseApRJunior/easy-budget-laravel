<?php

// Teste para verificar se a hierarquia ainda funciona após as correções
require_once __DIR__ . '/vendor/autoload.php';

try {
    echo "=== Testando Hierarquia de Categorias ===\n";

    // Inicializar Laravel
    $app    = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);
    $app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $repo = new \App\Repositories\CategoryRepository();

    // Testar listActiveByTenantId para verificar hierarquia
    echo "Testando listActiveByTenantId()...\n";
    $categories = $repo->listActiveByTenantId( 1 ); // Assumindo tenant_id = 1

    echo "Total categorias ativas: " . count( $categories ) . "\n";

    // Verificar se há categorias com parent
    $withParent = collect( $categories )->whereNotNull( 'parent_id' )->count();
    echo "Categorias com parent: " . $withParent . "\n";

    // Mostrar algumas categorias com parent para verificar hierarquia
    $parentCategories = collect( $categories )->whereNotNull( 'parent_id' )->take( 5 );
    if ( $parentCategories->count() > 0 ) {
        echo "\nExemplos de categorias com hierarquia:\n";
        foreach ( $parentCategories as $cat ) {
            $parentName = $cat->parent ? $cat->parent->name : 'Parent não carregado';
            echo "  - {$cat->name} (parent: {$parentName})\n";
        }
    }

    // Testar paginação com eager loading do parent
    echo "\nTestando paginação com eager loading do parent...\n";
    $request = \Illuminate\Http\Request::create( '/categories?page=1', 'GET' );
    app()->instance( 'request', $request );

    $paginator = $repo->getPaginated( [ 'with_parent' => true ], 5 );

    echo "Página atual: " . $paginator->currentPage() . "\n";
    echo "Primeira categoria: " . $paginator->first()->name . "\n";

    // Verificar se o parent está carregado
    $firstCat = $paginator->first();
    if ( $firstCat->relationLoaded( 'parent' ) ) {
        echo "Relacionamento 'parent' está carregado: " . ( $firstCat->parent ? "Sim (" . $firstCat->parent->name . ")" : "Sim (null)" ) . "\n";
    } else {
        echo "Relacionamento 'parent' NÃO está carregado\n";
    }

} catch ( Exception $e ) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
