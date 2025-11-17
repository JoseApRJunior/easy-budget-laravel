<?php
declare(strict_types=1);

/**
 * Script para corrigir duplos type hints em controllers
 * Exemplo: ": \Illuminate\Http\RedirectResponse: View" -> ": View"
 */

$directories = [
    'app/Http/Controllers',
    'app/Http/Controllers/Admin',
    'app/Http/Controllers/Api',
    'app/Http/Controllers/Auth',
    'app/Http/Controllers/Integrations',
];

$fixedFiles = [];
$totalFixes = 0;

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            $originalContent = $content;
            
            // Padrão para encontrar duplos type hints
            // Exemplo: \Illuminate\Http\RedirectResponse: View
            $pattern = '/:\\s*\\\\Illuminate\\\\[\\w\\\\]+:\\s*[\\w\\\\]+/';
            
            // Substituir pelo segundo tipo apenas
            $content = preg_replace_callback($pattern, function($matches) {
                // Pegar apenas o último tipo após os dois pontos
                $parts = explode(':', $matches[0]);
                $lastPart = trim(end($parts));
                
                // Se for um tipo simples, adicionar \ na frente se necessário
                if (strpos($lastPart, '\\') === false && !in_array($lastPart, ['int', 'string', 'bool', 'array', 'float', 'void'])) {
                    $lastPart = '\\' . $lastPart;
                }
                
                return ': ' . $lastPart;
            }, $content);
            
            // Também corrigir casos sem barra invertida no início
            $content = preg_replace('/:\\s*([A-Z][\\w\\\\]+):\\s*([A-Z][\\w\\\\]+)/', ': \\\2', $content);
            
            if ($content !== $originalContent) {
                file_put_contents($file->getPathname(), $content);
                $fixedFiles[] = $file->getPathname();
                
                // Contar número de correções neste arquivo
                $diff = substr_count($content, ':') - substr_count($originalContent, ':');
                if ($diff < 0) {
                    $totalFixes += abs($diff);
                }
            }
        }
    }
}

echo "Arquivos corrigidos: " . count($fixedFiles) . PHP_EOL;
echo "Total de correções: " . $totalFixes . PHP_EOL;
echo "\nArquivos:\n";
foreach ($fixedFiles as $file) {
    echo "- $file\n";
}