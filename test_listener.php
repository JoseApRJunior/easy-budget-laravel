<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make( \Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTE DE INSTANCIAÇÃO DO LISTENER ===\n\n";

try {
    // Tentar criar o listener
    $listener = app( \App\Listeners\SendEmailVerification::class);
    echo "✅ Listener SendEmailVerification criado com sucesso!\n";
    echo "📋 Classe: " . get_class( $listener ) . "\n";

    // Verificar se implementa ShouldQueue
    if ( $listener instanceof \Illuminate\Contracts\Queue\ShouldQueue ) {
        echo "✅ Implementa ShouldQueue\n";
        echo "🔄 Tries: {$listener->tries}\n";
        echo "⏱️ Backoff: {$listener->backoff}s\n";
    } else {
        echo "❌ Não implementa ShouldQueue\n";
    }

} catch ( Exception $e ) {
    echo "❌ Erro ao criar listener: " . $e->getMessage() . "\n";
    echo "📂 Arquivo: " . $e->getFile() . "\n";
    echo "📍 Linha: " . $e->getLine() . "\n";
    echo "🔍 Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
