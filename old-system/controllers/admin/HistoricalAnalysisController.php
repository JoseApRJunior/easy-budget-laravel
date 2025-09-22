<?php

namespace app\controllers\admin;

use app\controllers\AbstractController;
use app\database\servicesORM\HistoricalAnalysisService;
use app\database\entitiesORM\TenantEntity;
use core\library\Response;
use core\library\Twig;
use DateTimeImmutable;
use Exception;

/**
 * Controller para análise histórica de métricas e alertas
 *
 * Fornece interface web para visualização de relatórios históricos,
 * análise de tendências e identificação de gargalos de performance.
 */
class HistoricalAnalysisController extends AbstractController
{
    /**
     * Construtor do controller de análise histórica
     *
     * @param Twig $twig Serviço de templates
     * @param HistoricalAnalysisService $analysisService Serviço de análise histórica
     */
    public function __construct(
        protected Twig $twig,
        private readonly HistoricalAnalysisService $analysisService,
    ) {}

    /**
     * Dashboard principal de análise histórica
     *
     * @return Response
     */
    public function index(): Response
    {
        try {
            // Simular tenant (em produção, pegar do contexto do usuário)
            $tenant = $this->getTenantFromSession();

            // Gerar dashboard com dados dos últimos 30 dias
            $dashboardResult = $this->analysisService->generateDashboard( $tenant );

            $data = [ 
                'dashboard' => $dashboardResult->isSuccess() ? $dashboardResult->getData() : [],
                'error'     => $dashboardResult->isSuccess() ? null : $dashboardResult->message
            ];

            return new Response(
                $this->twig->env->render( 'pages/admin/analysis/dashboard.twig', $data ),
            );
        } catch ( Exception $e ) {
            return new Response(
                $this->twig->env->render( 'pages/admin/analysis/dashboard.twig', [ 
                    'error' => 'Erro ao carregar dashboard: ' . $e->getMessage()
                ] ),
            );
        }
    }

    /**
     * Relatório de performance por período
     *
     * @return Response
     */
    public function performanceReport(): Response
    {
        try {
            $tenant = $this->getTenantFromSession();

            // Parâmetros do relatório (últimos 7 dias por padrão)
            $startDate = new DateTimeImmutable( '-7 days' );
            $endDate   = new DateTimeImmutable();

            $reportResult = $this->analysisService->generatePerformanceReport(
                $tenant,
                $startDate,
                $endDate,
            );

            $data = [ 
                'report' => $reportResult->isSuccess() ? $reportResult->getData() : [],
                'period' => [ 
                    'start' => $startDate->format( 'd/m/Y' ),
                    'end'   => $endDate->format( 'd/m/Y' )
                ],
                'error'  => $reportResult->isSuccess() ? null : $reportResult->message
            ];

            return new Response(
                $this->twig->env->render( 'pages/admin/analysis/performance-report.twig', $data ),
            );
        } catch ( Exception $e ) {
            return new Response(
                $this->twig->env->render( 'pages/admin/analysis/performance-report.twig', [ 
                    'error' => 'Erro ao gerar relatório: ' . $e->getMessage()
                ] ),
            );
        }
    }

    /**
     * Análise de tendências
     *
     * @return Response
     */
    public function trends(): Response
    {
        try {
            $tenant = $this->getTenantFromSession();

            // Análise dos últimos 30 dias
            $startDate = new DateTimeImmutable( '-30 days' );
            $endDate   = new DateTimeImmutable();

            $trendsResult = $this->analysisService->analyzeTrendsOverTime(
                $tenant,
                $startDate,
                $endDate,
                'day',
            );

            $data = [ 
                'trends' => $trendsResult->isSuccess() ? $trendsResult->getData() : [],
                'period' => [ 
                    'start' => $startDate->format( 'd/m/Y' ),
                    'end'   => $endDate->format( 'd/m/Y' )
                ],
                'error'  => $trendsResult->isSuccess() ? null : $trendsResult->message
            ];

            return new Response(
                $this->twig->env->render( 'pages/admin/analysis/trends.twig', $data ),
            );
        } catch ( Exception $e ) {
            return new Response(
                $this->twig->env->render( 'pages/admin/analysis/trends.twig', [ 
                    'error' => 'Erro ao analisar tendências: ' . $e->getMessage()
                ] ),
            );
        }
    }

    /**
     * Identificação de gargalos
     *
     * @return Response
     */
    public function bottlenecks(): Response
    {
        try {
            $tenant = $this->getTenantFromSession();

            // Análise dos últimos 7 dias
            $startDate = new DateTimeImmutable( '-7 days' );
            $endDate   = new DateTimeImmutable();

            $bottlenecksResult = $this->analysisService->identifyBottlenecks(
                $tenant,
                $startDate,
                $endDate,
            );

            $data = [ 
                'bottlenecks' => $bottlenecksResult->isSuccess() ? $bottlenecksResult->getData() : [],
                'period'      => [ 
                    'start' => $startDate->format( 'd/m/Y' ),
                    'end'   => $endDate->format( 'd/m/Y' )
                ],
                'error'       => $bottlenecksResult->isSuccess() ? null : $bottlenecksResult->message
            ];

            return new Response(
                $this->twig->env->render( 'pages/admin/analysis/bottlenecks.twig', $data ),
            );
        } catch ( Exception $e ) {
            return new Response(
                $this->twig->env->render( 'pages/admin/analysis/bottlenecks.twig', [ 
                    'error' => 'Erro ao identificar gargalos: ' . $e->getMessage()
                ] ),
            );
        }
    }

    /**
     * API: Métricas em tempo real
     *
     * @return Response
     */
    public function apiMetrics(): Response
    {
        try {
            $tenant = $this->getTenantFromSession();

            $dashboardResult = $this->analysisService->generateDashboard( $tenant );

            if ( $dashboardResult->isSuccess() ) {
                return new Response(
                    json_encode( $dashboardResult->getData() ),
                    200,
                    [ 'Content-Type' => 'application/json' ],
                );
            } else {
                return new Response(
                    json_encode( [ 'error' => $dashboardResult->message ] ),
                    500,
                    [ 'Content-Type' => 'application/json' ],
                );
            }
        } catch ( Exception $e ) {
            return new Response(
                json_encode( [ 'error' => $e->getMessage() ] ),
                500,
                [ 'Content-Type' => 'application/json' ],
            );
        }
    }

    /**
     * API: Relatório de performance customizado
     *
     * @return Response
     */
    public function apiPerformanceReport(): Response
    {
        try {
            $tenant = $this->getTenantFromSession();

            // Parâmetros da requisição
            $startDate = isset( $_GET[ 'start' ] )
                ? new DateTimeImmutable( $_GET[ 'start' ] )
                : new DateTimeImmutable( '-7 days' );

            $endDate = isset( $_GET[ 'end' ] )
                ? new DateTimeImmutable( $_GET[ 'end' ] )
                : new DateTimeImmutable();

            $filters = $_GET[ 'filters' ] ?? [];

            $reportResult = $this->analysisService->generatePerformanceReport(
                $tenant,
                $startDate,
                $endDate,
                $filters,
            );

            if ( $reportResult->isSuccess() ) {
                return new Response(
                    json_encode( $reportResult->getData() ),
                    200,
                    [ 'Content-Type' => 'application/json' ],
                );
            } else {
                return new Response(
                    json_encode( [ 'error' => $reportResult->message ] ),
                    500,
                    [ 'Content-Type' => 'application/json' ],
                );
            }
        } catch ( Exception $e ) {
            return new Response(
                json_encode( [ 'error' => $e->getMessage() ] ),
                500,
                [ 'Content-Type' => 'application/json' ],
            );
        }
    }

    /**
     * Obtém tenant da sessão (método temporário)
     *
     * @return TenantEntity
     */
    private function getTenantFromSession(): TenantEntity
    {
        // TODO: Implementar lógica real para obter tenant da sessão
        // Por enquanto, criar um tenant mock para testes
        $tenant = new TenantEntity('Mock Tenant');
        // Simular ID do tenant (em produção, pegar da sessão)
        $reflection = new \ReflectionClass( $tenant );
        $idProperty = $reflection->getProperty( 'id' );
        $idProperty->setAccessible( true );
        $idProperty->setValue( $tenant, 1 );

        return $tenant;
    }

}
