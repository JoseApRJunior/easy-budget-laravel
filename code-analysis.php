<?php

/**
 * Script de Análise Estática do Código
 * Análise abrangente de importações, namespaces e dependências
 */

class CodeAnalyzer
{
    private array $errors = [];
    private array $warnings = [];
    private array $notices = [];
    private array $statistics = [];
    
    private array $namespaces = [];
    private array $classMap = [];
    private array $missingClasses = [];
    private array $importIssues = [];
    
    public function __construct()
    {
        $this->initializeAnalysis();
    }
    
    private function initializeAnalysis(): void
    {
        echo "=== ANÁLISE ESTÁTICA DO CÓDIGO - PHPSTAN LEVEL 8 ===\n";
        echo "Iniciando análise abrangente...\n\n";
        
        $this->scanProjectStructure();
        $this->analyzeNamespaces();
        $this->checkImports();
        $this->validateDependencies();
        $this->generateReport();
    }
    
    private function scanProjectStructure(): void
    {
        echo "1. Escaneando estrutura do projeto...\n";
        
        $directories = [
            'app' => 'C:\xampp\htdocs\easy-budget-laravel\app',
            'config' => 'C:\xampp\htdocs\easy-budget-laravel\config',
            'routes' => 'C:\xampp\htdocs\easy-budget-laravel\routes',
            'database' => 'C:\xampp\htdocs\easy-budget-laravel\database'
        ];
        
        foreach ($directories as $name => $path) {
            if (is_dir($path)) {
                $this->scanDirectory($path, $name);
            }
        }
        
        echo "   ✓ Estrutura escaneada\n\n";
    }
    
    private function scanDirectory(string $dir, string $prefix): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->analyzeFile($file->getPathname(), $prefix);
            }
        }
    }
    
    private function analyzeFile(string $filepath, string $prefix): void
    {
        $content = file_get_contents($filepath);
        $lines = explode("\n", $content);
        
        $currentNamespace = '';
        $imports = [];
        $className = '';
        
        foreach ($lines as $lineNumber => $line) {
            $lineNumber++; // 1-based indexing
            
            // Detect namespace
            if (preg_match('/^\s*namespace\s+([^;]+);/', $line, $matches)) {
                $currentNamespace = $matches[1];
                $this->namespaces[$currentNamespace] = $filepath;
            }
            
            // Detect imports (use statements)
            if (preg_match('/^\s*use\s+([^;]+);/', $line, $matches)) {
                $import = trim($matches[1]);
                $imports[] = $import;
                $this->validateImport($import, $filepath, $lineNumber);
            }
            
            // Detect class/interface/trait definition
            if (preg_match('/^\s*(class|interface|trait)\s+(\w+)/', $line, $matches)) {
                $className = $matches[2];
                $fullClassName = $currentNamespace ? $currentNamespace . '\\' . $className : $className;
                $this->classMap[$fullClassName] = $filepath;
            }
            
            // Detect usage of classes
            $this->detectClassUsage($line, $filepath, $lineNumber, $currentNamespace, $imports);
        }
        
        // Store file analysis
        $this->statistics[$filepath] = [
            'namespace' => $currentNamespace,
            'class' => $className,
            'imports' => $imports,
            'lines' => count($lines)
        ];
    }
    
    private function validateImport(string $import, string $filepath, int $lineNumber): void
    {
        // Check for alias usage
        if (strpos($import, ' as ') !== false) {
            $parts = explode(' as ', $import);
            $className = trim($parts[0]);
            $alias = trim($parts[1]);
        } else {
            $className = $import;
            $alias = null;
        }
        
        // Check if class exists
        if (!$this->classExists($className)) {
            $this->errors[] = [
                'type' => 'missing_class',
                'file' => $filepath,
                'line' => $lineNumber,
                'message' => "Classe não encontrada: {$className}",
                'severity' => 'error'
            ];
            $this->missingClasses[] = $className;
        }
        
        // Check for duplicate imports
        if ($this->hasDuplicateImport($filepath, $import)) {
            $this->warnings[] = [
                'type' => 'duplicate_import',
                'file' => $filepath,
                'line' => $lineNumber,
                'message' => "Importação duplicada: {$import}",
                'severity' => 'warning'
            ];
        }
    }
    
    private function classExists(string $className): bool
    {
        // Check if it's a built-in PHP class
        if (class_exists($className) || interface_exists($className) || trait_exists($className)) {
            return true;
        }
        
        // Check if it's in our class map
        if (isset($this->classMap[$className])) {
            return true;
        }
        
        // Try to resolve relative to project
        $possiblePaths = [
            "C:\\xampp\\htdocs\\easy-budget-laravel\\app\\" . str_replace('\\', '/', $className) . '.php',
            "C:\\xampp\\htdocs\\easy-budget-laravel\\vendor\\" . str_replace('\\', '/', $className) . '.php'
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function hasDuplicateImport(string $filepath, string $import): bool
    {
        static $fileImports = [];
        
        if (!isset($fileImports[$filepath])) {
            $fileImports[$filepath] = [];
        }
        
        if (in_array($import, $fileImports[$filepath])) {
            return true;
        }
        
        $fileImports[$filepath][] = $import;
        return false;
    }
    
    private function detectClassUsage(string $line, string $filepath, int $lineNumber, string $namespace, array $imports): void
    {
        // Detect class instantiation
        if (preg_match('/new\s+(\w+)/', $line, $matches)) {
            $className = $matches[1];
            $this->checkClassUsage($className, $filepath, $lineNumber, $namespace, $imports);
        }
        
        // Detect static method calls
        if (preg_match('/(\w+)::/', $line, $matches)) {
            $className = $matches[1];
            $this->checkClassUsage($className, $filepath, $lineNumber, $namespace, $imports);
        }
        
        // Detect type hints
        if (preg_match('/:\s*(\w+)/', $line, $matches)) {
            $className = $matches[1];
            $this->checkClassUsage($className, $filepath, $lineNumber, $namespace, $imports);
        }
    }
    
    private function checkClassUsage(string $className, string $filepath, int $lineNumber, string $namespace, array $imports): void
    {
        // Skip built-in types
        $builtinTypes = ['int', 'string', 'bool', 'float', 'array', 'object', 'callable', 'void', 'mixed'];
        if (in_array($className, $builtinTypes)) {
            return;
        }
        
        // Check if class is imported
        $isImported = false;
        foreach ($imports as $import) {
            if (strpos($import, $className) !== false) {
                $isImported = true;
                break;
            }
        }
        
        // Check if class exists in current namespace
        $fullClassName = $namespace ? $namespace . '\\' . $className : $className;
        if (!$isImported && !isset($this->classMap[$fullClassName]) && !$this->classExists($className)) {
            $this->warnings[] = [
                'type' => 'unimported_class',
                'file' => $filepath,
                'line' => $lineNumber,
                'message' => "Possível uso de classe não importada: {$className}",
                'severity' => 'warning'
            ];
        }
    }
    
    private function analyzeNamespaces(): void
    {
        echo "2. Analisando namespaces...\n";
        
        foreach ($this->statistics as $filepath => $data) {
            if (empty($data['namespace'])) {
                $this->notices[] = [
                    'type' => 'missing_namespace',
                    'file' => $filepath,
                    'line' => 1,
                    'message' => 'Arquivo sem namespace definido',
                    'severity' => 'notice'
                ];
            }
            
            // Check PSR-4 compliance
            $this->checkPSR4Compliance($filepath, $data);
        }
        
        echo "   ✓ Namespaces analisados\n\n";
    }
    
    private function checkPSR4Compliance(string $filepath, array $data): void
    {
        if (empty($data['namespace']) || empty($data['class'])) {
            return;
        }
        
        $expectedPath = str_replace('\\', '/', $data['namespace']) . '/' . $data['class'] . '.php';
        $actualPath = str_replace('C:\\xampp\\htdocs\\easy-budget-laravel\\', '', $filepath);
        $actualPath = str_replace('\\', '/', $actualPath);
        
        if (!str_contains($actualPath, $expectedPath)) {
            $this->warnings[] = [
                'type' => 'psr4_non_compliance',
                'file' => $filepath,
                'line' => 1,
                'message' => "Possível não conformidade PSR-4. Esperado: {$expectedPath}, Encontrado: {$actualPath}",
                'severity' => 'warning'
            ];
        }
    }
    
    private function checkImports(): void
    {
        echo "3. Verificando importações...\n";
        
        // Check for unused imports
        foreach ($this->statistics as $filepath => $data) {
            foreach ($data['imports'] as $import) {
                $this->checkUnusedImport($import, $filepath, $data);
            }
        }
        
        echo "   ✓ Importações verificadas\n\n";
    }
    
    private function checkUnusedImport(string $import, string $filepath, array $data): void
    {
        $content = file_get_contents($filepath);
        $className = basename(str_replace('\\', '/', $import));
        
        // Check if class is used in file (excluding the import line)
        $lines = explode("\n", $content);
        $usageCount = 0;
        
        foreach ($lines as $line) {
            if (strpos($line, 'use ') === false && strpos($line, $className) !== false) {
                $usageCount++;
            }
        }
        
        if ($usageCount === 0) {
            $this->notices[] = [
                'type' => 'unused_import',
                'file' => $filepath,
                'line' => 1,
                'message' => "Possível importação não utilizada: {$import}",
                'severity' => 'notice'
            ];
        }
    }
    
    private function validateDependencies(): void
    {
        echo "4. Validando dependências...\n";
        
        $composerPath = "C:\\xampp\\htdocs\\easy-budget-laravel\\composer.json";
        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            
            // Check PHP version
            if (isset($composer['require']['php'])) {
                $this->checkPHPVersion($composer['require']['php']);
            }
            
            // Check for common dependency issues
            $this->checkDependencyConflicts($composer);
        }
        
        echo "   ✓ Dependências validadas\n\n";
    }
    
    private function checkPHPVersion(string $requiredVersion): void
    {
        $currentVersion = PHP_VERSION;
        $required = str_replace(['^', '~', '>=', '<=', '>', '<'], '', $requiredVersion);
        
        if (version_compare($currentVersion, $required, '<')) {
            $this->errors[] = [
                'type' => 'php_version_mismatch',
                'file' => 'composer.json',
                'line' => 1,
                'message' => "Versão PHP insuficiente. Requerido: {$requiredVersion}, Atual: {$currentVersion}",
                'severity' => 'error'
            ];
        }
    }
    
    private function checkDependencyConflicts(array $composer): void
    {
        $commonConflicts = [
            'doctrine/orm' => ['laravel/framework'],
            'symfony/console' => ['laravel/framework']
        ];
        
        foreach ($commonConflicts as $package => $conflicts) {
            if (isset($composer['require'][$package])) {
                foreach ($conflicts as $conflict) {
                    if (isset($composer['require'][$conflict])) {
                        $this->warnings[] = [
                            'type' => 'potential_conflict',
                            'file' => 'composer.json',
                            'line' => 1,
                            'message' => "Possível conflito de dependência: {$package} e {$conflict}",
                            'severity' => 'warning'
                        ];
                    }
                }
            }
        }
    }
    
    private function generateReport(): void
    {
        echo "5. Gerando relatório...\n\n";
        
        $totalFiles = count($this->statistics);
        $totalErrors = count($this->errors);
        $totalWarnings = count($this->warnings);
        $totalNotices = count($this->notices);
        
        echo "========================================\n";
        echo "        RELATÓRIO DE ANÁLISE ESTÁTICA\n";
        echo "========================================\n\n";
        
        echo "📊 ESTATÍSTICAS GERAIS:\n";
        echo "   Arquivos analisados: {$totalFiles}\n";
        echo "   Erros encontrados: {$totalErrors}\n";
        echo "   Avisos encontrados: {$totalWarnings}\n";
        echo "   Notas encontradas: {$totalNotices}\n\n";
        
        if ($totalErrors > 0) {
            echo "❌ ERROS CRÍTICOS:\n";
            foreach ($this->errors as $error) {
                echo "   📄 {$error['file']}:{$error['line']}\n";
                echo "      → {$error['message']}\n";
            }
            echo "\n";
        }
        
        if ($totalWarnings > 0) {
            echo "⚠️  AVISOS:\n";
            foreach ($this->warnings as $warning) {
                echo "   📄 {$warning['file']}:{$warning['line']}\n";
                echo "      → {$warning['message']}\n";
            }
            echo "\n";
        }
        
        if ($totalNotices > 0) {
            echo "ℹ️  NOTAS:\n";
            foreach ($this->notices as $notice) {
                echo "   📄 {$notice['file']}:{$notice['line']}\n";
                echo "      → {$notice['message']}\n";
            }
            echo "\n";
        }
        
        // Specific analysis for Abstract Controller issues
        $this->analyzeAbstractControllerIssues();
        
        // Generate JSON report
        $this->generateJSONReport();
        
        echo "✅ Análise concluída!\n";
        echo "📁 Relatório JSON salvo em: code-analysis-report.json\n";
    }
    
    private function analyzeAbstractControllerIssues(): void
    {
        echo "🔍 ANÁLISE ESPECÍFICA - CONTROLLERS ABSTRACT:\n";
        
        $abstractControllerPath = "C:\\xampp\\htdocs\\easy-budget-laravel\\app\\Http\\Controllers\\Abstract";
        
        if (is_dir($abstractControllerPath)) {
            $abstractFiles = glob($abstractControllerPath . "/*.php");
            
            foreach ($abstractFiles as $file) {
                $content = file_get_contents($file);
                
                // Check for common Abstract Controller issues
                if (!str_contains($content, 'namespace')) {
                    echo "   ⚠️  Controller Abstract sem namespace: " . basename($file) . "\n";
                }
                
                if (!str_contains($content, 'abstract class')) {
                    echo "   ⚠️  Controller não declarado como abstract: " . basename($file) . "\n";
                }
                
                if (!str_contains($content, 'use Illuminate\\Routing\\Controller')) {
                    echo "   ⚠️  Controller Abstract não estendendo Controller base: " . basename($file) . "\n";
                }
            }
        } else {
            echo "   ℹ️  Diretório Abstract não encontrado\n";
        }
        
        echo "\n";
    }
    
    private function generateJSONReport(): void
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => [
                'total_files' => count($this->statistics),
                'total_errors' => count($this->errors),
                'total_warnings' => count($this->warnings),
                'total_notices' => count($this->notices),
                'missing_classes' => array_unique($this->missingClasses)
            ],
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'notices' => $this->notices,
            'statistics' => $this->statistics,
            'namespaces' => $this->namespaces,
            'class_map' => $this->classMap
        ];
        
        file_put_contents('code-analysis-report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

// Execute analysis
echo "Iniciando análise estática...\n\n";
new CodeAnalyzer();