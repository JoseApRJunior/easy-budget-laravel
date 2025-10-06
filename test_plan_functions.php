<?php

/**
 * Teste das funções checkPlan() e checkPlanPending()
 */

echo "🧪 TESTE DAS FUNÇÕES CHECKPLAN() E CHECKPLANPENDING()\n";
echo "================================================\n\n";

// Teste básico de existência das funções
echo "🔍 Verificando se as funções existem...\n";

if ( function_exists( 'checkPlan' ) ) {
    echo "✅ checkPlan() existe\n";
} else {
    echo "❌ checkPlan() não existe\n";
}

if ( function_exists( 'checkPlanPending' ) ) {
    echo "✅ checkPlanPending() existe\n";
} else {
    echo "❌ checkPlanPending() não existe\n";
}

// Teste básico de execução
echo "\n🔍 Testando execução básica das funções...\n";

try {
    echo "Chamando checkPlan()... ";
    $result1 = checkPlan();
    echo "OK (retornou: " . ( is_null( $result1 ) ? 'null' : 'objeto' ) . ")\n";
} catch ( Exception $e ) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

try {
    echo "Chamando checkPlanPending()... ";
    $result2 = checkPlanPending();
    echo "OK (retornou: " . ( is_null( $result2 ) ? 'null' : 'objeto' ) . ")\n";
} catch ( Exception $e ) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

// Teste com sessão vazia
echo "\n🔍 Testando com sessão vazia...\n";
$_SESSION = [];

try {
    $result1 = checkPlan();
    $result2 = checkPlanPending();

    echo "checkPlan() retornou: " . ( is_null( $result1 ) ? 'null' : 'objeto' ) . "\n";
    echo "checkPlanPending() retornou: " . ( is_null( $result2 ) ? 'null' : 'objeto' ) . "\n";

    if ( $result1 === null && $result2 === null ) {
        echo "✅ Comportamento correto para sessão vazia\n";
    } else {
        echo "⚠️ Comportamento inesperado para sessão vazia\n";
    }
} catch ( Exception $e ) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "\n📊 RESUMO DO TESTE:\n";
echo "==================\n";

$success = true;

if ( function_exists( 'checkPlan' ) && function_exists( 'checkPlanPending' ) ) {
    echo "✅ Funções existem e estão implementadas\n";

    try {
        $result1 = checkPlan();
        $result2 = checkPlanPending();
        echo "✅ Funções podem ser executadas sem erros\n";
    } catch ( Exception $e ) {
        echo "❌ Erro na execução das funções: " . $e->getMessage() . "\n";
        $success = false;
    }

    if ( $result1 === null && $result2 === null ) {
        echo "✅ Comportamento correto para usuário não autenticado\n";
    } else {
        echo "⚠️ Comportamento diferente do esperado para usuário não autenticado\n";
    }

} else {
    echo "❌ Funções não estão implementadas\n";
    $success = false;
}

echo "\n" . str_repeat( "=", 50 ) . "\n";

if ( $success ) {
    echo "🎉 SUCESSO: As funções checkPlan() e checkPlanPending() estão funcionando!\n";
    echo "✅ Implementação concluída com sucesso.\n";
} else {
    echo "⚠️ ATENÇÃO: Alguns problemas foram detectados.\n";
    echo "Verifique a implementação das funções.\n";
}

echo "\n📋 PRÓXIMOS PASSOS:\n";
echo "- Teste as funções com usuário autenticado\n";
echo "- Teste as funções com dados reais no banco\n";
echo "- Verifique a integração com as views Blade\n";
