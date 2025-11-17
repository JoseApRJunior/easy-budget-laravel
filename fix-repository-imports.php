<?php
/**
 * Script para corrigir imports de AbstractTenantRepository em todos os repositórios
 */

declare(strict_types=1);

$directory = __DIR__ . '/app/Repositories';
$fixedCount = 0;

echo "🔧 Verificando repositórios com problemas de import...\n\n";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        
        // Verifica se estende AbstractTenantRepository mas não importa a classe
        if (preg_match('/extends\s+AbstractTenantRepository/', $content) && 
            !preg_match('/use\s+App\\Repositories\\Abstracts\\AbstractTenantRepository;/', $content) &&
            !preg_match('/^AbstractTenantRepository\.php$/', basename($file->getPathname()))) {
            
            echo "📁 Encontrado: " . basename($file->getPathname()) . "\n";
            
            // Adiciona o import após a declaração do namespace
            $content = preg_replace(
                '/(namespace\s+App\\Repositories;\s*\n)/',
                '$1\nuse App\\Repositories\\Abstracts\\AbstractTenantRepository;\n',
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