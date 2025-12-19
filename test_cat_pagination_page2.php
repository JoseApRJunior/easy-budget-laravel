<?php

// Teste de navegação para página 2
require_once __DIR__ . '/vendor/autoload.php';

try {
    echo "=== Testando Paginação - Página 2 ===\n";

    // Inicializar Laravel
    $app    = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);
    $app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    // Simular requisição para página 2
    $_GET[ 'page' ] = '2';

    $repo = new \App\Repositories\CategoryRepository();

    // Testar getPaginated com página 2
    echo "Testando getPaginated() com page=2...\n";
    $paginator = $repo->getPaginated( [], 5 );

    echo "Total itens: " . $paginator->total() . "\n";
    echo "Itens por página: " . $paginator->perPage() . "\n";
    echo "Página atual: " . $paginator->currentPage() . "\n";
    echo "Itens nesta página: " . $paginator->count() . "\n";
    echo "Tem mais páginas: " . ( $paginator->hasMorePages() ? "Sim" : "Não" ) . "\n";

    if ( $paginator->total() > 0 ) {
        echo "Primeiro item: " . $paginator->firstItem() . "\n";
        echo "Último item: " . $paginator->lastItem() . "\n";
        echo "Primeira categoria da página 2: " . $paginator->first()->name . "\n";
        echo "Última categoria da página 2: " . $paginator->last()->name . "\n";
    }

    echo "✅ Página 2 funciona corretamente!\n";

    // Testar também sem página (deve voltar para página 1)
    unset( $_GET[ 'page' ] );
    echo "\nTestando sem parâmetro de página...\n";
    $paginator1 = $repo->getPaginated( [], 5 );
    echo "Página atual: " . $paginator1->currentPage() . "\n";
    echo "Primeira categoria da página 1: " . $paginator1->first()->name . "\n";

} catch ( Exception $e ) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
