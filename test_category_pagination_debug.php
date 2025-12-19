<?php

echo "=== DEBUGGING DA PAGINAÇÃO DE CATEGORIAS ===" . PHP_EOL;

try {
    // Configurar autoloader
    require_once 'vendor/autoload.php';

    // Inicializar aplicação Laravel
    $app    = require_once 'bootstrap/app.php';
    $kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);

    echo "✅ Laravel carregado com sucesso" . PHP_EOL;

    // Simular requisição para a página de categorias
    $request = Illuminate\Http\Request::create( '/categories', 'GET', [
        'search'   => '',
        'active'   => '',
        'per_page' => 10,
        'deleted'  => '',
        'page'     => 1
    ] );

    echo "✅ Requisição simulada criada" . PHP_EOL;

    // Simular usuário autenticado com tenant_id
    $user            = new stdClass();
    $user->tenant_id = 1;

    // Fazer bind do usuário na aplicação
    $app->instance( 'user', $user );

    echo "✅ Usuário simulado configurado (tenant_id: 1)" . PHP_EOL;

    // Testar CategoryRepository diretamente
    echo PHP_EOL . "=== TESTE DIRETO DO CATEGORYREPOSITORY ===" . PHP_EOL;

    $repo = new App\Repositories\CategoryRepository();

    echo "✅ CategoryRepository instanciado" . PHP_EOL;

    // Testar método getPaginated
    $filters = [ 'deleted' => '', 'active' => '', 'search' => '' ];

    echo "📋 Testando getPaginated() com filtros: " . json_encode( $filters ) . PHP_EOL;

    $result = $repo->getPaginated( $filters, 10, [], [ 'name' => 'asc' ] );

    echo "✅ getPaginated() executado com sucesso!" . PHP_EOL;
    echo "📊 Resultados: " . $result->total() . " categorias encontradas" . PHP_EOL;
    echo "📄 Página atual: " . $result->currentPage() . " de " . $result->lastPage() . PHP_EOL;
    echo "🔢 Itens por página: " . $result->perPage() . PHP_EOL;

    // Testar método getPaginated com filtro deleted
    echo PHP_EOL . "=== TESTE COM FILTRO DELETED ===" . PHP_EOL;

    $filtersDeleted = [ 'deleted' => 'only' ];
    $resultDeleted  = $repo->getPaginated( $filtersDeleted, 10, [], [ 'name' => 'asc' ] );

    echo "✅ getPaginated() com filtro deleted executado!" . PHP_EOL;
    echo "📊 Resultados deletados: " . $resultDeleted->total() . " categorias encontradas" . PHP_EOL;

    // Testar CategoryService
    echo PHP_EOL . "=== TESTE DO CATEGORYSERVICE ===" . PHP_EOL;

    $service = new App\Services\Domain\CategoryService( $repo );

    echo "✅ CategoryService instanciado" . PHP_EOL;

    // Testar método getCategories
    $serviceFilters = [ 'deleted' => '', 'active' => '', 'per_page' => 10 ];
    $serviceResult  = $service->getCategories( $serviceFilters );

    if ( $serviceResult->isSuccess() ) {
        echo "✅ CategoryService->getCategories() executado com sucesso!" . PHP_EOL;
        $paginator = $serviceResult->getData();
        echo "📊 Resultados do service: " . $paginator->total() . " categorias encontradas" . PHP_EOL;
    } else {
        echo "❌ CategoryService->getCategories() falhou!" . PHP_EOL;
        echo "❌ Erro: " . $serviceResult->getMessage() . PHP_EOL;
    }

} catch ( Exception $e ) {
    echo "❌ ERRO: " . $e->getMessage() . PHP_EOL;
    echo "📍 Arquivo: " . $e->getFile() . " Linha: " . $e->getLine() . PHP_EOL;

    // Mostrar stack trace se disponível
    if ( function_exists( 'xdebug_get_trace' ) ) {
        echo PHP_EOL . "🔍 Stack trace:" . PHP_EOL;
        echo $e->getTraceAsString() . PHP_EOL;
    }
}

echo PHP_EOL . "=== TESTE CONCLUÍDO ===" . PHP_EOL;
