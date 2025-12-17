<?php

/**
 * Verificar categorias do tenant 2
 */

require_once __DIR__ . '/vendor/autoload.php';

$app    = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== VERIFICAÇÃO TENANT 2 ===\n\n";

$tenantId   = 2;
$categories = DB::table( 'categories' )
    ->where( 'tenant_id', $tenantId )
    ->orderBy( 'parent_id' )
    ->orderBy( 'name' )
    ->get();

echo "Tenant ID: {$tenantId}\n";
echo "Total de categorias: " . count( $categories ) . "\n\n";

if ( count( $categories ) > 0 ) {
    echo "✅ CATEGORIAS CRIADAS PARA TENANT 2:\n";

    $mainCategories = $categories->whereNull( 'parent_id' );
    $subcategories  = $categories->whereNotNull( 'parent_id' );

    foreach ( $mainCategories as $main ) {
        echo "\n📁 {$main->name}\n";
        $subs = $subcategories->where( 'parent_id', $main->id );
        foreach ( $subs as $sub ) {
            echo "   └── 📄 {$sub->name}\n";
        }
    }

    echo "\n=== RESUMO TENANT 2 ===\n";
    echo "Principais: " . $mainCategories->count() . "\n";
    echo "Subcategorias: " . $subcategories->count() . "\n";
    echo "Total: " . ( $mainCategories->count() + $subcategories->count() ) . "\n\n";

} else {
    echo "❌ Nenhuma categoria encontrada para tenant {$tenantId}\n";
}

echo "=== CATEGORIAS PARA TODOS OS TENANTS ===\n";
$allTenants = DB::table( 'tenants' )->get();
foreach ( $allTenants as $tenant ) {
    $count = DB::table( 'categories' )->where( 'tenant_id', $tenant->id )->count();
    echo "Tenant {$tenant->id} ({$tenant->name}): {$count} categorias\n";
}
