<?php

/**
 * Teste específico para simular o cenário do log de erro
 * Verifica se a correção resolve o problema de referência circular
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

// Configurar Laravel para ambiente de teste
$app    = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTE: Cenário do Log de Erro ===\n";
echo "Simulando tentativa de criar 'Sub Categoria Admin' com parent_id='6'\n\n";

// Simular o cenário exato do log
try {
    echo "1. Tentando criar categoria cross-tenant...\n";

    // Simulamos que existe uma categoria com id=6 que pertence a outro tenant
    $category6 = App\Models\Category::factory()->create( [
        'id'        => 6,
        'tenant_id' => 2, // Different tenant
        'name'      => "Categoria de Outro Tenant",
        'slug'      => "categoria-outro-tenant",
        'parent_id' => null,
    ] );

    echo "Categoria parent criada:\n";
    echo "- ID: {$category6->id}\n";
    echo "- Tenant ID: {$category6->tenant_id}\n";
    echo "- Nome: {$category6->name}\n\n";

    // Tentativa de criar categoria em tenant_id=1 com parent_id=6
    $service = new App\Services\Domain\CategoryService(
        app( App\Repositories\CategoryRepository::class),
    );

    $data = [
        'tenant_id' => 1,
        'name'      => 'Sub Categoria Admin',
        'parent_id' => 6,
        'is_active' => true
    ];

    echo "Dados da categoria a ser criada:\n";
    echo "- Nome: {$data[ 'name' ]}\n";
    echo "- Tenant ID: {$data[ 'tenant_id' ]}\n";
    echo "- Parent ID: {$data[ 'parent_id' ]}\n";
    echo "- Tenant da categoria parent: {$category6->tenant_id}\n\n";

    $result = $service->createCategory( $data, 1 );

    if ( $result->isSuccess() ) {
        echo "❌ ERRO: Categoria foi criada indevidamente!\n";
    } else {
        echo "✅ SUCESSO: Categoria foi bloqueada corretamente\n";
        echo "Mensagem de erro: " . $result->getErrorMessage() . "\n";

        // Verificar se a mensagem de erro está correta
        $errorMessage = $result->getErrorMessage();
        if ( strpos( $errorMessage, 'parent category' ) !== false ) {
            echo "✅ PERFEITO: Mensagem de erro correta (parent category)\n";
        } elseif ( strpos( $errorMessage, 'circular reference' ) !== false ) {
            echo "❌ PROBLEMA: Mensagem de erro incorreta (circular reference)\n";
        } else {
            echo "⚠️  AVISO: Mensagem de erro inesperada: $errorMessage\n";
        }
    }

} catch ( Exception $e ) {
    echo "❌ Exceção inesperada: " . $e->getMessage() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";

// Limpar dados de teste
try {
    App\Models\Category::where( 'id', 6 )->delete();
} catch ( Exception $e ) {
    // Ignorar erros de limpeza
}
