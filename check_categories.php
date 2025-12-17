<?php

/**
 * Script para verificar as categorias criadas
 */

require_once __DIR__ . '/vendor/autoload.php';

$app    = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== VERIFICAÇÃO DAS CATEGORIAS ===\n\n";

// Verificar categorias para tenant 1
$tenantId   = 1;
$categories = DB::table( 'categories' )
    ->where( 'tenant_id', $tenantId )
    ->orderBy( 'parent_id' )
    ->orderBy( 'name' )
    ->get();

echo "Tenant ID: {$tenantId}\n";
echo "Total de categorias: " . count( $categories ) . "\n\n";

if ( count( $categories ) > 0 ) {
    echo "=== CATEGORIAS CRIADAS ===\n";

    $mainCategories = $categories->whereNull( 'parent_id' );
    $subcategories  = $categories->whereNotNull( 'parent_id' );

    foreach ( $mainCategories as $main ) {
        echo "\n📁 {$main->name} (ID: {$main->id})\n";
        $subs = $subcategories->where( 'parent_id', $main->id );
        foreach ( $subs as $sub ) {
            echo "   └── 📄 {$sub->name} (ID: {$sub->id})\n";
        }
    }

    echo "\n=== RESUMO ===\n";
    echo "Categorias principais: " . $mainCategories->count() . "\n";
    echo "Subcategorias: " . $subcategories->count() . "\n";
    echo "Total: " . $mainCategories->count() + $subcategories->count() . "\n\n";

    // Verificar logs de auditoria
    $auditLog = DB::table( 'audit_logs' )
        ->where( 'action', 'seed_categories' )
        ->where( 'tenant_id', $tenantId )
        ->latest()
        ->first();

    if ( $auditLog ) {
        echo "=== LOG DE AUDITORIA ===\n";
        echo "Ação: {$auditLog->action}\n";
        echo "Descrição: {$auditLog->description}\n";
        echo "Data: {$auditLog->created_at}\n\n";
    }

} else {
    echo "❌ Nenhuma categoria encontrada para o tenant {$tenantId}\n";
}

echo "=== TESTE CONCLUÍDO ===\n";
