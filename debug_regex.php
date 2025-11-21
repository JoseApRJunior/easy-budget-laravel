<?php

/**
 * Script para identificar padrões de regex malformados
 */

// Função para testar regex patterns
function testRegexPattern( $pattern, $value, $name = '' )
{
    try {
        $result = preg_match( $pattern, $value );
        echo "✅ $name: $pattern -> " . ( $result ? 'MATCH' : 'NO MATCH' ) . "\n";
        return true;
    } catch ( Exception $e ) {
        echo "❌ $name: $pattern -> ERRO: " . $e->getMessage() . "\n";
        return false;
    }
}

echo "=== TESTE DE PADRÕES DE TELEFONE ===\n\n";

// Padrão que está falhando (do log de erro)
echo "Testando padrão atual de telefone...\n";
testRegexPattern( '/^\(\d{2}\) \d{4,5}-\d{4}$/', '(43) 99959-0945', 'Padrão Telefone' );

// Padrões alternativos se houver problema
echo "\n=== TESTE DE PADRÕES ALTERNATIVOS ===\n\n";

// Padrão para telefone com escapar correto
testRegexPattern( '/^\(\d{2}\) \d{4,5}-\d{4}$/', '(43) 99959-0945', 'Telefone Com Escapa' );

// Padrão sem escape para parênteses
testRegexPattern( '/^\(\d{2}\) \d{4,5}-\d{4}$/', '(43) 99959-0945', 'Telefone Sem Escape' );

// Verificar se há algum problema de encoding
$testPhone = '(43) 99959-0945';
echo "\n=== ANÁLISE DO TELEFONE ===\n";
echo "Telefone original: $testPhone\n";
echo "Bytes: " . bin2hex( $testPhone ) . "\n";
echo "Tamanho: " . strlen( $testPhone ) . "\n";

// Testar se é um problema de encoding ou caractere especial
for ( $i = 0; $i < strlen( $testPhone ); $i++ ) {
    echo "Char[$i]: " . $testPhone[ $i ] . " (ord: " . ord( $testPhone[ $i ] ) . ")\n";
}

echo "\n=== VERIFICAÇÃO COMPLETA ===\n";

// Lista de possíveis padrões problemáticos encontrados no código
$patterns = [
    '/^\(\d{2}\) \d{4,5}-\d{4}$/', // Padrão atual
    '/^#[0-9A-F]{6}$/i', // Cores hex
    '/^#[0-9A-Fa-f]{6}$/', // Cores hex
    '/^\d{5}-?\d{3}$/', // CEP
    '/^\d{8}$/', // CEP sem hífen
];

foreach ( $patterns as $pattern ) {
    testRegexPattern( $pattern, '(43) 99959-0945', 'Teste Geral' );
}

echo "\n=== RESULTADO ===\n";
echo "Script executado em " . date( 'Y-m-d H:i:s' ) . "\n";
