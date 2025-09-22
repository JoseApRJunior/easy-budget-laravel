<?php
declare(strict_types=1);

namespace app\controllers;

use app\database\entitiesORM\BudgetEntity;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\BudgetService;
use app\database\servicesORM\SharedService;
use app\request\BudgetChangeStatusFormRequest;
use app\request\BudgetChooseStatusFormRequest;
use app\request\BudgetFormRequest;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Sanitize;
use core\library\Session;
use core\library\Twig;
use DateTime;
use http\Redirect;
use http\Request;
use Throwable;

class BudgetController extends AbstractController
{
    // TODO: Implementar lógica de desconto para serviços cancelados/parciais em show/PDF
    public function __construct(
        protected Twig $twig,
        private BudgetService $budgetService,
        Sanitize $sanitize,
        ActivityService $activityService,
        private SharedService $sharedService,
        Request $request,
    ) {
        parent::__construct( $request, $activityService, $sanitize );
    }

    /**
     * Summary of index
     * @return Response
     */
    public function index(): Response
    {
        return new Response( $this->twig->env->render( 'pages/budget/index.twig' ) );
    }

    public function create(): Response
    {

        $customers = $this->budgetService->getAllCustomersForBudget( $this->authenticated->tenant_id );

        return new Response( $this->twig->env->render( 'pages/budget/create.twig', [ 'customers' => $customers ] ) );
    }

    public function store(): Response
    {
        try {
            // Validar os dados do formulário de criação de orçamento
            $validated = BudgetFormRequest::validate( $this->request );

            // Se os dados não forem válidos, redirecionar para a página de criação de orcamento e mostrar a mensagem de erro
            if ( !$validated ) {
                return Redirect::redirect( '/provider/budgets/create' )->withMessage( 'error', 'Erro ao cadastar o orçamento.' );
            }

            // Obter os dados do formulário de criação de usuário
            $data = $this->request->all();

            // Validar tenant_id se presente no input
            if ( isset( $data[ 'tenant_id' ] ) && (int) $data[ 'tenant_id' ] !== $this->authenticated->tenant_id ) {
                error_log( "SECURITY VIOLATION: Tentativa de criação com tenant_id inválido: {$data[ 'tenant_id' ]} (esperado: {$this->authenticated->tenant_id}). IP: " . ( $_SERVER[ 'REMOTE_ADDR' ] ?? 'unknown' ) );
                return Redirect::redirect( '/provider/budgets/create' )->withMessage( 'error', 'Erro de validação de segurança.' );
            }

            // Popula BudgetEntity com os dados do formulário

            $properties                = getConstructorProperties( BudgetEntity::class);
            $properties[ 'tenant_id' ] = $this->authenticated->tenant_id;
            $properties[ 'total' ]     = 0.00;

            $result = $this->budgetService->createBudget( $data );

            if ( !$result->isSuccess() ) {
                return Redirect::redirect( '/provider/budgets/create' )->withMessage( 'error', $result->getMessage() );
            }

            $entity = $result->getData();

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'budget_created',
                'budget',
                $entity->getId(),
                "Orçamento {$entity->getCode()} criado para {$data[ 'customer_name' ]}",
                [ 'entity' => $entity->jsonSerialize() ],
            );

            // Redirecionar para a página de orçamentos e mostrar a mensagem de sucesso
            return Redirect::redirect( '/provider/budgets' )->withMessage( 'success', 'Orçamento criado com sucesso!' );

            // Se houver redirecionar para a página inicial e mostrar a mensagem de erro
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao criar o orçamento, tente novamente mais tarde ou entre em contato com suporte!" );

            return Redirect::redirect( '/provider/budgets/create' );
        }
    }

    public function update( string $code ): Response
    {
        try {
            $params = $this->autoSanitizeForEntity( [ 'code' => $code ], BudgetEntity::class);
            $code   = $params[ 'code' ];

            $result = $this->budgetService->getBudgetUpdateData( $code );  // Placeholder; implement in service

            if ( !$result->isSuccess() ) {
                return Redirect::redirect( '/provider/budgets' )->withMessage( 'error', $result->message ?? 'Orçamento não encontrado.' );
            }
            $budget   = $result->data[ 'budget' ];
            $services = $result->data[ 'services' ];

            return new Response( $this->twig->env->render(
                'pages/budget/update.twig',
                [ 'budget' => $budget, 'services' => $services ],
            ) );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao carregar orçamento para edição.' );
            return Redirect::redirect( '/provider/budgets' );
        }
    }

    public function update_store(): Response
    {
        $data = [];
        try {
            // Validar dados do formulário
            $validated = BudgetFormRequest::validate( $this->request );

            if ( !$validated ) {
                return Redirect::redirect( '/provider/budgets' )->withMessage( 'error', 'Dados inválidos. Verifique os campos.' );
            }

            // Obter os dados do formulário
            $data = $this->request->all();

            // Validar tenant_id se presente no input
            if ( isset( $data[ 'tenant_id' ] ) && (int) $data[ 'tenant_id' ] !== $this->authenticated->tenant_id ) {
                error_log( "SECURITY VIOLATION: Tentativa de atualização com tenant_id inválido: {$data[ 'tenant_id' ]} (esperado: {$this->authenticated->tenant_id}). IP: " . ( $_SERVER[ 'REMOTE_ADDR' ] ?? 'unknown' ) );
                return Redirect::redirect( '/provider/budgets/update/' . $data[ 'code' ] )
                    ->withMessage( 'error', 'Erro de validação de segurança.' );
            }

            // Fetch original data for log
            $originalResult = $this->budgetService->getByCode( $data[ 'code' ] );  // Assumir método implementado no service
            $originalData   = $originalResult->isSuccess() ? $originalResult->data->jsonSerialize() : [];

            // Buscar os dados do orçamento
            $result = $this->budgetService->updateBudget( $data );

            if ( !$result->isSuccess() ) {
                return Redirect::redirect( '/provider/budgets/update/' . $data[ 'code' ] )
                    ->withMessage( 'error', $result->message ?? 'Erro ao atualizar orçamento.' );
            }

            $budgetEntity = $result->data;

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'budget_updated',
                'budget',
                $data[ 'code' ],
                "O orçamento {$data[ 'code' ]} foi atualizado.",
                [ 
                    'before' => $originalData,
                    'after'  => $budgetEntity->jsonSerialize(),
                ],
            );

            // Se tudo ocorreu bem, redirecionar para a página de detalhes do orçamento e mostrar a mensagem de sucesso
            return Redirect::redirect( '/provider/budgets/show/' . $data[ 'code' ] )
                ->withMessage( 'success', 'Orçamento atualizado com sucesso!' );

        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );

            // Obter o ID do orçamento da requisição para redirecionar corretamente
            $code        = $data[ 'code' ];
            $redirectUrl = $code ? "/provider/budgets/update/{$code}" : "/provider/budgets";

            Session::flash( 'error', "Falha ao atualizar o orçamento, tente novamente mais tarde ou entre em contato com suporte!" );

            return Redirect::redirect( $redirectUrl );
        }
    }

    public function show( string $code ): Response
    {
        try {
            $params = $this->autoSanitizeForEntity( [ 'code' => $code ], BudgetEntity::class);
            $code   = $params[ 'code' ];

            $result = $this->budgetService->getBudgetShowData( $code );  // Placeholder; implement in service

            if ( !$result->isSuccess() ) {
                return Redirect::redirect( '/provider/budgets' )->withMessage( 'error', $result->message ?? 'Orçamento não encontrado.' );
            }

            $showData = $result->data;

            // Retornar a view do orçamento com os dados e services
            return new Response( $this->twig->env->render( 'pages/budget/show.twig', $showData ) );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao carregar detalhes do orçamento.' );
            return Redirect::redirect( '/provider/budgets' );
        }
    }

    public function change_status(): Response
    {
        try {
            // TODO: Implementar lógica para gerar fatura completa com descontos se necessário

            // Validar dados do formulário
            $validated = BudgetChangeStatusFormRequest::validate( $this->request );

            if ( !$validated ) {
                return Redirect::redirect( '/provider/budgets' )->withMessage( 'error', 'Dados inválidos para mudança de status.' );
            }

            // Obter os dados do formulário
            $data = $this->request->all();

            // Validar tenant_id se presente no input
            if ( isset( $data[ 'tenant_id' ] ) && (int) $data[ 'tenant_id' ] !== $this->authenticated->tenant_id ) {
                error_log( "SECURITY VIOLATION: Tentativa de mudança de status com tenant_id inválido: {$data[ 'tenant_id' ]} (esperado: {$this->authenticated->tenant_id}). IP: " . ( $_SERVER[ 'REMOTE_ADDR' ] ?? 'unknown' ) );
                return Redirect::redirect( '/provider/budgets' )->withMessage( 'error', 'Erro de validação de segurança.' );
            }

            $response = $this->budgetService->handleStatusChange( $data, $this->authenticated );  // Placeholder; assume ServiceResult

            if ( !$response->isSuccess() ) {
                return Redirect::redirect( '/provider/budgets/show/' . $data[ 'budget_code' ] )->withMessage( 'error', $response->message ?? 'Erro no status.' );
            }

            $serviceStatusText    = '';
            $newServiceStatusName = $response->data[ 'new_status_service_name' ] ?? '';

            if ( !empty( $newServiceStatusName ) ) {
                if ( is_array( $newServiceStatusName ) ) {
                    $unique_statuses   = array_unique( $newServiceStatusName );
                    $serviceStatusText = " e serviços para " . implode( ', ', $unique_statuses );
                } else {
                    $serviceStatusText = " e serviço para " . $newServiceStatusName;
                }
            }

            // Registrar a mudança de status no histórico de atividades
            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'budget_status_changed',
                'budget',
                $data[ 'budget_id' ],
                "Status do orçamento {$data[ 'budget_code' ]} alterado para {$response->data[ 'new_status_name' ]}{$serviceStatusText}.",
                [ 'data' => $response->data ],
            );

            return Redirect::redirect( '/provider/budgets/show/' . $data[ 'budget_code' ] )->withMessage( 'success', 'Status do orçamento atualizado com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao alterar o status do orçamento, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/provider/budgets' );
        }
    }

    public function choose_budget_status( string $code, string $token ): Response
    {
        try {
            // Sanitizar params
            $params = $this->autoSanitizeForEntity( [ 'code' => $code, 'token' => $token ], BudgetEntity::class);

            $code  = $params[ 'code' ];
            $token = $params[ 'token' ];

            // Buscar o token e verificar se existe
            $response              = $this->sharedService->validateUserConfirmationToken( $token );
            $userConfirmationToken = $response->data ?? null;

            if ( $response->status === 'error' ) {
                if ( $response->getData()->condition === 'expired' ) {
                    $response = $this->budgetService->handleTokenUpdateBudget( $code, $userConfirmationToken, $this->authenticated );
                    if ( $response->status === 'success' ) {
                        return Redirect::redirect( '/' )
                            ->withMessage( 'success', 'Token expirado. Um novo link foi enviado para seu email.' );
                    } else {
                        return Redirect::redirect( '/' )->withMessage( 'error', $response->message ?? 'Erro no token.' );
                    }
                } else {
                    return Redirect::redirect( '/' )->withMessage( 'error', $response->message ?? 'Token inválido.' );
                }
            }

            if ( $this->authenticated === null ) {
                $this->authenticated = $this->sharedService->getProviderByToken( $userConfirmationToken );
            }

            $tenant_id = $this->authenticated->tenant_id;

            // Use service for budget data (placeholder; implement getBudgetByCodeWithCustomerData in BudgetService)
            $budgetResult = $this->budgetService->getBudgetByCodeWithCustomerData( $code, $tenant_id );
            if ( !$budgetResult->isSuccess() ) {
                return Redirect::redirect( '/' )->withMessage( 'error', 'Orçamento não encontrado.' );
            }
            $budget = $budgetResult->data;

            // Use service for services (placeholder; implement getAllServicesFullByBudgetId in BudgetService or ServiceService)
            $servicesResult = $this->budgetService->getAllServicesFullByBudgetId( $budget->id, $tenant_id );
            if ( !$servicesResult->isSuccess() ) {
                return Redirect::redirect( '/' )->withMessage( 'error', 'Serviços não encontrados.' );
            }
            $services = $servicesResult->data;

            $service_items    = [];
            $latest_schedules = [];

            $all_services_completed = true;
            foreach ( $services as $service ) {
                // Placeholder methods; implement in services
                $serviceItemsResult                = $this->budgetService->getAllServiceItemsByServiceId( $service[ 'id' ], $tenant_id );
                $service_items[ $service[ 'id' ] ] = $serviceItemsResult->isSuccess() ? $serviceItemsResult->data : [];

                $scheduleResult                       = $this->budgetService->getLatestScheduleByServiceId( $service[ 'id' ], $tenant_id );
                $latest_schedules[ $service[ 'id' ] ] = $scheduleResult->isSuccess() ? $scheduleResult->data : null;

                if ( $all_services_completed ) {
                    $all_services_completed =
                        in_array( $service[ 'status_slug' ], [ 
                            'COMPLETED',
                            'PARTIAL',
                            'CANCELLED',
                            'NOT_PERFORMED',
                            'EXPIRED',
                        ] );
                }
            }

            // Retornar a view do orçamento com os dados e services
            return new Response( $this->twig->env->render( 'pages/budget/choose_budget_status.twig', [ 
                'budget'                 => (array) $budget,
                'services'               => $services,
                'service_items'          => $service_items,
                'latest_schedules'       => $latest_schedules,
                'all_services_completed' => $all_services_completed,
                'token'                  => $token,
            ] ) );

        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao carregar status do orçamento.' );
            return Redirect::redirect( '/' );
        }
    }

    public function choose_budget_status_store(): Response
    {
        try {
            // Validar dados do formulário
            $validated = BudgetChooseStatusFormRequest::validate( $this->request );

            if ( !$validated ) {
                return Redirect::redirect( '/budgets' )->withMessage( 'error', 'Dados inválidos para status.' );
            }

            // Obter os dados do formulário
            $data = $this->request->all();

            $response = $this->sharedService->validateUserConfirmationToken( $data[ 'token' ] );
            if ( !$response->isSuccess() ) {
                return Redirect::redirect( '/' )->withMessage( 'error', $response->message ?? 'Token inválido.' );
            }
            $userConfirmationToken = $response->data;

            $response = $this->budgetService->handleStatusChange( $data, $userConfirmationToken );

            if ( !$response->isSuccess() ) {
                return Redirect::redirect( '/budgets/choose-budget-status/code/' . $data[ 'budget_code' ] . '/token/' . $data[ 'token' ] )->withMessage( 'error', $response->message ?? 'Erro no status.' );
            }

            $serviceStatusText    = '';
            $newServiceStatusName = $response->data[ 'new_status_service_name' ] ?? '';

            if ( !empty( $newServiceStatusName ) ) {
                if ( is_array( $newServiceStatusName ) ) {
                    $unique_statuses   = array_unique( $newServiceStatusName );
                    $serviceStatusText = " e serviços para (" . implode( ', ', $unique_statuses ) . ")";
                } else {
                    $serviceStatusText = " e serviço para " . $newServiceStatusName;
                }
            }

            // Registrar a mudança de status no histórico de atividades
            $this->activityLogger(
                $userConfirmationToken->tenant_id,
                $userConfirmationToken->user_id,
                'budget_status_changed',
                'budget',
                $data[ 'budget_id' ],
                "Status do orçamento {$data[ 'budget_code' ]} alterado para {$response->data[ 'new_status_name' ]}{$serviceStatusText}.",
                [ 'data' => $response->data ],
            );

            return Redirect::redirect( '/budgets/choose-budget-status/code/' . $data[ 'budget_code' ] . '/token/' . $data[ 'token' ] )->withMessage( 'success', 'Status do orçamento atualizado com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao alterar o status do orçamento, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/' );
        }
    }

    public function print( string $code, ?string $token = null ): Response
    {
        try {
            $params = $this->autoSanitizeForEntity( [ 'code' => $code, 'token' => $token ], BudgetEntity::class);
            $code   = $params[ 'code' ];
            $token  = $params[ 'token' ];

            $result = $this->budgetService->getBudgetPrintData( $code, $token );  // Placeholder; implement in service

            if ( !$result->isSuccess() ) {
                return Redirect::redirect( '/' )->withMessage( 'error', $result->message ?? 'Erro ao gerar PDF.' );
            }

            $printData = $result->data;

            $response = $printData[ 'pdf' ];

            $safeFilename = preg_replace( '/[^a-zA-Z0-9_.-]/', '_', $response[ 'fileName' ] );

            if ( empty( $response[ 'content' ] ) ) {
                return Redirect::redirect( '/' )->withMessage( 'error', 'Erro ao gerar o PDF.' );
            }

            return new Response(
                $response[ 'content' ],
                200,
                [ 
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => "inline; filename=\"{$safeFilename}\"",
                ],
            );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao imprimir orçamento.' );
            return Redirect::redirect( '/' );
        }
    }

    public function delete_store( string $code ): Response
    {
        try {
            $params = $this->autoSanitizeForEntity( [ 'code' => $code ], BudgetEntity::class);
            $code   = $params[ 'code' ];

            $result = $this->budgetService->deleteBudget( $code );

            if ( !$result->isSuccess() ) {
                return Redirect::redirect( '/provider/budgets' )
                    ->withMessage( 'error', $result->message ?? 'Erro ao deletar.' );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'budget_deleted',
                'budget',
                $code,
                "Orçamento {$code} deletado.",
                [ 'code' => $code ],
            );

            return Redirect::redirect( '/provider/budgets' )
                ->withMessage( 'success', 'Orçamento deletado com sucesso!' );

        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao deletar orçamento, tente novamente mais tarde ou entre em contato com suporte!" );

            return Redirect::redirect( '/provider/budgets' );
        }
    }

    /**
     * Log de atividade (herdado ou via $activityService)
     */
    // Removido; usar $this->activityLogger() herdado do AbstractController

}
