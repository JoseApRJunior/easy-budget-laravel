<?php

declare(strict_types=1);

/**
 * Script para executar PHPStan nível 6 e capturar erros específicos
 */

$directories = [
    'app/Http/Controllers',
    'app/Models', 
    'app/Services',
    'app/Mail',
];

echo "=== ANÁLISE PHPSTAN NÍVEL 6 ===\n\n";

$totalErrors = 0;
$totalFiles = 0;

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        echo "⚠️  Diretório não encontrado: $dir\n";
        continue;
    }
    
    echo "📁 Analisando: $dir\n";
    
    // Executar PHPStan para este diretório
    $cmd = "php vendor/bin/phpstan analyse $dir --level=6 --no-progress 2>&1";
    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);
    
    $outputStr = implode("\n", $output);
    
    if ($returnCode === 0) {
        echo "✅ Sem erros encontrados\n";
    } else {
        // Contar erros
        preg_match_all('/Error|error/', $outputStr, $errorMatches);
        $errorCount = count($errorMatches[0]);
        $totalErrors += $errorCount;
        
        // Mostrar primeiras linhas de erro
        $lines = array_slice($output, 0, 10);
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                echo "   $line\n";
            }
        }
        
        if (count($output) > 10) {
            echo "   ... e mais " . (count($output) - 10) . " linhas\n";
        }
    }
    
    echo "\n";
}

echo "=== RESUMO ===\n";
echo "Total de erros encontrados: $totalErrors\n";

if ($totalErrors === 0) {
    echo "🎉 PARABÉNS! Nenhum erro encontrado no nível 6!\n";
} else {
    echo "⚠️  Foram encontrados $totalErrors erros que precisam de atenção.\n";
}