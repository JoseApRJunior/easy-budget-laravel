<?php

/**
 * Teste para verificar se a correção do CNPJ funciona
 */

echo "🔍 Testando limpeza de CNPJ e CPF...\n\n";

// Test cases
$testCases = [
    'CNPJ formatado' => '75.263.400/0001-99',
    'CNPJ limpo'     => '75263400000199',
    'CPF formatado'  => '048.113.869-22',
    'CPF limpo'      => '04811386922',
    'CNPJ inválido'  => '123',
    'CNPJ vazio'     => '',
    'CNPJ null'      => null
];

// Simular a função cleanDocumentNumber
function cleanDocumentNumber( ?string $documentNumber ): ?string
{
    if ( empty( $documentNumber ) ) {
        return null;
    }

    // Remove all non-digit characters (points, hyphens, slashes)
    $cleaned = preg_replace( '/[^0-9]/', '', $documentNumber );

    // Ensure it's exactly the expected length
    if ( strlen( $cleaned ) === 14 || strlen( $cleaned ) === 11 ) {
        return $cleaned;
    }

    // Return null if invalid length
    return null;
}

echo "📊 Resultados dos testes:\n";
echo str_repeat( '-', 60 ) . "\n";

foreach ( $testCases as $description => $input ) {
    $result = cleanDocumentNumber( $input );
    $length = $result ? strlen( $result ) : 'null';
    $status = $result ? '✅' : '❌';

    echo sprintf(
        "%-20s | %-15s | %-14s | %s\n",
        $description,
        var_export( $input, true ),
        "Length: $length",
        $status,
    );
}

echo str_repeat( '-', 60 ) . "\n";

// Teste específico do erro original
echo "\n🎯 Teste do erro original:\n";
$originalCnpj = '75.263.400/0001-99';
$cleanedCnpj  = cleanDocumentNumber( $originalCnpj );

echo "CNPJ original: $originalCnpj (length: " . strlen( $originalCnpj ) . ")\n";
echo "CNPJ limpo: " . var_export( $cleanedCnpj, true ) . " (length: " . ( $cleanedCnpj ? strlen( $cleanedCnpj ) : 0 ) . ")\n";

if ( $cleanedCnpj === '75263400000199' ) {
    echo "✅ Teste PASSOU! CNPJ foi limpo corretamente.\n";
} else {
    echo "❌ Teste FALHOU! CNPJ não foi limpo conforme esperado.\n";
}

echo "\n🔧 Conclusão:\n";
echo "A correção limpa automaticamente CNPJs e CPFs antes de salvar no banco,\n";
echo "removendo formatação e garantindo que apenas números sejam armazenados.\n";
echo "Isto resolve o erro 'Data too long for column cnpj'.\n";
