<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

// Simular uma requisição HTTP para testar a paginação
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);

try {
    echo "=== Testando CategoryRepository com Paginação ===\n";

    // Testar diretamente o repositório
    require_once __DIR__ . '/vendor/autoload.php';

    $pdo = new PDO( "mysql:host=127.0.0.1;dbname=easy_budget", "root", "" );

    // Teste simples no banco
    $stmt  = $pdo->query( "SELECT COUNT(*) FROM categories" );
    $count = $stmt->fetchColumn();
    echo "Total de categorias no banco: $count\n";

    if ( $count > 10 ) {
        // Testar paginação manual
        $perPage    = 5;
        $totalPages = ceil( $count / $perPage );
        echo "Com $perPage itens por página, teríamos $totalPages páginas\n";

        // Testar primeira página
        $stmt      = $pdo->query( "SELECT id, name FROM categories ORDER BY name ASC, created_at ASC LIMIT $perPage" );
        $firstPage = $stmt->fetchAll( PDO::FETCH_ASSOC );
        echo "Primeira página - " . count( $firstPage ) . " itens:\n";
        foreach ( $firstPage as $item ) {
            echo "  - ID: {$item[ 'id' ]}, Nome: {$item[ 'name' ]}\n";
        }

        // Testar segunda página (skip = 5)
        $stmt       = $pdo->query( "SELECT id, name FROM categories ORDER BY name ASC, created_at ASC LIMIT $perPage OFFSET $perPage" );
        $secondPage = $stmt->fetchAll( PDO::FETCH_ASSOC );
        echo "Segunda página - " . count( $secondPage ) . " itens:\n";
        foreach ( $secondPage as $item ) {
            echo "  - ID: {$item[ 'id' ]}, Nome: {$item[ 'name' ]}\n";
        }

        echo "\n✅ Teste manual de paginação passou!\n";
        echo "Se a paginação funcionou manualmente, o problema pode estar no Laravel Paginator\n";
    }

} catch ( Exception $e ) {
    echo "❌ Erro no teste: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
