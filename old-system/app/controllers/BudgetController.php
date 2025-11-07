<?php

namespace app\controllers;

use app\database\entities\BudgetEntity;
use app\database\models\Budget;
use app\database\models\Customer;
use app\database\models\Schedule;
use app\database\models\Service;
use app\database\models\ServiceItem;
use app\database\services\ActivityService;
use app\database\services\BudgetService;
use app\database\services\SharedService;
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
    // TODO CRIAR LOGICA DE DESCONTO PARA SERVIÇOS CANCELADOS OU PACIAIS E EXIBIR NAS TELAS SHOW,PDF DE PROVIDER E CUSTOMER
    public function __construct(
        private Twig $twig,
        private Customer $customer,
        private Budget $budget,
        private Service $service,
        private ServiceItem $serviceItem,
        private BudgetService $budgetService,
        private Sanitize $sanitize,
        private SharedService $sharedService,
        private Schedule $schedule,
        Request $request,
    ) {
        parent::__construct( $request );
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

        $customers = $this->customer->getAllCustomers( $this->authenticated->tenant_id );

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

            // Popula BudgetEntity com os dados do formulário

            $properties                = getConstructorProperties( BudgetEntity::class);
            $properties[ 'tenant_id' ] = $this->authenticated->tenant_id;
            $properties[ 'total' ]     = 0.00;

            $last_code = $this->budget->getLastCode( $this->authenticated->tenant_id );
            $last_code = (float) ( substr( $last_code, -4 ) ) + 1;

            $properties[ 'code' ]               = 'ORC-' . date( 'Ymd' ) . str_pad( (string) $last_code, 4, '0', STR_PAD_LEFT );
            $properties[ 'budget_statuses_id' ] = 1;
            // popula model CommonDataEntity
            $entity = BudgetEntity::create( removeUnnecessaryIndexes(
                $properties,
                [ 'id', 'created_at', 'updated_at' ],
                $data,
            ) );

            // Criar novo orcamento
            $response = $this->budget->create( $entity );

            // Se não foi possível criar o novo orçamento, redirecionar para a página inicial e mostrar a mensagem de erro
            if ( $response[ 'status' ] === 'error' ) {
                return Redirect::redirect( '/provider/budgets/create' )->withMessage( 'error', "Falha ao cadastrar o orçamento, tente novamente mais tarde ou entre em contato com suporte!" );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'budget_created',
                'budget',
                $response[ 'data' ][ 'id' ],
                "Orçamento {$properties[ 'code' ]} criado para {$data[ 'customer_name' ]}",
                $response[ 'data' ],
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

    public function update( $code )
    {
        $code = $this->sanitize->sanitizeParamValue( $code, 'string' );

        $budget = $this->budget->getBudgetFullByCode( $code, $this->authenticated->tenant_id );
        if ( $budget instanceof EntityNotFound ) {
            return Redirect::redirect( '/provider/budgets' )->withMessage( 'error', 'Orçamento não encontrado.' );
        }
        $services = $this->service->getAllServiceFullByIdBudget( $budget->id, $this->authenticated->tenant_id );

        return new Response( $this->twig->env->render(
            'pages/budget/update.twig',
            [ 'budget' => $budget, 'services' => $services ],
        ) );
    }

    public function update_store(): Response
    {
        try {
            // Validar dados do formulário
            $validated = BudgetFormRequest::validate( $this->request );

            // Obter os dados do formulário
            $data = $this->request->all();

            // Se os dados não forem válidos, redirecionar para a página de atualização do orçamento e mostrar a mensagem de erro
            if ( !$validated ) {
                return Redirect::redirect( '/provider/budgets/update/' . $data[ 'code' ] )->withMessage( 'error', 'Erro ao atualizar orçamento' );
            }

            // Buscar os dados do orçamento
            $budget = $this->budget->getBudgetById( $data[ 'id' ], $this->authenticated->tenant_id );

            // Verificar se o orçamento existe
            if ( $budget instanceof EntityNotFound ) {
                return Redirect::redirect( '/provider/budgets' )->withMessage( 'error', 'Orçamento não encontrado.' );
            }

            // Converter o objeto para array
            $originalData = $budget->toArray();

            // Verificar se a data de vencimento é válida
            if ( isset( $data[ 'due_date' ] ) ) {
                $dueDate = new DateTime( $data[ 'due_date' ] );
                $today   = new DateTime( 'today' );

                if ( $dueDate < $today ) {
                    return Redirect::redirect( '/provider/budgets/update/' . $data[ 'code' ] )
                        ->withMessage( 'error', 'A data de vencimento não pode ser anterior à data atual.' );
                }
            }

            // Popula BudgetEntity com os dados do formulário
            $budgetEntity = BudgetEntity::create( removeUnnecessaryIndexes(
                $originalData,
                [ 'created_at', 'updated_at' ],
                $data,
            ) );

            // Verificar se os dados do formulário foram alterados
            if ( !compareObjects( $budget, $budgetEntity, [ 'created_at', 'updated_at' ] ) ) {
                // Atualizar BudgetEntity com os dados do formulário
                $response = $this->budget->update( $budgetEntity );

                // Se não foi possível atualizar o orçamento, redirecionar para a página de atualização e mostrar a mensagem de erro
                if ( $response[ 'status' ] === 'error' ) {
                    return Redirect::redirect( '/provider/budgets/update/' . $data[ 'code' ] )
                        ->withMessage( 'error', $response[ 'message' ] );
                }

                $budgetEntity = $budgetEntity->toArray();
                // Registrar a atividade de atualização
                $this->activityLogger(
                    $this->authenticated->tenant_id,
                    $this->authenticated->user_id,
                    'budget_updated',
                    'budget',
                    $data[ 'code' ],
                    "O orçamento {$data[ 'code' ]} foi atualizado.",
                    [
                        'before' => $originalData,
                        'after'  => $budgetEntity,
                    ],
                );
            }

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

    public function show( $code )
    {
        $budget = $this->budget->getBudgetByIdWhithCustomerDatas(
            $this->sanitize->sanitizeParamValue( $code, 'string' ),
            $this->authenticated->tenant_id,
        );
        if ( $budget instanceof EntityNotFound ) {
            return Redirect::redirect( '/provider/budgets' )->withMessage( 'error', 'Orçamento não encontrado.' );
        }
        $services = $this->service->getAllServiceFullByIdBudget( $budget->id, $this->authenticated->tenant_id );

        $service_items          = [];
        $latest_schedules       = [];
        $all_services_completed = true;
        foreach ( $services as $service ) {
            $service_items[ $service[ 'id' ] ]    = $this->serviceItem->getAllServiceItemsByIdService(
                $service[ 'id' ],
                $this->authenticated->tenant_id,
            );
            $latest_schedules[ $service[ 'id' ] ] = $this->schedule->getLatestByServiceId(
                $service[ 'id' ],
                $this->authenticated->tenant_id,
            );
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
        return new Response( $this->twig->env->render( 'pages/budget/show.twig', [
            'budget'                 => (array) $budget,
            'services'               => $services,
            'service_items'          => $service_items,
            'latest_schedules'       => $latest_schedules,
            'all_services_completed' => $all_services_completed,
        ] ) );
    }

    public function change_status(): Response
    {
        // TODO VER LOGICA PARA GERAR FATURA COMPLETA (se nesscesário colocar desconto no valor do orçamento)

        // Validar dados do formulário
        $validated = BudgetChangeStatusFormRequest::validate( $this->request );

        // Obter os dados do formulário
        $data = $this->request->all();

        // Se os dados não forem válidos, redirecionar para a página de atualização do orçamento e mostrar a mensagem de erro
        if ( !$validated ) {
            return Redirect::redirect( '/provider/budgets/show/' . $data[ 'budget_code' ] )->withMessage( 'error', 'Erro ao atualizar orçamento' );
        }

        $response = $this->budgetService->handleStatusChange( $data, $this->authenticated );

        if ( $response[ 'status' ] === 'error' ) {
            return Redirect::redirect( '/provider/budgets/show/' . $data[ 'budget_code' ] )->withMessage( 'error', $response[ 'message' ] );
        }

        $serviceStatusText    = '';
        $newServiceStatusName = $response[ 'data' ][ 'new_status_service_name' ] ?? '';

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
            "Status do orçamento {$data[ 'budget_code' ]} alterado para {$response[ 'data' ][ 'new_status_name' ]}{$serviceStatusText}.",
            [ $response[ 'data' ] ],
        );

        return Redirect::redirect( '/provider/budgets/show/' . $data[ 'budget_code' ] )->withMessage( 'success', 'Status do orçamento atualizado com sucesso!' );
    }

    public function choose_budget_status( $code, $token ): Response
    {
        $code  = $this->sanitize->sanitizeParamValue( $code, 'string' );
        $token = $this->sanitize->sanitizeParamValue( $token, 'string' );

        // Buscar o token e verificar se existe
        $response              = $this->sharedService->validateUserConfirmationToken( $token );
        $userConfirmationToken = $response[ 'data' ];

        if ( isset( $response[ 'data' ] ) ) {
            $userConfirmationToken = $response[ 'data' ];
            if ( $this->authenticated === null ) {
                $this->authenticated = $this->sharedService->getProviderByToken( $userConfirmationToken );
            }
        }
        if ( $response[ 'status' ] === 'error' ) {
            if ( $response[ 'condition' ] === 'expired' ) {
                $response = $this->budgetService->handleTokenUpdateBudget( $code, $response[ 'data' ], $this->authenticated );
                if ( $response[ 'status' ] === 'success' ) {
                    // Redirecionar para a página inicial
                    return Redirect::redirect( '/' )
                        ->withMessage( 'success', 'Token expirado. Um novo link foi enviado para seu email.' );
                } else {
                    return Redirect::redirect( '/' )->withMessage( 'error', $response[ 'message' ] );
                }
            } else {
                return Redirect::redirect( '/' )->withMessage( 'error', $response[ 'message' ] );
            }
        }

        $budget = $this->budget->getBudgetByIdWhithCustomerDatas(
            $this->sanitize->sanitizeParamValue( $code, 'string' ),
            $this->authenticated->tenant_id,
        );
        if ( $budget instanceof EntityNotFound ) {
            return Redirect::redirect( '/' )->withMessage( 'error', 'Orçamento não encontrado.' );
        }

        $services = $this->service->getAllServiceFullByIdBudget( $budget->id, $this->authenticated->tenant_id );

        $service_items    = [];
        $latest_schedules = [];

        $all_services_completed = true;
        foreach ( $services as $service ) {
            $service_items[ $service[ 'id' ] ]    = $this->serviceItem->getAllServiceItemsByIdService(
                $service[ 'id' ],
                $this->authenticated->tenant_id,
            );
            $latest_schedules[ $service[ 'id' ] ] = $this->schedule->getLatestByServiceId(
                $service[ 'id' ],
                $this->authenticated->tenant_id,
            );
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

    }

    public function choose_budget_status_store(): Response
    {
        // Validar dados do formulário
        $validated = BudgetChooseStatusFormRequest::validate( $this->request );

        // Obter os dados do formulário
        $data = $this->request->all();

        // Se os dados não forem válidos, redirecionar para a página inicial e mostrar a mensagem de erro
        if ( !$validated ) {
            return Redirect::redirect( '/budgets/choose-budget-status/code/' . $data[ 'budget_code' ] . '/token/' . $data[ 'token' ] )->withMessage( 'error', 'Erro ao atualizar o status do orçamento.' );
        }

        $response = $this->sharedService->validateUserConfirmationToken( $data[ 'token' ] );
        if ( $response[ 'status' ] === 'error' ) {
            return Redirect::redirect( '/' )->withMessage( 'error', $response[ 'message' ] );
        }
        $userConfirmationToken = $response[ 'data' ];

        $response = $this->budgetService->handleStatusChange( $data, $userConfirmationToken );

        if ( $response[ 'status' ] === 'error' ) {
            return Redirect::redirect( '/budgets/choose-budget-status/code/' . $data[ 'budget_code' ] . '/token/' . $data[ 'token' ] )->withMessage( 'error', $response[ 'message' ] );
        }

        $serviceStatusText    = '';
        $newServiceStatusName = $response[ 'data' ][ 'new_status_service_name' ] ?? '';

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
            "Status do orçamento {$data[ 'budget_code' ]} alterado para {$response[ 'data' ][ 'new_status_name' ]}{$serviceStatusText}.",
            [ $response[ 'data' ] ],
        );

        return Redirect::redirect( '/budgets/choose-budget-status/code/' . $data[ 'budget_code' ] . '/token/' . $data[ 'token' ] )->withMessage( 'success', 'Status do orçamento atualizado com sucesso!' );
    }

    public function print( $code, $token = null ): Response
    {
        $code                  = $this->sanitize->sanitizeParamValue( $code, 'string' );
        $token                 = $this->sanitize->sanitizeParamValue( $token, 'string' );
        $userConfirmationToken = null;

        // Buscar o token e verificar se existe
        if ( !empty( $token ) && $this->authenticated === null ) {
            $response = $this->sharedService->validateUserConfirmationToken( $token );

            if ( isset( $response[ 'data' ] ) ) {
                $userConfirmationToken = $response[ 'data' ];
                if ( $this->authenticated === null ) {
                    $this->authenticated = $this->sharedService->getProviderByToken( $userConfirmationToken );
                }
            }

            if ( $response[ 'status' ] === 'error' ) {
                if ( $response[ 'condition' ] === 'expired' ) {
                    $response = $this->budgetService->handleTokenUpdateBudget( $code, $response[ 'data' ], $this->authenticated );
                    if ( $response[ 'status' ] === 'error' ) {
                        return Redirect::redirect( '/' )->withMessage( 'error', $response[ 'message' ] );
                    } else {
                        // Redirecionar para a página inicial
                        return Redirect::redirect( '/' )
                            ->withMessage( 'success', 'Token expirado. Um novo link foi enviado para seu email.' );
                    }
                } else {
                    return Redirect::redirect( '/' )->withMessage( 'error', $response[ 'message' ] );
                }
            }
        }
        $budget = $this->budget->getBudgetByIdWhithCustomerDatas(
            $this->sanitize->sanitizeParamValue( $code, 'string' ),
            $this->authenticated->tenant_id,
        );
        if ( $budget instanceof EntityNotFound ) {
            return Redirect::redirect( '/' )->withMessage( 'error', 'Orçamento não encontrado.' );
        }

        // Se for um cliente (sem autenticação), validar o token e a data de validade
        if ( !$this->authenticated ) {
            if ( $budget->user_confirmation_token_id !== $userConfirmationToken->id ) {
                return Redirect::redirect( '/' )->withMessage( 'error', 'Token inválido para este orçamento.' );
            }
            if ( new DateTime() > convertToDateTime( $budget->due_date ) ) {
                return Redirect::redirect( '/' )->withMessage( 'error', 'Orçamento expirado.' );
            }
        }

        $customer = $this->customer->getCustomerFullbyId( $budget->customer_id, $this->authenticated->tenant_id );
        if ( $customer instanceof EntityNotFound ) {
            return Redirect::redirect( '/' )->withMessage( 'error', 'Cliente vinculado ao orçamento não encontrado.' );
        }

        $services = $this->service->getAllServiceFullByIdBudget( $budget->id, $this->authenticated->tenant_id );

        $service_items    = [];
        $latest_schedules = [];
        foreach ( $services as $service ) {
            $service_items[ $service[ 'id' ] ]    = $this->serviceItem->getAllServiceItemsByIdService(
                $service[ 'id' ],
                $this->authenticated->tenant_id,
            );
            $latest_schedules[ $service[ 'id' ] ] = $this->schedule->getLatestByServiceId(
                $service[ 'id' ],
                $this->authenticated->tenant_id,
            );
        }

        $response = $this->budgetService->printPDF( ( $this->authenticated ?? $userConfirmationToken ), $customer, $budget, $services, $service_items, $latest_schedules );

        // Sanitize the filename to remove potentially harmful characters and prevent header injection.
        $safeFilename = preg_replace( '/[^a-zA-Z0-9_.-]/', '_', $response[ 'fileName' ] );

        if ( empty( $response[ 'content' ] ) ) {
            return Redirect::redirect( '/' )->withMessage( 'error', 'Erro ao gerar o pdf.' );
        }

        return new Response(
            $response[ 'content' ],
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$safeFilename}\"",
            ],
        );
    }

    public function delete_store( $code ): Response
    {
        try {
            $code = $this->sanitize->sanitizeParamValue( $code, 'string' );

            $entity = $this->budget->getBudgetByCode( $code, $this->authenticated->tenant_id );

            if ( $entity instanceof EntityNotFound ) {
                return Redirect::redirect( '/provider/budgets' )
                    ->withMessage( 'error', "Orçamento não encontrado, tente novamente mais tarde ou entre em contato com suporte!" );
            }

            /** @var BudgetEntity $entity */
            $id = $entity->id;

            $response = $this->budget->checkRelationships(
                $id,
                $this->authenticated->tenant_id,
                [],
            );

            if ( $response[ 'status' ] === 'success' ) {
                $message   = "Orçamento não pode ser excluido pois possui {$response[ 'data' ][ 'countRelationships' ]} ";
                $message  .= "{$response[ 'data' ][ 'tables' ]} vinculado(s).";

                return Redirect::redirect( '/provider/budgets' )
                    ->withMessage( 'error', $message );
            }

            $response = $this->budget->delete( $id, $this->authenticated->tenant_id );
            if ( $response[ 'status' ] === 'success' ) {
                $this->activityLogger(
                    $this->authenticated->tenant_id,
                    $this->authenticated->user_id,
                    'budget_deleted',
                    'budget',
                    $response[ 'data' ][ 'id' ],
                    $response[ 'message' ],
                    [
                        'id' => $response[ 'data' ][ 'id' ],
                    ],
                );

                // Se tudo ocorreu bem, redirecionar para a página de atualização e mostrar a mensagem de sucesso
                return Redirect::redirect( '/provider/budgets' )
                    ->withMessage( 'success', 'Orçamento deletado com sucesso!' );
            }

            return Redirect::redirect( '/provider/budgets' )
                ->withMessage( 'error', 'Erro ao deletar orçamento, tente novamente mais tarde ou entre em contato com suporte!' );

        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao deletar orçamento, tente novamente mais tarde ou entre em contato com suporte!" );

            return Redirect::redirect( '/provider/budgets' );
        }
    }

    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] )
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}
