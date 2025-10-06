<?php

// Teste simples para verificar se o FinancialSummary funciona
require_once 'vendor/autoload.php';
require_once 'app/Services/FinancialSummary.php';

try {
    $financialSummary = new App\Services\FinancialSummary();
    $result           = $financialSummary->getMonthlySummary( 1 );

    if ( $result->isSuccess() ) {
        $data = $result->getData();
        echo "✅ SUCESSO! Dados obtidos:\n";
        echo "monthly_revenue: " . ( $data[ 'monthly_revenue' ] ?? 'não definido' ) . "\n";
        echo "pending_budgets: " . json_encode( $data[ 'pending_budgets' ] ?? 'não definido' ) . "\n";
        echo "overdue_payments: " . json_encode( $data[ 'overdue_payments' ] ?? 'não definido' ) . "\n";
        echo "next_month_projection: " . ( $data[ 'next_month_projection' ] ?? 'não definido' ) . "\n";

        // Verificar se todas as chaves necessárias estão presentes
        $requiredKeys = [ 'monthly_revenue', 'pending_budgets', 'overdue_payments', 'next_month_projection' ];
        $allPresent   = true;

        foreach ( $requiredKeys as $key ) {
            if ( !isset( $data[ $key ] ) ) {
                echo "❌ Chave '$key' ausente\n";
                $allPresent = false;
            }
        }

        if ( $allPresent ) {
            echo "✅ Todas as chaves necessárias estão presentes!\n";
        }
    } else {
        echo "❌ ERRO: " . $result->getMessage() . "\n";
    }
} catch ( Exception $e ) {
    echo "❌ Exceção: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
