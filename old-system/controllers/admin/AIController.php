<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\controllers\AbstractController;
use app\database\entitiesORM\BudgetEntity;
use app\database\entitiesORM\UserEntity;
use app\database\servicesORM\AnomalyDetectionService;
use app\services\ai\MLInsightsService;
use app\services\ai\PredictiveAnalysisService;
use app\services\ai\RecommendationService;
use core\library\Response;
use core\library\Twig;
use Doctrine\ORM\EntityManagerInterface;
use http\Request;

/**
 * Controlador principal para funcionalidades de Inteligência Artificial
 *
 * Este controlador gerencia todas as interações relacionadas à IA no sistema,
 * incluindo análises preditivas, insights de ML e recomendações para usuários.
 */
class AIController extends AbstractController
{
    private MLInsightsService       $mlService;
    private RecommendationService   $recommendationService;
    private AnomalyDetectionService $anomalyService;

    public function __construct(
        protected Twig $twig,
        protected PredictiveAnalysisService $predictiveService,
        Request $request,
        MLInsightsService $mlService,
        RecommendationService $recommendationService,
        AnomalyDetectionService $anomalyService,
        ?EntityManagerInterface $entityManager = null,
    ) {
        parent::__construct( $request, null, null, null, $entityManager );
        $this->mlService             = $mlService;
        $this->recommendationService = $recommendationService;
        $this->anomalyService        = $anomalyService;
        error_log( "AIController construído com sucesso" );
    }

    /**
     * Dashboard principal de IA para administradores
     *
     * Apresenta uma visão consolidada de todas as análises de IA, incluindo:
     * - Previsões de churn
     * - Previsões financeiras
     * - Alertas de risco
     * - Recomendações estratégicas
     */
    public function dashboard(): Response
    {
        try {
            error_log( "Iniciando dashboard de IA" );

            // Obtém todos os dados do dashboard através do MLInsightsService
            $dashboardData = [ 
                'default_risk_alerts' => $this->predictiveService->getDefaultRiskAlerts(),
                'insights'            => $this->mlService->getExecutiveDashboard()
            ];

            error_log( "Dados do dashboard: " . json_encode( $dashboardData ) );

            // Renderiza o dashboard
            return new Response(
                $this->twig->env->render( 'pages/admin/ai/dashboard.twig', $dashboardData ),
                200,
            );
        } catch ( \Throwable $e ) {
            // Log do erro
            error_log( "Erro no dashboard de IA: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() );

            // Retorna erro amigável
            return new Response(
                $this->twig->env->render( 'pages/admin/ai/error.twig', [ 
                    'error' => 'Não foi possível carregar o dashboard de IA. Por favor, tente novamente.'
                ] ),
                500,
            );
        }
    }

    public function dataset(): Response
    {
        try {
            error_log( "Iniciando dataset de IA" );

            // Obtém todos os dados do dashboard através dos serviços de IA
            $defaultRiskAlerts = $this->predictiveService->getDefaultRiskAlerts();
            error_log( "Dados de defaultRiskAlerts (antes): " . json_encode( $defaultRiskAlerts ) );

            $executiveDashboard = $this->mlService->getExecutiveDashboard();
            error_log( "Dados de executiveDashboard (antes): " . json_encode( $executiveDashboard ) );

            // Garantir que os dados sejam arrays
            if ( !is_array( $defaultRiskAlerts ) ) {
                error_log( "defaultRiskAlerts não é um array. Tipo: " . gettype( $defaultRiskAlerts ) );
                if ( is_object( $defaultRiskAlerts ) && method_exists( $defaultRiskAlerts, 'toArray' ) ) {
                    $defaultRiskAlerts = $defaultRiskAlerts->toArray();
                } elseif ( is_object( $defaultRiskAlerts ) && method_exists( $defaultRiskAlerts, 'jsonSerialize' ) ) {
                    $defaultRiskAlerts = $defaultRiskAlerts->jsonSerialize();
                } else {
                    $defaultRiskAlerts = [];
                }
            }

            if ( !is_array( $executiveDashboard ) ) {
                error_log( "executiveDashboard não é um array. Tipo: " . gettype( $executiveDashboard ) );
                if ( is_object( $executiveDashboard ) && method_exists( $executiveDashboard, 'toArray' ) ) {
                    $executiveDashboard = $executiveDashboard->toArray();
                } elseif ( is_object( $executiveDashboard ) && method_exists( $executiveDashboard, 'jsonSerialize' ) ) {
                    $executiveDashboard = $executiveDashboard->jsonSerialize();
                } else {
                    $executiveDashboard = [];
                }
            }

            $dashboardData = [ 
                'default_risk_alerts' => $defaultRiskAlerts,
                'insights'            => $executiveDashboard
            ];

            error_log( "Dados do dataset após conversão: " . json_encode( $dashboardData ) );

            // Retorna os dados como JSON
            $json = json_encode( $dashboardData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
            if ( $json === false ) {
                error_log( "Erro ao codificar JSON: " . json_last_error_msg() );
                $json = json_encode( [ 
                    'success' => false,
                    'message' => 'Erro ao codificar dados do dashboard: ' . json_last_error_msg()
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
            }

            error_log( "JSON final: " . $json );

            return new Response(
                $json,
                200,
                [ 'Content-Type' => 'application/json; charset=utf-8' ],
            );
        } catch ( \Throwable $e ) {
            // Log do erro
            error_log( "Erro no dataset de IA: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() );

            // Retorna erro em formato JSON
            $errorResponse = [ 
                'success' => false,
                'message' => 'Não foi possível carregar os dados do dashboard de IA. Por favor, tente novamente. Erro: ' . $e->getMessage()
            ];

            $json = json_encode( $errorResponse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
            if ( $json === false ) {
                $json = '{"success":false,"message":"Erro crítico ao codificar resposta de erro"}';
            }

            return new Response(
                $json,
                500,
                [ 'Content-Type' => 'application/json; charset=utf-8' ],
            );
        }
    }

    /**
     * Gera insights detalhados para um usuário específico
     *
     * Combina análises de diferentes serviços de IA para fornecer uma visão
     * completa do comportamento e potencial do usuário.
     */
    public function userInsights( int $userId ): Response
    {
        try {
            error_log( "Iniciando userInsights para usuário ID: " . $userId );

            $user = $this->entityManager->find( UserEntity::class, $userId );
            if ( !$user ) {
                error_log( "Usuário não encontrado" );
                return $this->jsonResponse( [ 'message' => 'Usuário não encontrado' ], 404 );
            }

            // Coleta insights de múltiplas fontes
            $insights = [ 
                'ml_insights'     => $this->mlService->generateUserInsights( $user ),
                'predictions'     => $this->predictiveService->analyzeUser( $user ),
                'recommendations' => $this->recommendationService->getRecommendations( $user )
            ];

            error_log( "Insights gerados: " . json_encode( $insights ) );
            return $this->jsonResponse( [ 'data' => $insights ] );
        } catch ( \Throwable $e ) {
            error_log( "Erro em userInsights: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() );
            return $this->jsonResponse( [ 'message' => 'Erro ao gerar insights: ' . $e->getMessage() ], 500 );
        }
    }

    /**
     * Análise preditiva de orçamentos
     *
     * Realiza uma análise completa de um orçamento, incluindo:
     * - Probabilidade de aprovação
     * - Sugestões de otimização
     * - Análise de mercado relacionada
     */
    public function analyzeBudget( int $budgetId ): Response
    {
        try {
            error_log( "Iniciando analyzeBudget para orçamento ID: " . $budgetId );

            $budget = $this->entityManager->find( BudgetEntity::class, $budgetId );
            if ( !$budget ) {
                error_log( "Orçamento não encontrado" );
                return $this->jsonResponse( [ 'message' => 'Orçamento não encontrado' ], 404 );
            }

            // Análise completa do orçamento
            $analysis = [ 
                'probability'      => $this->mlService->analyzeBudgetProbability( $budget ),
                'optimizations'    => $this->recommendationService->suggestBudgetOptimizations( $budget ),
                'pricing_insights' => $this->recommendationService->getOptimalPricing( $budget )
            ];

            error_log( "Análise concluída: " . json_encode( $analysis ) );
            return $this->jsonResponse( [ 'data' => $analysis ] );
        } catch ( \Throwable $e ) {
            error_log( "Erro em analyzeBudget: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() );
            return $this->jsonResponse( [ 'message' => 'Erro na análise: ' . $e->getMessage() ], 500 );
        }
    }

    /**
     * Sistema de alertas inteligentes
     *
     * Fornece alertas proativos baseados em análises de IA,
     * incluindo riscos, oportunidades e anomalias detectadas.
     */
    public function getAlerts(): Response
    {
        try {
            error_log( "Iniciando getAlerts" );

            // Coleta alertas de diferentes fontes
            $alerts = [ 
                'proactive_alerts' => $this->mlService->getProactiveAlerts(),
                'risk_alerts'      => $this->predictiveService->getDefaultRiskAlerts()
            ];

            // Log para depuração
            error_log( "Alertas gerados: " . json_encode( $alerts ) );

            // Retorna os alertas diretamente
            return $this->jsonResponse( $alerts );
        } catch ( \Throwable $e ) {
            // Log detalhado do erro
            error_log( "Erro em getAlerts: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() );
            return $this->jsonResponse( [ 'message' => 'Erro ao buscar alertas: ' . $e->getMessage() ], 500 );
        }
    }

    /**
     * Métricas e análises de ROI
     *
     * Fornece métricas detalhadas sobre o impacto das decisões
     * baseadas em IA no negócio.
     */
    public function roiMetrics(): Response
    {
        try {
            error_log( "Iniciando roiMetrics" );

            // Usa os dados do dashboard que já incluem as métricas de ROI
            $metrics = [ 
                'insights' => $this->mlService->getExecutiveDashboard()
            ];

            error_log( "Métricas geradas: " . json_encode( $metrics ) );
            return $this->jsonResponse( [ 'data' => $metrics ] );
        } catch ( \Throwable $e ) {
            error_log( "Erro em roiMetrics: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() );
            return $this->jsonResponse( [ 'message' => 'Erro ao calcular métricas: ' . $e->getMessage() ], 500 );
        }
    }

    /**
     * Método de teste para verificar funcionamento da API
     */
    public function test(): Response
    {
        error_log( "Método test chamado" );
        return $this->jsonResponse( [ 'message' => 'API de IA funcionando corretamente' ] );
    }

    public function analyzeAllBudgets(): Response
    {
        try {
            $budgets  = $this->entityManager->getRepository( BudgetEntity::class)->findAll();
            $analysis = [];
            foreach ( $budgets as $budget ) {
                $analysis[] = $this->mlService->analyzeBudgetProbability( $budget );
            }
            return $this->jsonResponse( [ 'success' => true, 'message' => 'Análise de ' . count( $analysis ) . ' budgets concluída', 'analysis' => $analysis ] );
        } catch ( \Throwable $e ) {
            return $this->jsonResponse( [ 'success' => false, 'message' => $e->getMessage() ], 500 );
        }
    }

    public function generateRecommendations(): Response
    {
        try {
            $userRepo = $this->entityManager->getRepository( UserEntity::class);
            $user     = $userRepo->find( 1 ); // Assumindo tenant_id=1 para teste; ajustar conforme necessário

            if ( !$user ) {
                throw new \Exception( 'Usuário de teste (ID=1) não encontrado. Crie um usuário para testes.' );
            }
            $recommendations = $this->recommendationService->getRecommendations( $user );
            return $this->jsonResponse( [ 'success' => true, 'message' => 'Recomendações geradas', 'data' => $recommendations ] );
        } catch ( \Throwable $e ) {
            return $this->jsonResponse( [ 'success' => false, 'message' => $e->getMessage() ], 500 );
        }
    }

    public function runAnomalyDetection(): Response
    {
        try {
            $anomalies = $this->anomalyService->getActiveAlerts();
            return $this->jsonResponse( [ 'success' => true, 'anomalies' => count( $anomalies ) ] );
        } catch ( \Throwable $e ) {
            return $this->jsonResponse( [ 'success' => false, 'message' => $e->getMessage() ], 500 );
        }
    }

    /**
     * Helper para respostas JSON padronizadas
     */
    private function jsonResponse( array $data, int $status = 200 ): Response
    {
        error_log( "Iniciando jsonResponse com status: " . $status );

        $response = [ 'success' => $status === 200 ];
        $response = array_merge( $response, $data );

        // Garantir que a resposta seja um JSON válido
        $json = json_encode( $response );
        if ( $json === false ) {
            // Em caso de erro na codificação JSON, retornar um JSON de erro
            error_log( "Erro ao codificar JSON: " . json_last_error_msg() );
            $json = json_encode( [ 
                'success' => false,
                'message' => 'Erro ao codificar resposta JSON: ' . json_last_error_msg()
            ] );
        }

        error_log( "Resposta JSON: " . $json );

        return new Response(
            $json,
            $status,
            [ 'Content-Type' => 'application/json' ],
        );
    }

}