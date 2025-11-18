<?php

// Teste para verificar se o modelo Budget pode ser carregado
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Models\Budget;

try {
    // Testa se o modelo pode ser instanciado
    $budget = new Budget();
    echo "✅ Modelo Budget pode ser instanciado\n";
    
    // Testa se a tabela existe
    $count = Budget::count();
    echo "✅ Tabela budgets existe ({$count} registros)\n";
    
    // Testa relacionamento com BudgetShare
    if (method_exists($budget, 'shares')) {
        echo "✅ Relacionamento shares() existe\n";
    } else {
        echo "❌ Relacionamento shares() NÃO existe\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}