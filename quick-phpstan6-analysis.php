<?php

declare(strict_types=1);

/**
 * Análise rápida de erros PHPStan nível 6
 */

function analyzeFile($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    
    $content = file_get_contents($filePath);
    $errors = [];
    
    // Verificações básicas do nível 6
    
    // 1. Verificar type hints em métodos públicos
    if (preg_match_all('/public function (\w+)\(([^)]*)\)(?::\s*([^\s{]+))?/', $content, $matches)) {
        foreach ($matches[0] as $i => $fullMatch) {
            $methodName = $matches[1][$i];
            $params = $matches[2][$i];
            $returnType = $matches[3][$i] ?? null;
            
            // Verificar se método público tem return type
            if (empty($returnType) && !in_array($methodName, ['__construct', '__destruct'])) {
                $lineNum = getLineNumber($content, $fullMatch);
                $errors[] = "Método público '$methodName' sem tipo de retorno (linha $lineNum)";
            }
            
            // Verificar parâmetros sem tipo
            if (!empty($params)) {
                $paramList = explode(',', $params);
                foreach ($paramList as $param) {
                    $param = trim($param);
                    if (!empty($param) && !preg_match('/^(\w+\s+)?\$\w+/', $param) && !preg_match('/:\s*\w+/', $param)) {
                        $lineNum = getLineNumber($content, $param);
                        $errors[] = "Parâmetro sem tipo: '$param' (linha $lineNum)";
                    }
                }
            }
        }
    }
    
    // 2. Verificar propriedades sem tipo
    if (preg_match_all('/(public|protected|private) \$(\w+)(?:\s*=|;)/', $content, $matches)) {
        foreach ($matches[0] as $i => $fullMatch) {
            if (!preg_match('/:\s*\w+/', $fullMatch)) {
                $lineNum = getLineNumber($content, $fullMatch);
                $propName = $matches[2][$i];
                $errors[] = "Propriedade '\$$propName' sem tipo (linha $lineNum)";
            }
        }
    }
    
    // 3. Verificar uso de mixed
    if (preg_match_all('/\$\w+\s*=\s*([^;]+)/', $content, $matches)) {
        foreach ($matches[1] as $i => $assignment) {
            if (preg_match('/array\(|json_decode|unserialize/', $assignment)) {
                $lineNum = getLineNumber($content, $matches[0][$i]);
                $errors[] = "Possível tipo misto detectado (linha $lineNum)";
            }
        }
    }
    
    return $errors;
}

function getLineNumber($content, $search) {
    $lines = explode("\n", $content);
    foreach ($lines as $i => $line) {
        if (strpos($line, $search) !== false) {
            return $i + 1;
        }
    }
    return 0;
}

// Analisar controllers principais
$files = [
    'app/Http/Controllers/DashboardController.php',
    'app/Http/Controllers/BudgetController.php',
    'app/Http/Controllers/CustomerController.php',
    'app/Http/Controllers/InvoiceController.php',
    'app/Http/Controllers/ProviderController.php',
    'app/Http/Controllers/UserController.php',
];

echo "=== ANÁLISE RÁPIDA PHPSTAN NÍVEL 6 ===\n\n";

$totalErrors = 0;

foreach ($files as $file) {
    echo "📄 Analisando: $file\n";
    $errors = analyzeFile($file);
    
    if (empty($errors)) {
        echo "✅ Sem erros críticos encontrados\n";
    } else {
        $totalErrors += count($errors);
        foreach ($errors as $error) {
            echo "❌ $error\n";
        }
    }
    echo "\n";
}

echo "=== RESUMO ===\n";
echo "Total de possíveis erros nível 6: $totalErrors\n\n";

if ($totalErrors === 0) {
    echo "🎉 Excelente! Os controllers principais estão em conformidade com o nível 6!\n";
} else {
    echo "⚠️  Foram encontrados $totalErrors possíveis problemas de tipagem.\n";
    echo "💡 Recomendação: Adicionar type hints e tipos de retorno onde indicado.\n";
}