<?php
/**
 * Script para corrigir declarações de $model em repositórios
 */

declare(strict_types=1);

$directory = __DIR__ . '/app/Repositories';
$fixedCount = 0;

echo "🔧 Verificando repositórios com problemas de declaração de \$model...\n\n";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && !str_contains($file->getPathname(), 'Abstracts')) {
        $content = file_get_contents($file->getPathname());
        
        // Verifica se tem a declaração problemática de $model
        if (preg_match('/\/\*\*\s*\*\s*@var\s+\w+\s*\*\/\s*protected\s+\$model;/', $content)) {
            
            echo "📁 Encontrado: " . basename($file->getPathname()) . "\n";
            
            // Remove a declaração comentada de $model
            $content = preg_replace(
                '/\/\*\*\s*\*\s*@var\s+\w+\s*\*\/\s*protected\s+\$model;\s*\n/',
                '',
                $content
            );
            
            file_put_contents($file->getPathname(), $content);
            $fixedCount++;
            echo "✅ Corrigido: " . basename($file->getPathname()) . "\n\n";
        }
    }
}

echo "\n🎉 Total de repositórios corrigidos: $fixedCount\n";
echo "✅ Todos os repositórios foram verificados e corrigidos!\n";