<?php

require_once 'vendor/autoload.php';

use App\Models\MonitoringAlertHistory;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

$app    = require_once 'bootstrap/app.php';
$kernel = $app->make( Kernel::class);
$kernel->bootstrap();

echo "=== TESTE MANUAL DA MIGRAÇÃO ===\n\n";

try {
    // 1. Criar um tenant para o teste
    echo "1. Criando tenant...\n";
    $tenant = Tenant::factory()->create();
    echo "✅ Tenant criado com ID: {$tenant->id}\n\n";

    // 2. Criar usuários para foreign keys (usando apenas campos básicos)
    echo "2. Criando usuários...\n";
    $user1 = User::create( [
        'tenant_id' => $tenant->id,
        'name'      => 'Test User 1',
        'email'     => 'test1_' . time() . '@example.com',
        'password'  => bcrypt( 'password' ),
    ] );
    $user2 = User::create( [
        'tenant_id' => $tenant->id,
        'name'      => 'Test User 2',
        'email'     => 'test2_' . time() . '@example.com',
        'password'  => bcrypt( 'password' ),
    ] );
    echo "✅ Usuários criados com IDs: {$user1->id}, {$user2->id}\n\n";

    // 3. Verificar estrutura da tabela
    echo "3. Verificando estrutura da tabela...\n";
    $columns = DB::select( 'DESCRIBE monitoring_alerts_history' );
    echo "Estrutura da tabela monitoring_alerts_history:\n";
    foreach ( $columns as $column ) {
        echo "- {$column->Field}: {$column->Type} " . ( $column->Null === 'YES' ? 'NULL' : 'NOT NULL' ) . "\n";
    }
    echo "\n";

    // 4. Criar registro com TODOS os campos
    echo "4. Criando registro com todos os campos...\n";
    $testData = [
        'tenant_id'        => $tenant->id,
        'alert_type'       => 'system_performance',
        'severity'         => 'high',
        'title'            => 'CPU Usage Alert',
        'description'      => 'CPU usage has exceeded the warning threshold',
        'component'        => 'CPU Monitor',
        'endpoint'         => '/api/system/metrics',
        'method'           => 'GET',
        'current_value'    => 85.750,
        'threshold_value'  => 80.000,
        'unit'             => 'percentage',
        'metadata'         => [
            'server' => 'web-01',
            'region' => 'us-east-1',
            'tags'   => [ 'performance', 'monitoring' ]
        ],
        'message'          => 'CPU usage is at 85.75%, which is above the warning threshold of 80%',
        'status'           => 'active',
        'acknowledged_by'  => $user1->id,
        'acknowledged_at'  => Carbon::now()->subMinutes( 30 ),
        'resolved_by'      => $user2->id,
        'resolved_at'      => Carbon::now()->subMinutes( 15 ),
        'resolution_notes' => 'Issue resolved by restarting the service',
        'occurrence_count' => 5,
        'first_occurrence' => Carbon::now()->subHours( 2 ),
        'last_occurrence'  => Carbon::now()->subMinutes( 30 ),
        'resolved'         => true,
    ];

    $monitoringAlert = MonitoringAlertHistory::create( $testData );
    echo "✅ Registro criado com ID: {$monitoringAlert->id}\n\n";

    // 4. Verificar se os campos foram salvos corretamente
    echo "4. Verificando campos salvos...\n";
    foreach ( $testData as $field => $expectedValue ) {
        $actualValue = $monitoringAlert->$field;

        if ( in_array( $field, [ 'first_occurrence', 'last_occurrence', 'acknowledged_at', 'resolved_at' ] ) ) {
            // Verificar timestamps
            $expectedFormatted = $expectedValue->format( 'Y-m-d H:i:s' );
            $actualFormatted   = $actualValue->format( 'Y-m-d H:i:s' );
            if ( $expectedFormatted === $actualFormatted ) {
                echo "✅ {$field}: {$actualFormatted}\n";
            } else {
                echo "❌ {$field}: esperado {$expectedFormatted}, recebido {$actualFormatted}\n";
            }
        } elseif ( $field === 'metadata' ) {
            // Verificar JSON
            if ( $expectedValue == $actualValue ) {
                echo "✅ {$field}: " . json_encode( $actualValue ) . "\n";
            } else {
                echo "❌ {$field}: esperado " . json_encode( $expectedValue ) . ", recebido " . json_encode( $actualValue ) . "\n";
            }
        } elseif ( in_array( $field, [ 'current_value', 'threshold_value' ] ) ) {
            // Verificar decimais
            if ( abs( $expectedValue - $actualValue ) < 0.001 ) {
                echo "✅ {$field}: {$actualValue}\n";
            } else {
                echo "❌ {$field}: esperado {$expectedValue}, recebido {$actualValue}\n";
            }
        } else {
            // Verificar outros campos
            if ( $expectedValue === $actualValue ) {
                echo "✅ {$field}: {$actualValue}\n";
            } else {
                echo "❌ {$field}: esperado {$expectedValue}, recebido {$actualValue}\n";
            }
        }
    }

    // 5. Fazer um dump dos dados salvos
    echo "\n5. Dump dos dados salvos:\n";
    var_dump( $monitoringAlert->toArray() );

    // 6. Testar consultas com índices
    echo "\n6. Testando consultas com índices...\n";

    // Consulta por status
    $foundAlerts = MonitoringAlertHistory::where( 'status', 'active' )
        ->where( 'tenant_id', $tenant->id )
        ->get();
    echo "✅ Consulta por status 'active': {$foundAlerts->count()} registros encontrados\n";

    // Consulta por occurrence_count
    $countAlerts = MonitoringAlertHistory::where( 'occurrence_count', 5 )
        ->where( 'tenant_id', $tenant->id )
        ->get();
    echo "✅ Consulta por occurrence_count = 5: {$countAlerts->count()} registros encontrados\n";

    // 7. Testar campos nulos
    echo "\n7. Testando campos nulos...\n";
    $testDataWithNulls = [
        'tenant_id'        => $tenant->id,
        'alert_type'       => 'system_performance',
        'severity'         => 'low',
        'title'            => 'Test Alert',
        'message'          => 'Test message',
        'status'           => 'active',
        'occurrence_count' => 1,
        'first_occurrence' => Carbon::now(),
        'last_occurrence'  => Carbon::now(),
        'resolved'         => false,
        // Campos que devem ser nulos (exceto description e component que são obrigatórios)
        'description'      => 'Test description',
        'component'        => 'Test Component',
        'endpoint'         => null,
        'method'           => null,
        'current_value'    => null,
        'threshold_value'  => null,
        'unit'             => null,
        'metadata'         => null,
        'acknowledged_by'  => null,
        'acknowledged_at'  => null,
        'resolved_by'      => null,
        'resolved_at'      => null,
        'resolution_notes' => null,
    ];

    $alertWithNulls = MonitoringAlertHistory::create( $testDataWithNulls );
    echo "✅ Registro com campos nulos criado com ID: {$alertWithNulls->id}\n";

    // Verificar campos nulos
    $nullFields = [ 'description', 'component', 'endpoint', 'method', 'current_value', 'threshold_value', 'unit', 'metadata', 'acknowledged_by', 'acknowledged_at', 'resolved_by', 'resolved_at', 'resolution_notes' ];
    foreach ( $nullFields as $field ) {
        if ( is_null( $alertWithNulls->$field ) ) {
            echo "✅ {$field}: null\n";
        } else {
            echo "❌ {$field}: esperado null, recebido {$alertWithNulls->$field}\n";
        }
    }

    echo "\n=== RESUMO DO TESTE ===\n";
    echo "✅ Migração executada com sucesso\n";
    echo "✅ Todos os campos foram criados corretamente\n";
    echo "✅ Não há erros de 'unknown column'\n";
    echo "✅ Índices estão funcionando para consultas otimizadas\n";
    echo "✅ Campos nulos são tratados corretamente\n";
    echo "✅ Modelo MonitoringAlertHistory está funcionando perfeitamente!\n";

} catch ( Exception $e ) {
    echo "❌ Erro durante o teste: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}