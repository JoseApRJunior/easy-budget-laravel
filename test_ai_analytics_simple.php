<?php

// Test script simples para verificar a implementação da AI Analytics
require_once __DIR__ . '/vendor/autoload.php';

echo "=== Teste AI Analytics Implementation ===\n\n";

// Test 1: Verificar se os arquivos existem
echo "1. Verificando arquivos...\n";
$files = [
    __DIR__ . '/app/Http/Controllers/AIAnalyticsController.php',
    __DIR__ . '/app/Services/Application/AIAnalyticsService.php',
    __DIR__ . '/resources/views/pages/provider/analytics/index.blade.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ " . basename($file) . " encontrado\n";
    } else {
        echo "✗ " . basename($file) . " não encontrado\n";
    }
}

echo "\n2. Verificando rotas no arquivo...\n";
$routesFile = __DIR__ . '/routes/web.php';
if (file_exists($routesFile)) {
    $content = file_get_contents($routesFile);
    if (strpos($content, 'AIAnalyticsController') !== false) {
        echo "✓ AIAnalyticsController importado\n";
    } else {
        echo "✗ AIAnalyticsController não importado\n";
    }
    
    if (strpos($content, 'provider.analytics') !== false) {
        echo "✓ Rotas de analytics definidas\n";
    } else {
        echo "✗ Rotas de analytics não definidas\n";
    }
}

echo "\n3. URL esperada:\n";
echo "https://dev.easybudget.net.br/provider/analytics\n";

echo "\n=== Teste concluído ===\n";

// Salvar log
$logFile = __DIR__ . '/test_ai_analytics.log';
file_put_contents($logFile, ob_get_contents());
echo "\nLog salvo em: $logFile\n";

?>