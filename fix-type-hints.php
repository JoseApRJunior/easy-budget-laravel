<?php

declare(strict_types=1);

/**
 * Script para adicionar type hints em controllers críticos
 */

$controllersToFix = [
    'app/Http/Controllers/AuthController.php',
    'app/Http/Controllers/DashboardController.php',
    'app/Http/Controllers/BudgetController.php',
    'app/Http/Controllers/CustomerController.php',
    'app/Http/Controllers/InvoiceController.php',
    'app/Http/Controllers/ProviderController.php',
    'app/Http/Controllers/SettingsController.php',
    'app/Http/Controllers/UserController.php',
];

function addTypeHintsToController($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Adicionar return types para métodos comuns
    $patterns = [
        // Métodos que retornam view
        '/public function (\w+)\(([^)]*)\)\s*\n*\s*\{/' => 'public function $1($2): \\Illuminate\\View\\View',
        
        // Métodos que retornam redirect
        '/public function (store|update|delete|destroy)\(([^)]*)\)\s*\n*\s*\{/' => 'public function $1($2): \\Illuminate\\Http\\RedirectResponse',
        
        // Métodos que retornam JSON
        '/public function (\w*api\w*|\w*json\w*|\w*ajax\w*)\(([^)]*)\)\s*\n*\s*\{/' => 'public function $1($2): \\Illuminate\\Http\\JsonResponse',
        
        // Métodos que retornam ServiceResult
        '/public function (\w+)\(([^)]*)\)\s*\n*\s*\{/' => 'public function $1($2): \\App\\Support\\ServiceResult',
    ];
    
    // Mas precisamos ser mais cuidadosos - vamos analisar o conteúdo do método
    $lines = explode("\n", $content);
    $inMethod = false;
    $methodStart = -1;
    $methodName = '';
    $methodParams = '';
    
    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        
        // Encontrar métodos públicos
        if (preg_match('/public function (\w+)\(([^)]*)\)/', $line, $matches)) {
            $methodName = $matches[1];
            $methodParams = $matches[2];
            $methodStart = $i;
            $inMethod = true;
            
            // Analisar o corpo do método para determinar o return type
            $returnType = determineReturnType($lines, $i, $methodName);
            
            if ($returnType) {
                // Adicionar return type
                $lines[$i] = preg_replace('/public function (\w+)\(([^)]*)\)/', "public function $1($2): $returnType", $line);
            }
        }
    }
    
    $newContent = implode("\n", $lines);
    
    if ($newContent !== $originalContent) {
        file_put_contents($filePath, $newContent);
        return true;
    }
    
    return false;
}

function determineReturnType($lines, $startLine, $methodName) {
    $braceCount = 0;
    $hasReturnView = false;
    $hasReturnRedirect = false;
    $hasReturnJson = false;
    $hasReturnServiceResult = false;
    $hasReturnResponse = false;
    
    for ($i = $startLine; $i < count($lines); $i++) {
        $line = $lines[$i];
        
        // Contar chaves para saber quando o método termina
        $braceCount += substr_count($line, '{');
        $braceCount -= substr_count($line, '}');
        
        if ($braceCount < 0) break; // Método terminou
        
        // Verificar tipos de retorno
        if (strpos($line, 'return view(') !== false) {
            $hasReturnView = true;
        }
        if (strpos($line, 'return redirect(') !== false || strpos($line, 'back(') !== false) {
            $hasReturnRedirect = true;
        }
        if (strpos($line, 'return response()->json(') !== false || strpos($line, 'return json(') !== false) {
            $hasReturnJson = true;
        }
        if (strpos($line, 'ServiceResult::') !== false || strpos($line, 'new ServiceResult') !== false) {
            $hasReturnServiceResult = true;
        }
        if (strpos($line, 'return response(') !== false) {
            $hasReturnResponse = true;
        }
    }
    
    // Determinar tipo baseado no que encontrou
    if ($hasReturnServiceResult) {
        return '\\App\\Support\\ServiceResult';
    }
    if ($hasReturnJson) {
        return '\\Illuminate\\Http\\JsonResponse';
    }
    if ($hasReturnRedirect) {
        return '\\Illuminate\\Http\\RedirectResponse';
    }
    if ($hasReturnView) {
        return '\\Illuminate\\View\\View';
    }
    if ($hasReturnResponse) {
        return '\\Illuminate\\Http\\Response';
    }
    
    // Métodos comuns que tipicamente retornam algo específico
    if (in_array($methodName, ['index', 'create', 'edit', 'show'])) {
        return '\\Illuminate\\View\\View';
    }
    if (in_array($methodName, ['store', 'update', 'destroy', 'delete'])) {
        return '\\Illuminate\\Http\\RedirectResponse';
    }
    
    return null;
}

echo "Iniciando adição de type hints em controllers...\n\n";

$totalFixed = 0;

foreach ($controllersToFix as $controller) {
    if (file_exists($controller)) {
        if (addTypeHintsToController($controller)) {
            echo "Type hints adicionados: $controller\n";
            $totalFixed++;
        } else {
            echo "Sem mudanças necessárias: $controller\n";
        }
    } else {
        echo "Arquivo não encontrado: $controller\n";
    }
}

echo "\nTotal de controllers com type hints adicionados: $totalFixed\n";
echo "Correções de type hints concluídas!\n";