<?php

// Teste mais realista usando Request do Laravel
require_once __DIR__ . '/vendor/autoload.php';

try {
    echo "=== Testando Paginação com Request do Laravel ===\n";

    // Inicializar Laravel
    $app    = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);
    $app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $repo = new \App\Repositories\CategoryRepository();

    // Simular Request com página 2 usando Illuminate\Http\Request
    $request = \Illuminate\Http\Request::create( '/categories?page=2', 'GET' );
    app()->instance( 'request', $request );

    echo "URL simulada: " . $request->url() . "\n";
    echo "Query string: " . $request->getQueryString() . "\n";
    echo "Page parameter: " . $request->get( 'page', 'default' ) . "\n";

    // Testar getPaginated com request
    echo "\nTestando getPaginated() com request...\n";
    $paginator = $repo->getPaginated( [], 5 );

    echo "Total itens: " . $paginator->total() . "\n";
    echo "Itens por página: " . $paginator->perPage() . "\n";
    echo "Página atual: " . $paginator->currentPage() . "\n";
    echo "Itens nesta página: " . $paginator->count() . "\n";
    echo "Tem mais páginas: " . ( $paginator->hasMorePages() ? "Sim" : "Não" ) . "\n";

    if ( $paginator->total() > 0 ) {
        echo "Primeiro item: " . $paginator->firstItem() . "\n";
        echo "Último item: " . $paginator->lastItem() . "\n";
        echo "Primeira categoria: " . $paginator->first()->name . "\n";
        echo "Última categoria: " . $paginator->last()->name . "\n";
    }

    // Testar também página 3
    $request3 = \Illuminate\Http\Request::create( '/categories?page=3', 'GET' );
    app()->instance( 'request', $request3 );

    echo "\nTestando página 3...\n";
    $paginator3 = $repo->getPaginated( [], 5 );
    echo "Página atual: " . $paginator3->currentPage() . "\n";
    echo "Primeira categoria da página 3: " . $paginator3->first()->name . "\n";
    echo "Última categoria da página 3: " . $paginator3->last()->name . "\n";

} catch ( Exception $e ) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
