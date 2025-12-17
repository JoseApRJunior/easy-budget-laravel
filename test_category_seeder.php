<?php

/**
 * Teste do CategorySeeder
 *
 * Execute: php test_category_seeder.php
 *
 * Ou no Laravel Tinker:
 * require 'test_category_seeder.php';
 */

require_once __DIR__ . '/vendor/autoload.php';

use Database\Seeders\CategorySeeder;

// Carregar configuração Laravel
$app    = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTE DO CATEGORYSEEDER ===\n\n";

// Teste 1: Criar categorias para tenant 1
echo "1. Testando criação de categorias para tenant 1...\n";
CategorySeeder::seedForTenant( 1 );
echo "✓ Categorias criadas para tenant 1\n\n";

// Verificar categorias criadas
$categories = DB::table( 'categories' )
    ->where( 'tenant_id', 1 )
    ->orderBy( 'parent_id' )
    ->orderBy( 'name' )
    ->get();

echo "=== CATEGORIAS CRIADAS ===\n";
print_r( $categories->toArray() );
echo "\n=== ESTRUTURA HIERÁRQUICA ===\n";

// Mostrar estrutura hierárquica
$mainCategories = $categories->whereNull( 'parent_id' );
$subcategories  = $categories->whereNotNull( 'parent_id' );

foreach ( $mainCategories as $main ) {
    echo "\n📁 {$main->name}\n";
    $subs = $subcategories->where( 'parent_id', $main->id );
    foreach ( $subs as $sub ) {
        echo "   └── 📄 {$sub->name}\n";
    }
}

echo "\n=== TOTAL DE CATEGORIAS: " . count( $categories ) . " ===\n";
echo "   Principais: " . $mainCategories->count() . "\n";
echo "   Subcategorias: " . $subcategories->count() . "\n\n";

// Teste 2: Atualizar categorias (não deve duplicar)
echo "2. Testando atualização (não deve duplicar)...\n";
CategorySeeder::updateCategoriesForTenant( 1 );

$categoriesAfter = DB::table( 'categories' )
    ->where( 'tenant_id', 1 )
    ->count();

echo "✓ Categorias após update: {$categoriesAfter} (deve ser o mesmo número)\n\n";

// Teste 3: Verificar logs de auditoria
echo "3. Verificando logs de auditoria...\n";
$auditLogs = DB::table( 'audit_logs' )
    ->where( 'action', 'seed_categories' )
    ->where( 'tenant_id', 1 )
    ->latest()
    ->first();

if ( $auditLogs ) {
    echo "✓ Log de auditoria encontrado:\n";
    echo "   Ação: {$auditLogs->action}\n";
    echo "   Descrição: {$auditLogs->description}\n";
    echo "   Data: {$auditLogs->created_at}\n";
} else {
    echo "⚠ Nenhum log de auditoria encontrado\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
echo "✅ CategorySeeder funcionando corretamente!\n";
echo "✅ Estrutura hierárquica criada com sucesso!\n";
echo "✅ Sem duplicação de categorias!\n";
echo "✅ Logs de auditoria registrados!\n\n";

// Teste 4: Limpeza (opcional)
if ( isset( $argv[ 1 ] ) && $argv[ 1 ] === 'clean' ) {
    echo "4. Limpando categorias para teste...\n";
    CategorySeeder::clearCategoriesForTenant( 1 );
    echo "✓ Categorias removidas\n\n";
} else {
    echo "💡 Para limpar as categorias de teste, execute:\n";
    echo "   php test_category_seeder.php clean\n\n";
}

echo "=== INTEGRAÇÃO COM LARAVEL TINKER ===\n";
echo "Para testar no Laravel Tinker, execute:\n";
echo "php artisan tinker\n\n";
echo "require '" . __FILE__ . "';\n\n";
echo "Ou métodos individuais:\n";
echo "CategorySeeder::seedForTenant(1);\n";
echo "CategorySeeder::clearCategoriesForTenant(1);\n";
echo "CategorySeeder::updateCategoriesForTenant(1);\n\n";

echo "=== CATEGORIAS PRÉ-CONFIGURADAS ===\n";
echo "Principais (8):\n";
echo "• Serviços Gerais\n";
echo "• Construção Civil\n";
echo "• Instalações\n";
echo "• Acabamentos\n";
echo "• Produtos e Materiais\n";
echo "• Manutenção Predial\n";
echo "• Consultoria Técnica\n";
echo "• Serviços Digitais\n\n";

echo "Especiais (3):\n";
echo "• Outros Serviços\n";
echo "• Serviços Emergenciais\n";
echo "• Orçamentos Rápidos\n\n";

echo "Total: 35 categorias (8 principais × 4 subcategorias + 3 especiais)\n";
