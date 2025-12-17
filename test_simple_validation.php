<?php

/**
 * Teste simples para verificar se a validação de referência circular está funcionando corretamente
 * Testa os métodos diretamente sem criar dados no banco
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

// Configurar Laravel para ambiente de teste
$app    = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTE: Validação de Referência Circular ===\n\n";

// Teste 1: Verificar se wouldCreateCircularReference funciona corretamente
echo "1. Testando método wouldCreateCircularReference...\n";

try {
    // Criar uma categoria mock
    $category            = new App\Models\Category();
    $category->id        = 1;
    $category->tenant_id = 1;
    $category->name      = "Categoria Teste";
    $category->parent_id = null;

    // Testar referência direta a si mesmo (caso extremo)
    $category->parent_id = 1; // Categoria apontando para si mesma
    $result1             = $category->wouldCreateCircularReference( 1 );
    echo "   - Referência a si mesmo: " . ( $result1 ? "✅ Detectado" : "❌ Não detectado" ) . "\n";

    // Testar referência válida (null)
    $result2 = $category->wouldCreateCircularReference( 0 );
    echo "   - Referência nula: " . ( !$result2 ? "✅ Permitido" : "❌ Bloqueado" ) . "\n";

} catch ( Exception $e ) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n2. Testando validação de tenant com autenticação simulada...\n";

try {
    // Simular usuário autenticado
    $mockUser = new class
    {
        public function __construct()
        {
            $this->tenant_id = 1;
        }

        public function __get( $name )
        {
            if ( $name === 'tenant_id' ) {
                return $this->tenant_id;
            }
            return null;
        }

    };

    // Simular facade auth
    Illuminate\Support\Facades\Auth::shouldReceive( 'user' )
        ->andReturn( $mockUser );

    $service = new App\Services\Domain\CategoryService(
        app( App\Repositories\CategoryRepository::class),
    );

    // Simular dados de categoria cross-tenant
    $data = [
        'name'      => 'Sub Categoria Admin',
        'parent_id' => 6, // Parent que pertence a outro tenant
        'is_active' => true
    ];

    echo "   - Tentando criar categoria cross-tenant\n";
    echo "   - Usuário tenant_id: 1\n";
    echo "   - Dados: " . json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";

    // O serviço deve detectar que o parent_id=6 não pertence ao tenant_id=1
    $result = $service->createCategory( $data );

    if ( $result->isSuccess() ) {
        echo "   ❌ ERRO: Categoria foi criada indevidamente\n";
    } else {
        echo "   ✅ SUCESSO: Categoria foi bloqueada\n";
        echo "   - Mensagem: " . $result->getMessage() . "\n";

        // Verificar se a mensagem é adequada
        $errorMessage = $result->getMessage();
        if (
            strpos( $errorMessage, 'parent' ) !== false ||
            strpos( $errorMessage, 'pai' ) !== false ||
            strpos( $errorMessage, 'inválida' ) !== false
        ) {
            echo "   ✅ PERFEITO: Mensagem de erro adequada (categoria pai inválida)\n";
        } elseif ( strpos( $errorMessage, 'circular reference' ) !== false ) {
            echo "   ❌ PROBLEMA: Mensagem de erro incorreta (circular reference)\n";
        } elseif ( strpos( $errorMessage, 'tenant' ) !== false ) {
            echo "   ❌ PROBLEMA: Mensagem de erro incorreta (tenant)\n";
        } else {
            echo "   ⚠️  AVISO: Mensagem de erro inesperada\n";
        }
    }

} catch ( Exception $e ) {
    echo "   ❌ Exceção: " . $e->getMessage() . "\n";
}

echo "\n3. Testando validação de referência circular direta...\n";

try {
    // Simular usuário autenticado
    Illuminate\Support\Facades\Auth::shouldReceive( 'user' )
        ->andReturn( $mockUser );

    $service = new App\Services\Domain\CategoryService(
        app( App\Repositories\CategoryRepository::class),
    );

    // Simular dados de categoria com referência circular (tentativa de criar categoria como pai de si mesma)
    $data = [
        'name'      => 'Categoria Circular',
        'parent_id' => 999, // ID inexistente (simulando categoria que não existe)
        'is_active' => true
    ];

    echo "   - Tentando criar categoria com parent_id inexistente\n";
    echo "   - Usuário tenant_id: 1\n";
    echo "   - Dados: " . json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";

    $result = $service->createCategory( $data );

    if ( $result->isSuccess() ) {
        echo "   ❌ ERRO: Categoria foi criada indevidamente\n";
    } else {
        echo "   ✅ SUCESSO: Categoria foi bloqueada\n";
        echo "   - Mensagem: " . $result->getMessage() . "\n";

        // Verificar se a mensagem é adequada
        $errorMessage = $result->getMessage();
        if (
            strpos( $errorMessage, 'parent' ) !== false ||
            strpos( $errorMessage, 'pai' ) !== false ||
            strpos( $errorMessage, 'inválida' ) !== false
        ) {
            echo "   ✅ PERFEITO: Mensagem de erro adequada (categoria pai inválida)\n";
        } else {
            echo "   ⚠️  AVISO: Mensagem: " . $errorMessage . "\n";
        }
    }

} catch ( Exception $e ) {
    echo "   ❌ Exceção: " . $e->getMessage() . "\n";
}

echo "\n=== RESULTADO ===\n";
echo "A correção implementada deve resolver o problema do log de erro.\n";
echo "O sistema agora deve retornar 'Categoria pai inválida' ao invés de 'circular reference'.\n\n";

// Verificar se a correção está aplicada
echo "Verificando correções aplicadas:\n";
$storeRequestPath = __DIR__ . '/app/Http/Requests/StoreCategoryRequest.php';
$servicePath      = __DIR__ . '/app/Services/Domain/CategoryService.php';

if ( file_exists( $storeRequestPath ) ) {
    $content = file_get_contents( $storeRequestPath );
    if ( strpos( $content, 'wouldCreateCircularReference' ) === false ) {
        echo "✅ StoreCategoryRequest: Validação de referência circular removida\n";
    } else {
        echo "⚠️  StoreCategoryRequest: Ainda contém validação de referência circular\n";
    }
}

if ( file_exists( $servicePath ) ) {
    $content = file_get_contents( $servicePath );
    if ( strpos( $content, '$tempCategory->wouldCreateCircularReference' ) !== false ) {
        echo "✅ CategoryService: Correção aplicada (tempCategory)\n";
    } else {
        echo "⚠️  CategoryService: Pode precisar de correção adicional\n";
    }

    // Verificar se updateCategory também foi corrigido
    if ( strpos( $content, '$parentCategory->wouldCreateCircularReference((int) $data[\'parent_id\'])' ) !== false ) {
        echo "⚠️  CategoryService: updateCategory ainda tem problema (linha ~237)\n";
    } else {
        echo "✅ CategoryService: updateCategory parece estar correto\n";
    }
}
