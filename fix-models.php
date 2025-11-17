<?php

declare(strict_types=1);

/**
 * Script para corrigir models faltando import de Model
 */

function fixModelImports($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    
    // Verificar se extende Model
    $extendsModel = false;
    $hasModelImport = false;
    $useLines = [];
    $lastUseLine = -1;
    $namespaceLine = -1;
    
    foreach ($lines as $i => $line) {
        if (preg_match('/^namespace\s+(.+);$/', $line, $matches)) {
            $namespaceLine = $i;
        }
        if (preg_match('/^use\s+(.+);$/', $line, $matches)) {
            $lastUseLine = $i;
            if ($matches[1] === 'Illuminate\Database\Eloquent\Model') {
                $hasModelImport = true;
            }
        }
        if (preg_match('/class\s+\w+\s+extends\s+Model/', $line)) {
            $extendsModel = true;
        }
    }
    
    // Se extende Model mas não tem import, adicionar
    if ($extendsModel && !$hasModelImport) {
        $insertPosition = $lastUseLine >= 0 ? $lastUseLine + 1 : $namespaceLine + 1;
        array_splice($lines, $insertPosition, 0, ['use Illuminate\Database\Eloquent\Model;']);
        
        $newContent = implode("\n", $lines);
        file_put_contents($filePath, $newContent);
        return true;
    }
    
    return false;
}

// Encontrar todos os models
$modelFiles = glob('app/Models/*.php');
$fixedCount = 0;

echo "Verificando models com import de Model faltando...\n\n";

foreach ($modelFiles as $file) {
    if (fixModelImports($file)) {
        echo "Corrigido: $file\n";
        $fixedCount++;
    }
}

echo "\nTotal de models corrigidos: $fixedCount\n";

// Testar se o artisan serve funciona agora
echo "\nTestando php artisan serve...\n";
exec('php artisan serve --port=8001 > /dev/null 2>&1 & echo $!', $output);
$pid = trim($output[0] ?? '');

if (!empty($pid)) {
    echo "✅ Servidor iniciado com sucesso! PID: $pid\n";
    echo "Verificando se está respondendo...\n";
    sleep(2);
    
    $ch = curl_init('http://localhost:8001');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode > 0) {
        echo "✅ Servidor respondendo! HTTP Code: $httpCode\n";
    } else {
        echo "⚠️  Servidor não está respondendo\n";
    }
    
    // Matar o processo de teste
    exec("taskkill /F /PID $pid 2>nul");
} else {
    echo "❌ Falha ao iniciar servidor\n";
}