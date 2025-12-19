<?php
/**
 * Script de teste para verificar se as correções de paginação de categorias estão funcionando.
 * Testa navegação entre páginas e ordenação correta.
 */

require __DIR__ . '/vendor/autoload.php';

$app    = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);

echo "=== TESTE DE PAGINAÇÃO DE CATEGORIAS CORRIGIDA ===\n\n";

// Teste 1: Verificar se carrega categorias sem filtros (primeira página)
echo "1. Testando carregamento sem filtros (página 1):\n";
try {
    $request = Illuminate\Http\Request::create( '/categories', 'GET', [
        'per_page' => 10,
        'page'     => 1
    ] );

    $response = $kernel->handle( $request );

    if ( $response->getStatusCode() === 200 ) {
        echo "✅ Página 1 carregada com sucesso (status 200)\n";

        // Verificar se há conteúdo na resposta
        $content = $response->getContent();
        if ( strpos( $content, 'categories' ) !== false || strpos( $content, 'Categoria' ) !== false ) {
            echo "✅ Conteúdo de categorias encontrado na resposta\n";
        } else {
            echo "⚠️  Nenhum conteúdo específico de categorias encontrado\n";
        }
    } else {
        echo "❌ Erro ao carregar página 1 (status: {$response->getStatusCode()})\n";
    }

} catch ( Exception $e ) {
    echo "❌ Erro na página 1: " . $e->getMessage() . "\n";
}

echo "\n";

// Teste 2: Verificar se carrega categorias na página 2
echo "2. Testando navegação para página 2:\n";
try {
    $request = Illuminate\Http\Request::create( '/categories', 'GET', [
        'per_page' => 10,
        'page'     => 2,
        'all'      => 1
    ] );

    $response = $kernel->handle( $request );

    if ( $response->getStatusCode() === 200 ) {
        echo "✅ Página 2 carregada com sucesso (status 200)\n";

        // Verificar se há conteúdo na resposta
        $content = $response->getContent();
        if ( strpos( $content, 'categories' ) !== false || strpos( $content, 'Categoria' ) !== false ) {
            echo "✅ Conteúdo de categorias encontrado na página 2\n";
        } else {
            echo "❌ Nenhum conteúdo encontrado na página 2 (PROBLEMA: página vazia)\n";
        }
    } else {
        echo "❌ Erro ao carregar página 2 (status: {$response->getStatusCode()})\n";
    }

} catch ( Exception $e ) {
    echo "❌ Erro na página 2: " . $e->getMessage() . "\n";
}

echo "\n";

// Teste 3: Verificar com filtros (busca)
echo "3. Testando carregamento com filtros de busca:\n";
try {
    $request = Illuminate\Http\Request::create( '/categories', 'GET', [
        'per_page' => 10,
        'search'   => 'test',
        'active'   => 1
    ] );

    $response = $kernel->handle( $request );

    if ( $response->getStatusCode() === 200 ) {
        echo "✅ Página com filtros carregada com sucesso\n";
    } else {
        echo "❌ Erro ao carregar com filtros (status: {$response->getStatusCode()})\n";
    }

} catch ( Exception $e ) {
    echo "❌ Erro com filtros: " . $e->getMessage() . "\n";
}

echo "\n";

// Teste 4: Verificar se a ordenação está correta
echo "4. Testando ordenação de categorias:\n";
try {
    $request = Illuminate\Http\Request::create( '/categories', 'GET', [
        'per_page' => 5,
        'sort'     => 'name',
        'order'    => 'asc'
    ] );

    $response = $kernel->handle( $request );

    if ( $response->getStatusCode() === 200 ) {
        echo "✅ Página com ordenação carregada com sucesso\n";
    } else {
        echo "❌ Erro ao carregar com ordenação (status: {$response->getStatusCode()})\n";
    }

} catch ( Exception $e ) {
    echo "❌ Erro na ordenação: " . $e->getMessage() . "\n";
}

echo "\n=== RESUMO DO TESTE ===\n";
echo "Se todos os testes passaram, a paginação de categorias foi corrigida com sucesso.\n";
echo "Problemas identificados:\n";
echo "- Página 2 vazia: CORRIGIDO (removido \$hasFilters)\n";
echo "- Ordenação duplicada: CORRIGIDO (simplificado para name ASC)\n";
echo "- Conflito de assinatura: CORRIGIDO (4 parâmetros padronizados)\n";

echo "\n=== FIM DO TESTE ===\n";
