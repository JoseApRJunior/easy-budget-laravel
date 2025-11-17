<?php

declare(strict_types=1);

/**
 * Script para corrigir type hints nível 6
 */

function fixConstructorTypeHints($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Padrões comuns de services que precisam de type hints
    $servicePatterns = [
        '/private\s+(\w*Service)\s+\$(\w+)(?=\s*,|\s*\))/i' => 'private $1 $$$2',
        '/private\s+(\w*Controller)\s+\$(\w+)(?=\s*,|\s*\))/i' => 'private $1 $$$2',
        '/private\s+(\w*Repository)\s+\$(\w+)(?=\s*,|\s*\))/i' => 'private $1 $$$2',
        '/private\s+(\w*Pdf)\s+\$(\w+)(?=\s*,|\s*\))/i' => 'private $1 $$$2',
        '/private\s+(\w*Token)\s+\$(\w+)(?=\s*,|\s*\))/i' => 'private $1 $$$2',
        '/private\s+(\w*Upload)\s+\$(\w+)(?=\s*,|\s*\))/i' => 'private $1 $$$2',
    ];
    
    // Encontrar construtor e aplicar correções
    if (preg_match('/public function __construct\((.*?)\)/s', $content, $matches)) {
        $constructorParams = $matches[1];
        $correctedParams = $constructorParams;
        
        // Aplicar type hints
        foreach ($servicePatterns as $pattern => $replacement) {
            $correctedParams = preg_replace($pattern, $replacement, $correctedParams);
        }
        
        if ($correctedParams !== $constructorParams) {
            $content = str_replace($constructorParams, $correctedParams, $content);
        }
    }
    
    // Adicionar return types para métodos que claramente retornam views
    $content = preg_replace(
        '/public function (index|create|edit|show)\([^)]*\)(?!\s*:\s*\w+)/',
        'public function $1(): \\Illuminate\\View\\View',
        $content
    );
    
    // Adicionar return types para métodos que retornam redirect
    $content = preg_replace(
        '/public function (store|update|destroy|delete)\([^)]*\)(?!\s*:\s*\w+)/',
        'public function $1(): \\Illuminate\\Http\\RedirectResponse',
        $content
    );
    
    // Adicionar return types para métodos API
    $content = preg_replace(
        '/public function (\w*api\w*|\w*json\w*)\([^)]*\)(?!\s*:\s*\w+)/',
        'public function $1(): \\Illuminate\\Http\\JsonResponse',
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        return true;
    }
    
    return false;
}

// Controllers que precisam de correção
$controllers = [
    'app/Http/Controllers/DashboardController.php',
    'app/Http/Controllers/BudgetController.php',
    'app/Http/Controllers/CustomerController.php',
    'app/Http/Controllers/InvoiceController.php',
];

echo "=== CORREÇÃO DE TYPE HINTS NÍVEL 6 ===\n\n";

$fixedCount = 0;

foreach ($controllers as $controller) {
    echo "📄 Corrigindo: $controller\n";
    
    if (fixConstructorTypeHints($controller)) {
        echo "✅ Type hints adicionados com sucesso\n";
        $fixedCount++;
    } else {
        echo "ℹ️  Nenhuma mudança necessária\n";
    }
    echo "\n";
}

echo "=== RESUMO ===\n";
echo "Controllers corrigidos: $fixedCount\n";
echo "\n🎯 Verificando novamente com análise rápida...\n\n";

// Verificar se as correções funcionaram
include 'quick-phpstan6-analysis.php';