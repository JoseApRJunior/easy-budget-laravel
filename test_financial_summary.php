<?php

require_once 'vendor/autoload.php';

use App\Services\FinancialSummary;
use App\Services\ProviderManagementService;
use Illuminate\Support\Facades\Auth;

// Simular autenticação (você precisará ajustar isso conforme seu setup)
$tenantId = 1; // Ajuste conforme necessário

try {
    // Criar instância do serviço
    $providerService = new ProviderManagementService(
        new FinancialSummary(),
        app( \App\Services\ActivityService::class),
    );

    // Chamar o método getDashboardData
    $dashboardData = $providerService->getDashboardData( $tenantId );

    // Verificar se financial_summary está presente e estruturado corretamente
    if ( isset( $dashboardData[ 'financial_summary' ] ) ) {
        echo "✅ Variável financial_summary definida com sucesso!\n";
        echo "Estrutura da variável:\n";
        echo json_encode( $dashboardData[ 'financial_summary' ], JSON_PRETTY_PRINT ) . "\n";

        // Verificar se as chaves esperadas estão presentes
        $expectedKeys = [ 'monthly_revenue', 'pending_budgets', 'overdue_payments', 'next_month_projection' ];
        foreach ( $expectedKeys as $key ) {
            if ( isset( $dashboardData[ 'financial_summary' ][ $key ] ) ) {
                echo "✅ Chave '$key' presente\n";
            } else {
                echo "❌ Chave '$key' ausente\n";
            }
        }
    } else {
        echo "❌ Variável financial_summary não definida\n";
    }

    // Mostrar todas as chaves disponíveis no resultado
    echo "\nChaves disponíveis no resultado:\n";
    echo implode( ', ', array_keys( $dashboardData ) ) . "\n";

} catch ( Exception $e ) {
    echo "Erro ao testar: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
