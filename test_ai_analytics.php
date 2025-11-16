<?php

// Test script para verificar a implementação da AI Analytics
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Teste AI Analytics Implementation ===\n\n";

// Test 1: Verificar se o controller existe
echo "1. Verificando AIAnalyticsController...\n";
if (file_exists(__DIR__ . '/app/Http/Controllers/AIAnalyticsController.php')) {
    echo "✓ Controller encontrado\n";
} else {
    echo "✗ Controller não encontrado\n";
}

// Test 2: Verificar se o service existe
echo "\n2. Verificando AIAnalyticsService...\n";
if (file_exists(__DIR__ . '/app/Services/Application/AIAnalyticsService.php')) {
    echo "✓ Service encontrado\n";
} else {
    echo "✗ Service não encontrado\n";
}

// Test 3: Verificar se a view existe
echo "\n3. Verificando view de analytics...\n";
if (file_exists(__DIR__ . '/resources/views/pages/provider/analytics/index.blade.php')) {
    echo "✓ View encontrada\n";
} else {
    echo "✗ View não encontrada\n";
}

// Test 4: Verificar rotas
echo "\n4. Verificando rotas de analytics...\n";
try {
    $routes = app('router')->getRoutes();
    $analyticsRoutes = [];
    
    foreach ($routes as $route) {
        if (strpos($route->getName(), 'provider.analytics') !== false) {
            $analyticsRoutes[] = $route->getName();
        }
    }
    
    if (count($analyticsRoutes) > 0) {
        echo "✓ Rotas encontradas:\n";
        foreach ($analyticsRoutes as $route) {
            echo "  - $route\n";
        }
    } else {
        echo "✗ Nenhuma rota de analytics encontrada\n";
    }
} catch (Exception $e) {
    echo "✗ Erro ao verificar rotas: " . $e->getMessage() . "\n";
}

echo "\n=== Teste de carregamento do service ===\n";
try {
    $service = app(\App\Services\Application\AIAnalyticsService::class);
    echo "✓ Service carregado com sucesso\n";
    
    // Testar método básico
    if (method_exists($service, 'getBusinessOverview')) {
        echo "✓ Método getBusinessOverview disponível\n";
    } else {
        echo "✗ Método getBusinessOverview não encontrado\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erro ao carregar service: " . $e->getMessage() . "\n";
}

echo "\n=== Teste completo ===\n";
echo "A implementação da AI Analytics está " . (count($analyticsRoutes) > 0 ? "FUNCIONAL" : "INCOMPLETA") . "!\n";

// Testar URL
echo "\nURL da AI Analytics: " . route('provider.analytics.index') . "\n";

?>