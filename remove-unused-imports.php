<?php

declare(strict_types=1);

/**
 * Script para remover imports não utilizados
 */

function removeUnusedImports($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    $imports = [];
    $namespace = '';
    
    // Coletar imports e namespace
    foreach ($lines as $i => $line) {
        if (preg_match('/^namespace\s+(.+);$/', $line, $matches)) {
            $namespace = $matches[1];
        }
        if (preg_match('/^use\s+(.+);$/', $line, $matches)) {
            $imports[$i] = $matches[1];
        }
    }
    
    if (empty($imports)) {
        return false;
    }
    
    // Verificar quais imports são usados
    $unusedImports = [];
    foreach ($imports as $lineNum => $import) {
        $className = basename(str_replace('\\', '/', $import));
        $isUsed = false;
        
        // Verificar uso no código (excluindo a linha do import)
        foreach ($lines as $i => $line) {
            if ($i === $lineNum) continue;
            
            // Verificar uso direto da classe
            if (preg_match('/[^a-zA-Z0-9_]' . preg_quote($className, '/') . '[^a-zA-Z0-9_]/', $line)) {
                $isUsed = true;
                break;
            }
            
            // Verificar uso com namespace completo
            if (strpos($line, $import) !== false) {
                $isUsed = true;
                break;
            }
        }
        
        if (!$isUsed) {
            $unusedImports[] = $lineNum;
        }
    }
    
    if (empty($unusedImports)) {
        return false;
    }
    
    // Remover imports não utilizados
    $newLines = [];
    foreach ($lines as $i => $line) {
        if (!in_array($i, $unusedImports)) {
            $newLines[] = $line;
        }
    }
    
    $newContent = implode("\n", $newLines);
    file_put_contents($filePath, $newContent);
    
    return count($unusedImports);
}

function processDirectory($dir) {
    $totalRemoved = 0;
    $files = glob($dir . '/*.php');
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $removed = removeUnusedImports($file);
            if ($removed > 0) {
                echo "Removidos $removed imports não utilizados: $file\n";
                $totalRemoved += $removed;
            }
        }
    }
    
    return $totalRemoved;
}

echo "Iniciando remoção de imports não utilizados...\n\n";

$directories = [
    'app/Http/Controllers',
    'app/Services',
    'app/Models',
    'app/Mail',
];

$totalRemoved = 0;

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $removed = processDirectory($dir);
        $totalRemoved += $removed;
        echo "Total removido em $dir: $removed\n";
    }
}

echo "\nTotal de imports não utilizados removidos: $totalRemoved\n";
echo "Limpeza concluída!\n";