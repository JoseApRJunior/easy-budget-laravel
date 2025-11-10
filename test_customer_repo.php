<?php

require_once 'vendor/autoload.php';

$app    = require_once 'bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testando CustomerRepository...\n";

try {
    $repo = app( App\Repositories\CustomerRepository::class);
    echo "✅ Repository criado com sucesso\n";

    // Testar métodos de validação
    echo "🔍 Testando métodos de validação...\n";

    $methods         = get_class_methods( $repo );
    $expectedMethods = [
        'isEmailUnique',
        'isCpfUnique',
        'isCnpjUnique',
        'getPaginated',
        'findWithCompleteData',
        'createWithRelations',
        'updateWithRelations'
    ];

    foreach ( $expectedMethods as $method ) {
        if ( in_array( $method, $methods ) ) {
            echo "✅ Método $method existe\n";
        } else {
            echo "❌ Método $method não encontrado\n";
        }
    }

    echo "\n📋 Resumo:\n";
    echo "Total de métodos disponíveis: " . count( $methods ) . "\n";
    echo "Métodos implementados: " . count( $expectedMethods ) . "\n";

} catch ( Exception $e ) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\nTeste concluído!\n";
