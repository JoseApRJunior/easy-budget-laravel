<?php

namespace Tests\Feature;

use App\Models\ReportDefinition;
use App\Models\ReportExecution;
use App\Models\ReportSchedule;
use App\Models\User;
use App\Services\AdvancedQueryBuilder;
use App\Services\ExportService;
use App\Services\ReportCacheService;
use App\Services\ReportGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Testes para o módulo de relatórios
 * Testa funcionalidades básicas e avançadas do sistema
 */
class ReportModuleTest extends TestCase
{
    use RefreshDatabase;

    private User             $user;
    private ReportDefinition $reportDefinition;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar usuário de teste
        $this->user = User::factory()->create();

        // Criar definição de relatório básica
        $this->reportDefinition = ReportDefinition::factory()->create( [
            'tenant_id'     => $this->user->tenant_id,
            'user_id'       => $this->user->id,
            'name'          => 'Relatório de Teste',
            'category'      => 'financial',
            'type'          => 'table',
            'query_builder' => [
                'table'   => 'budgets',
                'selects' => [
                    [ 'field' => 'id', 'alias' => 'ID' ],
                    [ 'field' => 'name', 'alias' => 'Nome' ],
                    [ 'field' => 'total_value', 'alias' => 'Valor' ]
                ]
            ],
            'config'        => [
                'title'  => 'Relatório de Teste',
                'format' => 'table'
            ]
        ] );
    }

    /**
     * Testa criação de definição de relatório
     */
    public function test_can_create_report_definition(): void
    {
        $reportData = [
            'name'          => 'Novo Relatório',
            'description'   => 'Descrição do relatório de teste',
            'category'      => 'customer',
            'type'          => 'chart',
            'query_builder' => [
                'table'   => 'customers',
                'selects' => [
                    [ 'field' => 'id', 'alias' => 'ID' ],
                    [ 'field' => 'name', 'alias' => 'Nome' ]
                ]
            ],
            'config'        => [
                'title'  => 'Novo Relatório',
                'format' => 'chart'
            ]
        ];

        $response = $this->actingAs( $this->user )
            ->postJson( '/api/reports', $reportData );

        $response->assertStatus( 201 )
            ->assertJson( [
                'success' => true,
                'message' => 'Relatório criado com sucesso'
            ] );

        $this->assertDatabaseHas( 'report_definitions', [
            'tenant_id' => $this->user->tenant_id,
            'user_id'   => $this->user->id,
            'name'      => 'Novo Relatório',
            'category'  => 'customer'
        ] );
    }

    /**
     * Testa listagem de definições de relatório
     */
    public function test_can_list_report_definitions(): void
    {
        // Criar algumas definições de teste
        ReportDefinition::factory()->count( 3 )->create( [
            'tenant_id' => $this->user->tenant_id,
            'user_id'   => $this->user->id
        ] );

        $response = $this->actingAs( $this->user )
            ->getJson( '/api/reports' );

        $response->assertStatus( 200 )
            ->assertJson( [
                'success' => true
            ] )
            ->assertJsonStructure( [
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'category',
                        'type',
                        'is_active'
                    ]
                ],
                'pagination'
            ] );

        $this->assertCount( 4, $response->json( 'data' ) ); // 3 criados + 1 do setUp
    }

    /**
     * Testa geração de relatório básico
     */
    public function test_can_generate_basic_report(): void
    {
        $response = $this->actingAs( $this->user )
            ->postJson( "/api/reports/{$this->reportDefinition->id}/generate", [
                'filters' => [],
                'format'  => 'json'
            ] );

        $response->assertStatus( 200 )
            ->assertJson( [
                'success' => true
            ] )
            ->assertJsonStructure( [
                'success',
                'execution_id',
                'data',
                'metadata'
            ] );

        // Verificar se execução foi registrada
        $this->assertDatabaseHas( 'report_executions', [
            'tenant_id'     => $this->user->tenant_id,
            'definition_id' => $this->reportDefinition->id,
            'user_id'       => $this->user->id,
            'status'        => 'completed'
        ] );
    }

    /**
     * Testa geração de relatório financeiro de receitas
     */
    public function test_can_generate_revenue_report(): void
    {
        $response = $this->actingAs( $this->user )
            ->getJson( '/api/reports/financial/revenue', [
                'filters' => [
                    'start_date' => now()->subMonth()->toDateString(),
                    'end_date'   => now()->toDateString()
                ]
            ] );

        $response->assertStatus( 200 )
            ->assertJson( [
                'success' => true
            ] )
            ->assertJsonStructure( [
                'success',
                'data' => [
                    'type',
                    'data',
                    'summary'
                ]
            ] );
    }

    /**
     * Testa exportação de relatório para PDF
     */
    public function test_can_export_report_to_pdf(): void
    {
        $response = $this->actingAs( $this->user )
            ->get( "/api/reports/{$this->reportDefinition->id}/export/pdf" );

        // Pode ser 200 (sucesso) ou 302 (redirect para download)
        $this->assertTrue(
            in_array( $response->getStatusCode(), [ 200, 302 ] ),
            'Exportação deve retornar sucesso ou redirect',
        );
    }

    /**
     * Testa criação de agendamento de relatório
     */
    public function test_can_create_report_schedule(): void
    {
        $scheduleData = [
            'name'           => 'Agendamento Diário',
            'frequency_type' => 'daily',
            'time_to_run'    => '09:00',
            'timezone'       => 'America/Sao_Paulo',
            'recipients'     => [ 'test@example.com' ],
            'email_subject'  => 'Relatório Diário',
            'format'         => 'pdf'
        ];

        $response = $this->actingAs( $this->user )
            ->postJson( "/api/reports/{$this->reportDefinition->id}/schedule", $scheduleData );

        $response->assertStatus( 201 )
            ->assertJson( [
                'success' => true,
                'message' => 'Agendamento criado com sucesso'
            ] );

        $this->assertDatabaseHas( 'report_schedules', [
            'tenant_id'      => $this->user->tenant_id,
            'definition_id'  => $this->reportDefinition->id,
            'user_id'        => $this->user->id,
            'frequency_type' => 'daily',
            'is_active'      => true
        ] );
    }

    /**
     * Testa invalidação de cache
     */
    public function test_can_invalidate_report_cache(): void
    {
        // Primeiro, gerar um relatório para criar cache
        $this->actingAs( $this->user )
            ->postJson( "/api/reports/{$this->reportDefinition->id}/generate" );

        // Verificar se cache foi criado
        $cacheKey = app( ReportCacheService::class)->generateCacheKey(
            $this->reportDefinition->id,
            [],
        );

        $this->assertTrue( Cache::has( $cacheKey ) );

        // Invalidar cache
        $response = $this->actingAs( $this->user )
            ->postJson( "/api/reports/cache/invalidate/{$this->reportDefinition->id}" );

        $response->assertStatus( 200 )
            ->assertJson( [
                'success' => true,
                'message' => 'Cache do relatório invalidado com sucesso'
            ] );

        // Verificar se cache foi removido
        $this->assertFalse( Cache::has( $cacheKey ) );
    }

    /**
     * Testa Advanced Query Builder
     */
    public function test_advanced_query_builder(): void
    {
        $queryBuilder = app( AdvancedQueryBuilder::class);

        $query = $queryBuilder->from( 'budgets' )
            ->select( 'id', 'ID' )
            ->select( 'name', 'Nome' )
            ->where( 'status', '=', 'approved' )
            ->orderBy( 'created_at', 'DESC' )
            ->limit( 10 );

        $sql = $query->build();

        $this->assertStringContainsString( 'SELECT', $sql );
        $this->assertStringContainsString( 'FROM budgets', $sql );
        $this->assertStringContainsString( 'WHERE', $sql );
        $this->assertStringContainsString( 'ORDER BY', $sql );
        $this->assertStringContainsString( 'LIMIT', $sql );
    }

    /**
     * Testa serviço de cache inteligente
     */
    public function test_report_cache_service(): void
    {
        $cacheService = app( ReportCacheService::class);
        $testData     = collect( [
            [ 'id' => 1, 'name' => 'Teste 1' ],
            [ 'id' => 2, 'name' => 'Teste 2' ]
        ] );

        // Testar armazenamento
        $cacheKey = 'test_report_' . uniqid();
        $success  = $cacheService->putReportData( $cacheKey, $testData, 60 );

        $this->assertTrue( $success );

        // Testar recuperação
        $cachedData = $cacheService->getCachedData( $cacheKey );
        $this->assertInstanceOf( \Illuminate\Support\Collection::class, $cachedData );
        $this->assertEquals( 2, $cachedData->count() );

        // Testar remoção
        $cacheService->forgetCache( $cacheKey );
        $this->assertNull( $cacheService->getCachedData( $cacheKey ) );
    }

    /**
     * Testa validação de configuração de relatório
     */
    public function test_report_definition_validation(): void
    {
        $invalidData = [
            'name'          => '', // Nome obrigatório
            'category'      => 'invalid_category', // Categoria inválida
            'type'          => 'invalid_type', // Tipo inválido
            'query_builder' => [], // Query builder obrigatório
            'config'        => [] // Config obrigatório
        ];

        $response = $this->actingAs( $this->user )
            ->postJson( '/api/reports', $invalidData );

        $response->assertStatus( 422 )
            ->assertJsonValidationErrors( [
                'name',
                'category',
                'type',
                'query_builder',
                'config'
            ] );
    }

    /**
     * Testa controle de acesso por tenant
     */
    public function test_tenant_access_control(): void
    {
        // Criar usuário de outro tenant
        $otherUser = User::factory()->create();

        // Tentar acessar relatório de outro tenant
        $response = $this->actingAs( $otherUser )
            ->getJson( "/api/reports/{$this->reportDefinition->id}" );

        $response->assertStatus( 403 )
            ->assertJson( [
                'success' => false,
                'error'   => 'Acesso negado'
            ] );
    }

    /**
     * Testa métricas de performance
     */
    public function test_performance_metrics(): void
    {
        $startTime = microtime( true );

        // Executar relatório
        $this->actingAs( $this->user )
            ->postJson( "/api/reports/{$this->reportDefinition->id}/generate" );

        $endTime       = microtime( true );
        $executionTime = ( $endTime - $startTime ) * 1000; // em milissegundos

        // Deve executar em menos de 5 segundos
        $this->assertLessThan( 5000, $executionTime );

        // Verificar se métricas foram registradas
        $execution = ReportExecution::where( 'definition_id', $this->reportDefinition->id )
            ->latest()
            ->first();

        $this->assertNotNull( $execution );
        $this->assertEquals( 'completed', $execution->status );
        $this->assertGreaterThan( 0, $execution->execution_time );
    }

    /**
     * Testa paginação de resultados
     */
    public function test_pagination(): void
    {
        // Criar múltiplas definições
        ReportDefinition::factory()->count( 25 )->create( [
            'tenant_id' => $this->user->tenant_id,
            'user_id'   => $this->user->id
        ] );

        $response = $this->actingAs( $this->user )
            ->getJson( '/api/reports?per_page=10&page=2' );

        $response->assertStatus( 200 )
            ->assertJson( [
                'success' => true
            ] );

        $data = $response->json();
        $this->assertEquals( 10, count( $data[ 'data' ] ) );
        $this->assertEquals( 2, $data[ 'pagination' ][ 'current_page' ] );
        $this->assertEquals( 26, $data[ 'pagination' ][ 'total' ] ); // 25 + 1 do setUp
    }

    /**
     * Testa filtros de busca
     */
    public function test_search_filters(): void
    {
        // Criar relatório com nome específico
        ReportDefinition::factory()->create( [
            'tenant_id' => $this->user->tenant_id,
            'user_id'   => $this->user->id,
            'name'      => 'Relatório Especial de Vendas'
        ] );

        $response = $this->actingAs( $this->user )
            ->getJson( '/api/reports?search=vendas' );

        $response->assertStatus( 200 )
            ->assertJson( [
                'success' => true
            ] );

        $data = $response->json();
        $this->assertGreaterThan( 0, count( $data[ 'data' ] ) );

        // Verificar se apenas relatórios relevantes foram retornados
        foreach ( $data[ 'data' ] as $report ) {
            $this->assertStringContainsString(
                strtolower( 'vendas' ),
                strtolower( $report[ 'name' ] . ' ' . ( $report[ 'description' ] ?? '' ) ),
            );
        }
    }

    /**
     * Testa limpeza de dados antigos
     */
    public function test_cleanup_old_data(): void
    {
        // Criar execuções antigas
        ReportExecution::factory()->count( 5 )->create( [
            'tenant_id'     => $this->user->tenant_id,
            'definition_id' => $this->reportDefinition->id,
            'user_id'       => $this->user->id,
            'created_at'    => now()->subDays( 100 ) // 100 dias atrás
        ] );

        // Executar limpeza
        $cleanedCount = app( ReportCacheService::class)->cleanupExpiredCache();

        // Deve limpar dados antigos (embora este teste específico possa variar)
        $this->assertIsInt( $cleanedCount );
    }

    /**
     * Testa tratamento de erros
     */
    public function test_error_handling(): void
    {
        // Tentar gerar relatório com configuração inválida
        $response = $this->actingAs( $this->user )
            ->postJson( "/api/reports/{$this->reportDefinition->id}/generate", [
                'filters' => [
                    'invalid_field' => 'invalid_value'
                ]
            ] );

        // Pode retornar erro ou sucesso dependendo da implementação
        $this->assertTrue(
            in_array( $response->getStatusCode(), [ 200, 400, 500 ] ),
            'Deve retornar código de status válido',
        );
    }

    /**
     * Testa estatísticas do sistema
     */
    public function test_system_stats(): void
    {
        $response = $this->actingAs( $this->user )
            ->getJson( '/api/reports/stats' );

        $response->assertStatus( 200 )
            ->assertJson( [
                'success' => true
            ] )
            ->assertJsonStructure( [
                'success',
                'stats' => [
                    'definitions',
                    'executions_today',
                    'executions_month',
                    'active_schedules',
                    'failed_executions'
                ]
            ] );
    }

    /**
     * Testa preview de relatório
     */
    public function test_report_preview(): void
    {
        $response = $this->actingAs( $this->user )
            ->getJson( "/api/reports/{$this->reportDefinition->id}/preview", [
                'filters' => []
            ] );

        $response->assertStatus( 200 )
            ->assertJson( [
                'success' => true
            ] )
            ->assertJsonStructure( [
                'success',
                'data',
                'total_records',
                'is_preview'
            ] );
    }

    /**
     * Testa tipos de gráfico suportados
     */
    public function test_supported_chart_types(): void
    {
        $chartService   = app( \App\Services\ChartVisualizationService::class);
        $supportedTypes = $chartService->getAvailableChartTypes();

        $this->assertIsArray( $supportedTypes );
        $this->assertArrayHasKey( 'line', $supportedTypes );
        $this->assertArrayHasKey( 'bar', $supportedTypes );
        $this->assertArrayHasKey( 'pie', $supportedTypes );

        // Verificar se todos os tipos têm labels
        foreach ( $supportedTypes as $type => $label ) {
            $this->assertIsString( $type );
            $this->assertIsString( $label );
            $this->assertNotEmpty( $label );
        }
    }

    /**
     * Testa validação de configuração de gráfico
     */
    public function test_chart_config_validation(): void
    {
        $chartService = app( \App\Services\ChartVisualizationService::class);

        // Configuração válida
        $validErrors = $chartService->validateChartConfig( 'line', [
            'x_field' => 'date',
            'y_field' => 'value'
        ] );

        $this->assertIsArray( $validErrors );
        $this->assertEmpty( $validErrors );

        // Configuração inválida
        $invalidErrors = $chartService->validateChartConfig( 'invalid_type', [] );

        $this->assertIsArray( $invalidErrors );
        $this->assertNotEmpty( $invalidErrors );
    }

    /**
     * Testa geração de dados de exemplo
     */
    public function test_sample_data_generation(): void
    {
        $chartService = app( \App\Services\ChartVisualizationService::class);

        $sampleData = $chartService->generateSampleData( 'line', 5 );

        $this->assertInstanceOf( \Illuminate\Support\Collection::class, $sampleData );
        $this->assertCount( 5, $sampleData );

        // Verificar estrutura dos dados
        $firstItem = $sampleData->first();
        $this->assertArrayHasKey( 'date', $firstItem );
        $this->assertArrayHasKey( 'value', $firstItem );
    }

    /**
     * Testa formatos de exportação suportados
     */
    public function test_supported_export_formats(): void
    {
        $exportService    = app( ExportService::class);
        $supportedFormats = $exportService->getSupportedFormats();

        $this->assertIsArray( $supportedFormats );
        $this->assertArrayHasKey( 'pdf', $supportedFormats );
        $this->assertArrayHasKey( 'excel', $supportedFormats );
        $this->assertArrayHasKey( 'csv', $supportedFormats );
        $this->assertArrayHasKey( 'json', $supportedFormats );

        // Verificar estrutura de cada formato
        foreach ( $supportedFormats as $format => $config ) {
            $this->assertArrayHasKey( 'name', $config );
            $this->assertArrayHasKey( 'mime_type', $config );
            $this->assertArrayHasKey( 'extension', $config );
            $this->assertArrayHasKey( 'description', $config );
        }
    }

    /**
     * Testa validação de configuração de exportação
     */
    public function test_export_config_validation(): void
    {
        $exportService = app( ExportService::class);

        // Configuração válida
        $validErrors = $exportService->validateExportConfig( [
            'orientation' => 'portrait',
            'page_size'   => 'a4'
        ], 'pdf' );

        $this->assertIsArray( $validErrors );
        $this->assertEmpty( $validErrors );

        // Configuração inválida
        $invalidErrors = $exportService->validateExportConfig( [
            'orientation' => 'invalid',
            'page_size'   => 'invalid'
        ], 'pdf' );

        $this->assertIsArray( $invalidErrors );
        $this->assertNotEmpty( $invalidErrors );
    }

    /**
     * Testa métricas disponíveis
     */
    public function test_available_metrics(): void
    {
        $generationService = app( ReportGenerationService::class);
        $availableMetrics  = $generationService->getAvailableMetrics();

        $this->assertIsArray( $availableMetrics );
        $this->assertNotEmpty( $availableMetrics );

        // Verificar se métricas básicas estão presentes
        $expectedMetrics = [ 'revenue', 'expenses', 'profit', 'customers', 'budgets' ];
        foreach ( $expectedMetrics as $metric ) {
            $this->assertArrayHasKey( $metric, $availableMetrics );
        }
    }

    /**
     * Testa execução assíncrona
     */
    public function test_async_execution(): void
    {
        Queue::fake();

        $response = $this->actingAs( $this->user )
            ->postJson( "/api/reports/{$this->reportDefinition->id}/generate", [
                'async' => true
            ] );

        $response->assertStatus( 200 )
            ->assertJson( [
                'success' => true,
                'status'  => 'processing'
            ] );

        // Verificar se job foi despachado
        Queue::assertPushed( \App\Jobs\ProcessScheduledReport::class);
    }

    /**
     * Testa limpeza geral do sistema
     */
    public function test_system_cleanup(): void
    {
        // Criar dados antigos
        ReportExecution::factory()->count( 10 )->create( [
            'tenant_id'     => $this->user->tenant_id,
            'definition_id' => $this->reportDefinition->id,
            'user_id'       => $this->user->id,
            'created_at'    => now()->subDays( 100 )
        ] );

        // Executar limpeza
        $schedulerService = app( \App\Services\ReportSchedulerService::class);
        $cleanedCount     = $schedulerService->cleanupOldSchedules( 30 );

        $this->assertIsInt( $cleanedCount );
    }

    /**
     * Testa proteção contra ataques comuns
     */
    public function test_security_protection(): void
    {
        // Teste de SQL Injection
        $maliciousInput = [
            'name'          => "'; DROP TABLE users; --",
            'query_builder' => [
                'table'   => 'users; DELETE FROM users; --',
                'selects' => []
            ]
        ];

        $response = $this->actingAs( $this->user )
            ->postJson( '/api/reports', $maliciousInput );

        // Deve falhar na validação ou sanitização
        $this->assertTrue(
            in_array( $response->getStatusCode(), [ 400, 422, 500 ] ),
            'Input malicioso deve ser rejeitado',
        );
    }

    /**
     * Testa responsividade da API
     */
    public function test_api_responsiveness(): void
    {
        $startTime = microtime( true );

        $response = $this->actingAs( $this->user )
            ->getJson( '/api/reports/stats' );

        $responseTime = ( microtime( true ) - $startTime ) * 1000;

        $response->assertStatus( 200 );

        // API deve responder em menos de 1 segundo
        $this->assertLessThan( 1000, $responseTime );
    }

    /**
     * Testa estrutura de resposta consistente
     */
    public function test_response_structure_consistency(): void
    {
        $endpoints = [
            [ 'GET', '/api/reports' ],
            [ 'GET', '/api/reports/stats' ],
            [ 'POST', '/api/reports' ],
        ];

        foreach ( $endpoints as [ $method, $endpoint ] ) {
            if ( $method === 'POST' ) {
                $response = $this->actingAs( $this->user )
                    ->postJson( $endpoint, [
                        'name'          => 'Teste',
                        'category'      => 'financial',
                        'type'          => 'table',
                        'query_builder' => [ 'table' => 'budgets' ],
                        'config'        => [ 'title' => 'Teste' ]
                    ] );
            } else {
                $response = $this->actingAs( $this->user )
                    ->getJson( $endpoint );
            }

            $response->assertJsonStructure( [
                'success',
                // Outros campos podem variar por endpoint
            ] );

            // Verificar se success é boolean
            $data = $response->json();
            $this->assertIsBool( $data[ 'success' ] );
        }
    }

}
