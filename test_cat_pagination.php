<?php

// Script simples para testar a paginação do CategoryRepository
require_once __DIR__ . '/vendor/autoload.php';

try {
    echo "=== Testando CategoryRepository Paginação ===\n";

    // Inicializar Laravel
    $app    = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);

    // Boot Laravel
    $app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    // Testar o repositório
    $repo = new \App\Repositories\CategoryRepository();

    // Testar getPaginated com filtros vazios
    echo "Testando getPaginated()...\n";
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
    }

    echo "✅ Teste de paginação concluído com sucesso!\n";

} catch ( Exception $e ) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
