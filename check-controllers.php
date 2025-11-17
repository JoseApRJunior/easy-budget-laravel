<?php
/**
 * Script para verificar e listar TODOS os controllers que ainda têm problemas
 */

declare(strict_types=1);

$directory = __DIR__ . '/app/Http/Controllers';
$problematicFiles = [];

echo "🔍 Verificando TODOS os controllers em busca de problemas...\n\n";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $basename = basename($file->getPathname());
        
        // Pula o Controller.php abstrato
        if ($basename === 'Controller.php') {
            continue;
        }
        
        // Verifica se estende Controller mas não importa o Abstracts\Controller
        if (preg_match('/class\s+\w+\s+extends\s+Controller/', $content) && 
            !preg_match('/use\s+App\\Http\\Controllers\\Abstracts\\Controller;/', $content)) {
            
            $problematicFiles[] = $basename;
            echo "❌ Problema encontrado: $basename\n";
        }
    }
}

if (empty($problematicFiles)) {
    echo "\n✅ Nenhum controller com problema encontrado!\n";
} else {
    echo "\n📊 Total de controllers com problemas: " . count($problematicFiles) . "\n";
    echo "\nControllers problemáticos:\n";
    foreach ($problematicFiles as $file) {
        echo "  - $file\n";
    }
}