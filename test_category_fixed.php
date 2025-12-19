<?php
// Teste direto do CategoryService após correção

require_once 'vendor/autoload.php';

try {
    // Configurar Laravel
    $app    = require_once 'bootstrap/app.php';
    $kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);

    // Simular autenticação de usuário
    $user            = new App\Models\User();
    $user->id        = 1;
    $user->tenant_id = 1;
    auth()->login( $user );

    // Criar request
    $request = Illuminate\Http\Request::create( '/categories', 'GET', [
        'deleted'  => 'only',
        'active'   => '1',
        'per_page' => 10
    ] );

    // Instanciar serviço e controller
    $categoryRepository = new App\Repositories\CategoryRepository();
    $categoryService    = new App\Services\Domain\CategoryService( $categoryRepository );
    $controller         = new App\Http\Controllers\CategoryController( $categoryService );

    // Testar método index
    $response = $controller->index( $request );

    echo "✅ SUCCESS: CategoryController->index() funcionou!" . PHP_EOL;
    echo "Response type: " . get_class( $response ) . PHP_EOL;
    echo "Response status: " . $response->getStatusCode() . PHP_EOL;

} catch ( Exception $e ) {
    echo "❌ ERROR: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
}
