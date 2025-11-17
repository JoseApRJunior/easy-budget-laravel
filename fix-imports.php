<?php

declare(strict_types=1);

/**
 * Script para corrigir importações faltantes baseado no relatório do PHPStan
 */

$corrections = [
    // Controllers - adicionar imports de facades e models
    'app/Http/Controllers/*.php' => [
        'missing_imports' => [
            'Illuminate\\Support\\Facades\\Auth',
            'Illuminate\\Support\\Facades\\DB',
            'Illuminate\\Support\\Facades\\Log',
            'Illuminate\\Support\\Facades\\Mail',
            'Illuminate\\Support\\Facades\\Cache',
            'Illuminate\\Support\\Facades\\Session',
            'Illuminate\\Support\\Facades\\Storage',
            'Illuminate\\Support\\Facades\\Validator',
            'Illuminate\\Http\\Request',
            'Illuminate\\Http\\JsonResponse',
            'App\\Models\\User',
            'App\\Models\\Tenant',
            'App\\Models\\Provider',
            'App\\Support\\ServiceResult',
        ]
    ],
    // Services - adicionar imports baseados no uso
    'app/Services/**/*.php' => [
        'missing_imports' => [
            'Illuminate\\Support\\Facades\\DB',
            'Illuminate\\Support\\Facades\\Log',
            'Illuminate\\Support\\Facades\\Cache',
            'Illuminate\\Support\\Facades\\Mail',
            'Illuminate\\Support\\Facades\\Storage',
            'Illuminate\\Database\\Eloquent\\Model',
            'Illuminate\\Database\\Eloquent\\Collection',
            'Illuminate\\Support\\Collection',
            'App\\Support\\ServiceResult',
            'App\\Models\\User',
            'App\\Models\\Tenant',
            'App\\Models\\Provider',
        ]
    ],
    // Models - adicionar imports de Eloquent
    'app/Models/*.php' => [
        'missing_imports' => [
            'Illuminate\\Database\\Eloquent\\Model',
            'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany',
            'Illuminate\\Database\\Eloquent\\Relations\\HasOne',
            'Illuminate\\Database\\Eloquent\\SoftDeletes',
            'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
        ]
    ]
];

function analyzeFileAndAddImports($filePath, $missingImports) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    $namespaceLine = -1;
    $lastUseLine = -1;
    $existingImports = [];
    
    // Encontrar namespace e imports existentes
    foreach ($lines as $i => $line) {
        if (preg_match('/^namespace\s+(.+);$/', $line, $matches)) {
            $namespaceLine = $i;
        }
        if (preg_match('/^use\s+(.+);$/', $line, $matches)) {
            $lastUseLine = $i;
            $existingImports[] = $matches[1];
        }
    }
    
    // Verificar quais imports estão realmente faltando
    $importsToAdd = [];
    foreach ($missingImports as $import) {
        $importClass = basename(str_replace('\\', '/', $import));
        
        // Verificar se a classe é usada no arquivo
        $isUsed = false;
        foreach ($lines as $line) {
            // Verificar uso direto da classe (sem namespace)
            if (preg_match('/[^a-zA-Z0-9_]' . preg_quote($importClass, '/') . '[^a-zA-Z0-9_]/', $line)) {
                $isUsed = true;
                break;
            }
        }
        
        // Verificar se já não está importado
        $alreadyImported = false;
        foreach ($existingImports as $existing) {
            if ($existing === $import || basename(str_replace('\\', '/', $existing)) === $importClass) {
                $alreadyImported = true;
                break;
            }
        }
        
        if ($isUsed && !$alreadyImported) {
            $importsToAdd[] = $import;
        }
    }
    
    if (empty($importsToAdd)) {
        return false;
    }
    
    // Adicionar imports
    $insertPosition = $lastUseLine >= 0 ? $lastUseLine + 1 : $namespaceLine + 1;
    $importLines = array_map(function($import) {
        return "use $import;";
    }, $importsToAdd);
    
    array_splice($lines, $insertPosition, 0, array_merge([''], $importLines));
    
    $newContent = implode("\n", $lines);
    file_put_contents($filePath, $newContent);
    
    return true;
}

function processDirectory($dir, $patterns, $missingImports) {
    $files = glob($dir . '/' . $patterns);
    $totalFixed = 0;
    
    foreach ($files as $file) {
        if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            if (analyzeFileAndAddImports($file, $missingImports)) {
                echo "Corrigido: $file\n";
                $totalFixed++;
            }
        }
    }
    
    return $totalFixed;
}

// Executar correções
echo "Iniciando correções de importações...\n\n";

$totalCorrections = 0;

foreach ($corrections as $pattern => $config) {
    $dir = dirname($pattern);
    $filePattern = basename($pattern);
    
    if ($dir === '.') {
        $dir = 'app';
    }
    
    if (is_dir($dir)) {
        $fixed = processDirectory($dir, $filePattern, $config['missing_imports']);
        $totalCorrections += $fixed;
        echo "Corrigidos $fixed arquivos em $dir\n";
    }
}

echo "\nTotal de arquivos corrigidos: $totalCorrections\n";
echo "Correções concluídas!\n";