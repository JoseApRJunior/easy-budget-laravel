<?php

namespace app\controllers\admin;

use app\controllers\AbstractController;
use app\database\entitiesORM\AlertSettingsEntity;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\AlertGenerationService;
use app\database\servicesORM\AlertSettingsService;
use app\database\servicesORM\MiddlewareMetricsService;
use app\request\AlertSettingsFormRequest;
use core\library\Response;
use core\library\Session;
use core\library\Twig;
use DateTime;
use http\Redirect;
use http\Request;
use Throwable;

class AlertController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        private MiddlewareMetricsService $metricsService,
        private AlertSettingsService $alertSettingsService,
        private AlertGenerationService $alertGenerationService,
        Request $request,
        ActivityService $activityService,
    ) {
        parent::__construct( $request, $activityService );
    }

    public function index(): Response
    {
        $endDate   = new DateTime();
        $startDate = ( clone $endDate )->modify( '-24 hours' );

        $result = $this->metricsService->getPerformanceMetrics( '', $startDate, $endDate );

        if ( !$result->isSuccess() ) {
            return $this->errorResponse( $result->getMessage() );
        }

        $rawData      = $result->getData();
        $settings     = $this->getAlertSettings();
        $alerts       = $this->alertGenerationService->generateAlertsFromMetrics( $rawData, $settings );
        $systemStatus = $this->alertGenerationService->calculateSystemStatus( $rawData, $settings );
        $aiInsights   = $this->alertGenerationService->generateAIInsights( $rawData );

        $data = [ 
            'activeAlerts' => $alerts[ 'active' ],
            'alertStats'   => $alerts[ 'stats' ],
            'aiInsights'   => $aiInsights,
            'systemStatus' => $systemStatus,
            'pageTitle'    => 'Sistema de Alertas'
        ];

        return new Response(
            $this->twig->env->render( 'pages/admin/alerts/dashboard.twig', $data ),
        );
    }

    public function getAlertsApi(): Response
    {
        return new Response(
            json_encode( [ 'success' => true, 'alerts' => [], 'stats' => [] ] ),
            200,
            [ 'Content-Type' => 'application/json' ],
        );
    }

    public function resolveAlert(): Response
    {
        return new Response(
            json_encode( [ 'success' => true, 'message' => 'Alerta resolvido' ] ),
            200,
            [ 'Content-Type' => 'application/json' ],
        );
    }

    public function checkNow(): Response
    {
        return new Response(
            json_encode( [ 'success' => true, 'message' => 'Verificação executada' ] ),
            200,
            [ 'Content-Type' => 'application/json' ],
        );
    }

    public function settings(): Response
    {
        if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
            return $this->saveSettings();
        }

        $settings = $this->getAlertSettings();

        return new Response(
            $this->twig->env->render( 'pages/admin/alerts/settings.twig', [ 
                'settings'  => $settings,
                'pageTitle' => 'Configurações de Alertas'
            ] ),
        );
    }

    public function history(): Response
    {
        return new Response(
            $this->twig->env->render( 'pages/admin/alerts/history.twig', [ 
                'pageTitle' => 'Histórico de Alertas'
            ] ),
        );
    }

    private function getAlertSettings(): array
    {
        $result = $this->alertSettingsService->getSettings();

        if ( $result->isSuccess() ) {
            return $result->getData();
        }

        return $this->alertSettingsService->getDefaultSettings();
    }

    private function saveSettings(): Response
    {
        try {
            // Obter todos os dados da requisição para depuração
            $allData = $this->request->all();

            // Validar os dados do formulário
            $validated = AlertSettingsFormRequest::validate( $this->request );

            // Se os dados não forem válidos, redirecionar com erro
            if ( !$validated ) {
                // Verificar se há mensagens de erro na sessão
                $errors       = $_SESSION[ 'flash_messages' ] ?? [];
                $errorMessage = 'Dados inválidos. Verifique os campos e tente novamente.';

                if ( !empty( $errors ) ) {
                    $errorMessage = implode( ' ', $errors );
                }

                return Redirect::redirect( '/admin/alerts/settings' )
                    ->withMessage( 'error', $errorMessage );
            }

            $input = $this->request->all();

            // Processar e sanitizar dados
            $settings = [ 
                'thresholds'    => [ 
                    'critical_success_rate'  => (int) $input[ 'critical_success_rate' ],
                    'warning_success_rate'   => (int) $input[ 'warning_success_rate' ],
                    'critical_response_time' => (int) $input[ 'critical_response_time' ],
                    'warning_response_time'  => (int) $input[ 'warning_response_time' ],
                    'max_memory_mb'          => (int) ( $input[ 'max_memory_mb' ] ?? 512 ),
                    'max_cpu_percent'        => (int) ( $input[ 'max_cpu_percent' ] ?? 80 )
                ],
                'notifications' => [ 
                    'email_enabled'   => isset( $input[ 'email_enabled' ] ),
                    'email_addresses' => trim( $input[ 'email_addresses' ] ?? '' ),
                    'webhook_enabled' => isset( $input[ 'webhook_enabled' ] ),
                    'webhook_url'     => trim( $input[ 'webhook_url' ] ?? '' ),
                    'slack_enabled'   => isset( $input[ 'slack_enabled' ] ),
                    'slack_webhook'   => trim( $input[ 'slack_webhook' ] ?? '' )
                ],
                'monitoring'    => [ 
                    'check_interval'      => (int) $input[ 'check_interval' ],
                    'auto_resolve'        => isset( $input[ 'auto_resolve' ] ),
                    'min_severity'        => $input[ 'min_severity' ] ?? 'WARNING',
                    'enabled_middlewares' => $input[ 'enabled_middlewares' ] ?? []
                ],
                'interface'     => [ 
                    'auto_refresh' => (int) ( $input[ 'auto_refresh' ] ?? 30 ),
                    'theme'        => $input[ 'theme' ] ?? 'light',
                    'timezone'     => $input[ 'timezone' ] ?? 'America/Sao_Paulo'
                ]
            ];

            // Salvar no banco de dados
            $result = $this->alertSettingsService->saveSettings( $settings );

            if ( !$result->isSuccess() ) {
                return Redirect::redirect( '/admin/alerts/settings' )
                    ->withMessage( 'error', $result->getMessage() ?? 'Erro ao salvar configurações no banco de dados.' );
            }

            /** @var AlertSettingsEntity $entity */
            $entity = $result->getData();

            // Log da atividade
            $this->activityLogger(
                $this->authenticated[ 'tenant_id' ],
                $this->authenticated[ 'user_id' ],
                'alert_settings_updated',
                'alert_settings',
                $entity->getId(),
                'Configurações de alertas atualizadas',
                [ 'settings' => $settings ],
            );

            return Redirect::redirect( '/admin/alerts/settings' )
                ->withMessage( 'success', 'Configurações salvas com sucesso!' );

        } catch ( Throwable $e ) {
            Session::flash( 'error', 'Falha ao salvar configurações. Tente novamente mais tarde.' );
            return Redirect::redirect( '/admin/alerts/settings' );
        }
    }

}
