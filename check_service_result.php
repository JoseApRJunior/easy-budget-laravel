<?php

/**
 * Script para verificar os métodos da classe ServiceResult
 */

require_once __DIR__ . '/vendor/autoload.php';

$app    = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Verificando métodos da classe ServiceResult ===\n\n";

// Criar uma instância de ServiceResult
$result = new App\Support\ServiceResult();

echo "Métodos disponíveis na classe ServiceResult:\n";
$methods = get_class_methods( $result );
foreach ( $methods as $method ) {
    echo "- $method\n";
}

echo "\n=== Verificando métodos relacionados a mensagens de erro ===\n";
foreach ( $methods as $method ) {
    if ( stripos( $method, 'error' ) !== false || stripos( $method, 'message' ) !== false || stripos( $method, 'fail' ) !== false ) {
        echo "🔍 Método relacionado a erro/encontrado: $method\n";

        // Tentar usar reflection para ver se é getter
        $reflection = new ReflectionMethod( $result, $method );
        if ( $reflection->getNumberOfParameters() === 0 ) {
            echo "   - É um getter (sem parâmetros)\n";
        } else {
            echo "   - Requer parâmetros\n";
        }
    }
}
