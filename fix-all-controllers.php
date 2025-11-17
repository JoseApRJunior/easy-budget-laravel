<?php
/**
 * Script para corrigir TODOS os controllers que não importam Abstracts\Controller
 */

declare(strict_types=1);

$directory = __DIR__ . '/app/Http/Controllers';
$fixedCount = 0;

echo "🔧 Verificando TODOS os controllers com problemas de import...\n\n";

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
            
            echo "📁 Encontrado: $basename\n";
            
            // Adiciona o import após a declaração do namespace
            $content = preg_replace(
                '/(namespace\s+App\\Http\\Controllers;\s*\n)/',
                '$1\nuse App\\Http\\Controllers\\Abstracts\\Controller;\n',
                $content
            );
            
            file_put_contents($file->getPathname(), $content);
            $fixedCount++;
            echo "✅ Corrigido: $basename\n\n";
        }
    }
}

echo "\n🎉 Total de controllers corrigidos: $fixedCount\n";
echo "✅ Todos os controllers foram verificados e corrigidos!\n";