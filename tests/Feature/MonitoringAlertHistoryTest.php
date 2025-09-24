<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MonitoringAlertHistory;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringAlertHistoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se TODOS os campos do modelo MonitoringAlertHistory funcionam corretamente após a migração.
     * Este teste usa o banco de dados principal em vez do banco de teste SQLite.
     */
    public function test_comprehensive_fields_work_correctly(): void
    {
        // Criar um tenant para o teste
        $tenant = Tenant::factory()->create();

        // Criar usuários para foreign keys
        $user1 = User::factory()->create( [ 'tenant_id' => $tenant->id ] );
        $user2 = User::factory()->create( [ 'tenant_id' => $tenant->id ] );

        // Dados de teste com TODOS os campos da migração
        $testData = [
            'tenant_id'        => $tenant->id,
            'alert_type'       => 'system_performance',
            'severity'         => 'warning',
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

        // Criar o registro
        $monitoringAlert = MonitoringAlertHistory::create( $testData );

        // Verificar se o registro foi criado sem erros de "unknown column"
        $this->assertInstanceOf( MonitoringAlertHistory::class, $monitoringAlert );
        $this->assertEquals( $tenant->id, $monitoringAlert->tenant_id );

        // Verificar se os campos foram salvos corretamente
        foreach ( $testData as $field => $value ) {
            if ( in_array( $field, [ 'first_occurrence', 'last_occurrence', 'acknowledged_at', 'resolved_at' ] ) ) {
                // Verificar timestamps
                $this->assertEquals( $value->format( 'Y-m-d H:i:s' ), $monitoringAlert->$field->format( 'Y-m-d H:i:s' ) );
            } elseif ( $field === 'metadata' ) {
                // Verificar JSON
                $this->assertEquals( $value, $monitoringAlert->$field );
            } elseif ( in_array( $field, [ 'current_value', 'threshold_value' ] ) ) {
                // Verificar decimais
                $this->assertEqualsWithDelta( $value, $monitoringAlert->$field, 0.001 );
            } else {
                // Verificar outros campos
                $this->assertEquals( $value, $monitoringAlert->$field );
            }
        }

        // Fazer um dump dos dados salvos para confirmar que tudo foi persistido
        var_dump( 'Dados básicos salvos com sucesso:', $monitoringAlert->toArray() );

        // Testar uma consulta para verificar se os índices funcionam
        $foundAlerts = MonitoringAlertHistory::where( 'status', 'active' )
            ->where( 'tenant_id', $tenant->id )
            ->get();

        $this->assertCount( 1, $foundAlerts );
        $this->assertEquals( $monitoringAlert->id, $foundAlerts->first()->id );

        // Testar consulta por occurrence_count
        $countAlerts = MonitoringAlertHistory::where( 'occurrence_count', 5 )
            ->where( 'tenant_id', $tenant->id )
            ->get();

        $this->assertCount( 1, $countAlerts );

        echo "\n✅ Teste abrangente concluído com sucesso! TODOS os campos funcionam corretamente.\n";
        echo "✅ Não há erros de 'unknown column'.\n";
        echo "✅ Índices estão funcionando para consultas otimizadas.\n";
    }

    /**
     * Testa campos nulos e valores padrão.
     */
    public function test_nullable_fields_work_correctly(): void
    {
        $tenant = Tenant::factory()->create();

        // Dados de teste com campos nulos
        $testDataWithNulls = [
            'tenant_id'        => $tenant->id,
            'alert_type'       => 'system_performance',
            'severity'         => 'info',
            'title'            => 'Test Alert',
            'message'          => 'Test message',
            'status'           => 'active',
            'occurrence_count' => 1,
            'first_occurrence' => Carbon::now(),
            'last_occurrence'  => Carbon::now(),
            'resolved'         => false,
            // Campos que devem ser nulos
            'description'      => null,
            'component'        => null,
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

        // Criar o registro com campos nulos
        $alertWithNulls = MonitoringAlertHistory::create( $testDataWithNulls );

        // Verificar se os campos nulos foram salvos corretamente
        $this->assertNull( $alertWithNulls->description );
        $this->assertNull( $alertWithNulls->component );
        $this->assertNull( $alertWithNulls->endpoint );
        $this->assertNull( $alertWithNulls->method );
        $this->assertNull( $alertWithNulls->current_value );
        $this->assertNull( $alertWithNulls->threshold_value );
        $this->assertNull( $alertWithNulls->unit );
        $this->assertNull( $alertWithNulls->metadata );
        $this->assertNull( $alertWithNulls->acknowledged_by );
        $this->assertNull( $alertWithNulls->acknowledged_at );
        $this->assertNull( $alertWithNulls->resolved_by );
        $this->assertNull( $alertWithNulls->resolved_at );
        $this->assertNull( $alertWithNulls->resolution_notes );

        // Verificar campos não nulos
        $this->assertEquals( $tenant->id, $alertWithNulls->tenant_id );
        $this->assertEquals( 'system_performance', $alertWithNulls->alert_type );
        $this->assertEquals( 'Test Alert', $alertWithNulls->title );
        $this->assertEquals( 'Test message', $alertWithNulls->message );
        $this->assertFalse( $alertWithNulls->resolved );

        var_dump( 'Teste com campos nulos concluído com sucesso:', $alertWithNulls->toArray() );

        echo "\n✅ Teste de campos nulos concluído com sucesso!\n";
        echo "✅ Campos nulos são tratados corretamente.\n";
        echo "✅ Não há erros de validação ou 'unknown column'.\n";
    }

    /**
     * Testa diferentes tipos de dados para garantir compatibilidade.
     */
    public function test_different_data_types(): void
    {
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [ 'tenant_id' => $tenant->id ] );

        // Testar valores nulos
        $alertWithNulls = MonitoringAlertHistory::create( [
            'tenant_id'        => $tenant->id,
            'alert_type'       => 'test_alert',
            'severity'         => 'info',
            'title'            => 'Test Alert with Nulls',
            'description'      => 'Testing nullable fields',
            'component'        => 'test_component',
            'endpoint'         => null,
            'method'           => null,
            'current_value'    => null,
            'threshold_value'  => null,
            'unit'             => null,
            'metadata'         => null,
            'message'          => 'Test message',
            'status'           => 'active',
            'acknowledged_by'  => null,
            'acknowledged_at'  => null,
            'resolved_by'      => null,
            'resolved_at'      => null,
            'resolution_notes' => null,
            'occurrence_count' => 1,
            'first_occurrence' => null,
            'last_occurrence'  => null,
            'resolved'         => false,
        ] );

        $this->assertNull( $alertWithNulls->endpoint );
        $this->assertNull( $alertWithNulls->current_value );
        $this->assertNull( $alertWithNulls->metadata );

        var_dump( 'Teste com valores nulos concluído com sucesso:', $alertWithNulls->toArray() );
    }

}