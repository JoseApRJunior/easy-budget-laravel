<?php
/**
 * Script para adicionar método makeModel faltante em repositórios
 */

declare(strict_types=1);

$directory = __DIR__ . '/app/Repositories';
$fixedCount = 0;

echo "🔧 Verificando repositórios que precisam do método makeModel...\n\n";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && !str_contains($file->getPathname(), 'Abstracts')) {
        $content = file_get_contents($file->getPathname());
        
        // Verifica se estende AbstractTenantRepository mas não tem makeModel
        if (preg_match('/extends\s+AbstractTenantRepository/', $content) && 
            !preg_match('/protected\s+function\s+makeModel/', $content)) {
            
            // Extrai o nome do model do construtor
            if (preg_match('/__construct\(\s*(\w+)\s*\$model/', $content, $matches)) {
                $modelClass = $matches[1];
                
                echo "📁 Encontrado: " . basename($file->getPathname()) . " (Model: $modelClass)\n";
                
                // Adiciona o método makeModel após o construtor
                $content = preg_replace(
                    '/(\/\*\*\s*\*\s*Create a new repository instance\.\s*\*\/\s*public function __construct.*?\n    \}\n)/s',
                    '$1\n    /**\n     * Create a new model instance.\n     */\n    protected function makeModel(): Model\n    {\n        return new ' . $modelClass . '();\n    }\n',
                    $content
                );
                
                // Adiciona o import de Model se necessário
                if (!preg_match('/use Illuminate\\Database\\Eloquent\\Model;/', $content)) {
                    $content = preg_replace(
                        '/(use App\\Repositories\\Abstracts\\AbstractTenantRepository;\s*\n)/',
                        '$1use Illuminate\\Database\\Eloquent\\Model;\n',
                        $content
                    );
                }
                
                file_put_contents($file->getPathname(), $content);
                $fixedCount++;
                echo "✅ Corrigido: " . basename($file->getPathname()) . "\n\n";
            }
        }
    }
}

echo "\n🎉 Total de repositórios corrigidos: $fixedCount\n";
echo "✅ Todos os repositórios foram verificados e corrigidos!\n";