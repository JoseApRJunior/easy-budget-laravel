<?php
declare(strict_types=1);

/**
 * Script para corrigir todos os duplos type hints no BudgetController
 */

$file = 'app/Http/Controllers/BudgetController.php';
$content = file_get_contents($file);
$originalContent = $content;

// Padrões para substituir
$replacements = [
    // Padrão específico: \Illuminate\Http\JsonResponse: RedirectResponse -> RedirectResponse
    '/:\\s*\\\\Illuminate\\\\Http\\\\JsonResponse:\\s*RedirectResponse/' => ': RedirectResponse',
    
    // Padrão específico: \Illuminate\Http\JsonResponse: View -> View  
    '/:\\s*\\\\Illuminate\\\\Http\\\\JsonResponse:\\s*View/' => ': View',
    
    // Padrão genérico: \Qualquer\Coisa: OutraCoisa -> OutraCoisa
    '/:\\s*\\\\[\\\\\\w]+:\\s*[\\\\\\w]+/' => function($matches) {
        $parts = explode(':', $matches[0]);
        $lastPart = trim(end($parts));
        // Se não tiver barra invertida, adicionar
        if (strpos($lastPart, '\\') === false && !in_array($lastPart, ['int', 'string', 'bool', 'array', 'float', 'void'])) {
            $lastPart = '\\' . $lastPart;
        }
        return ': ' . $lastPart;
    },
];

foreach ($replacements as $pattern => $replacement) {
    if (is_callable($replacement)) {
        $content = preg_replace_callback($pattern, $replacement, $content);
    } else {
        $content = preg_replace($pattern, $replacement, $content);
    }
}

if ($content !== $originalContent) {
    file_put_contents($file, $content);
    echo "BudgetController.php corrigido com sucesso!\n";
    
    // Contar número de correções
    $originalLines = explode("\n", $originalContent);
    $newLines = explode("\n", $content);
    $changes = 0;
    
    for ($i = 0; $i < min(count($originalLines), count($newLines)); $i++) {
        if ($originalLines[$i] !== $newLines[$i]) {
            $changes++;
            echo "Linha " . ($i + 1) . " modificada\n";
        }
    }
} else {
    echo "Nenhuma correção necessária em BudgetController.php\n";
}