<?php
/**
 * Script para corrigir imports de Controller em todos os arquivos
 */

declare(strict_types=1);

$directory = __DIR__ . '/app/Http/Controllers';
$fixedCount = 0;

echo "🔧 Verificando controllers com problemas de import...\n\n";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        
        // Verifica se estende Controller mas não importa o Abstracts\Controller
        if (preg_match('/class\s+\w+\s+extends\s+Controller/', $content) && 
            !preg_match('/use\s+App\\Http\\Controllers\\Abstracts\\Controller;/', $content)) {
            
            echo "📁 Encontrado: " . basename($file->getPathname()) . "\n";
            
            // Adiciona o import após a declaração do namespace
            $content = preg_replace(
                '/(namespace\s+App\\Http\\Controllers;\s*\n)/',
                '$1\nuse App\\Http\\Controllers\\Abstracts\\Controller;\n',
                $content
            );
            
            // Também adiciona o import de View se necessário
            if (preg_match('/:\s*View/', $content) && 
                !preg_match('/use\s+Illuminate\\Contracts\\View\\View;/', $content)) {
                $content = preg_replace(
                    '/(use\s+App\\Http\\Controllers\\Abstracts\\Controller;\s*\n)/',
                    '$1use Illuminate\\Contracts\\View\\View;\n',
                    $content
                );
            }
            
            file_put_contents($file->getPathname(), $content);
            $fixedCount++;
            echo "✅ Corrigido: " . basename($file->getPathname()) . "\n\n";
        }
    }
}

echo "\n🎉 Total de arquivos corrigidos: $fixedCount\n";
echo "✅ Todos os controllers foram verificados e corrigidos!\n";