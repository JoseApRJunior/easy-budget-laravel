<?php

namespace app\controllers;

use app\database\entitiesORM\ScheduleEntity;
use app\database\entitiesORM\ServiceEntity;
use app\database\models\Budget;
use app\database\models\Category;
use app\database\models\Customer;
use app\database\models\Invoice;
use app\database\models\Product;
use app\database\models\Schedule;
use app\database\models\Service;
use app\database\models\ServiceItem;
use app\database\models\Unit;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\ServiceService;
use app\database\servicesORM\SharedService;
use app\request\ServiceChangeStatusFormRequest;
use app\request\ServiceChooseStatusFormRequest;
use app\request\ServiceFormRequest;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Sanitize;
use core\library\Session;
use core\library\Twig;
use http\Redirect;
use http\Request;
use Throwable;

class ServiceController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        private Budget $budget,
        protected Sanitize $sanitize,
        protected ServiceService $serviceService,
        protected Service $service,
        protected ServiceItem $serviceItem,
        private Product $product,
        private Category $category,
        private Invoice $invoiceModel,
        protected ActivityService $activityService,
        private Unit $unit,
        private Schedule $schedule,
        private Customer $customer,
        protected SharedService $sharedService,
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
        return new Response( $this->twig->env->render( 'pages/service/index.twig' ) );
    }

    public function create( ?string $code = null ): Response
    {
        $categories = $this->category->getAllCategories();
        //TODO IMPLEMENTAR USO DE UNITS
        $units    = $this->unit->findAllByTenant();
        $products = $this->product->getAllProductsActive( $this->authenticated->tenant_id );

        $code    = $this->sanitize->sanitizeParamValue( $code, 'int' );
        $budgets = $this->budget->getAllBudgetsNotCompleted( $this->authenticated->tenant_id );

        return new Response( $this->twig->env->render( 'pages/service/create.twig', [ 
            'budgets'    => $budgets,
            'code'       => $code,
            'categories' => $categories,
            'units'      => $units,
            'products'   => $products,
        ] ) );
    }

    public function store(): Response
    {
        try {
            // Validar os dados do formulário de criação de cliente
            $validated = ServiceFormRequest::validate( $this->request );

            // Se os dados não forem válidos, redirecionar para a página de criação de servico e mostrar a mensagem de erro
            if ( !$validated ) {
                return Redirect::redirect( '/provider/services/create' )->withMessage( 'error', 'Erro ao cadastar o servico.' );
            }

            // Criar novo servico
            $data     = $this->request->all();
            $response = $this->serviceService->createService( $data );

            // Se houve erro, redirecionar com a mensagem adequada
            if ( $response[ 'status' ] === 'error' ) {
                return Redirect::redirect( '/provider/services/create' )->withMessage( 'error', $response[ 'message' ] . ", tente novamente mais tarde ou entre em contato com suporte!" );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'service_created',
                'service',
                $response[ 'data' ][ 'created_service_id' ],
                $response[ 'message' ],
                $response[ 'data' ],
            );

            // Se tudo ocorreu bem, redirecionar para a página de detalhes do servico e mostrar a mensagem de sucesso
            return Redirect::redirect( '/provider/services/show/' . $response[ "data" ][ "created_service" ]->code )->withMessage( 'success', $response[ 'message' ] );

            // Se houver erro redirecionar para a página inicial criar servico e mostrar a mensagem de erro
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao cadastrar o servico, tente novamente mais tarde ou entre em contato com suporte!" );

            return Redirect::redirect( '/provider/services/create' );
        }
    }

    public function show( string $code ): Response
    {
        $code = $this->sanitize->sanitizeParamValue( $code, 'string' );

        $service = $this->service->getServiceFullByCode( $code, $this->authenticated->tenant_id );

        if ( !$service[ 'success' ] ) {
            return Redirect::redirect( '/provider/services' )
                ->withMessage( 'error', "Serviço não encontrado, tente novamente mais tarde ou entre em contato com suporte!" );
        }
        $budget          = $this->budget->getBudgetByIdWhithCustomerDatas(
            $service[ 'data' ]->budget_code,
            $this->authenticated->tenant_id,
        );
        $serviceItems    = $this->serviceItem->getAllServiceItemsByIdService(
            $service[ 'data' ]->id,
            $this->authenticated->tenant_id,
        );
        $latest_schedule = $this->schedule->getLatestByServiceId(
            $service[ 'data' ]->id,
            $this->authenticated->tenant_id,
        );
        $invoice         = $this->invoiceModel->findBy( [ 
            'tenant_id'  => $this->authenticated->tenant_id,
            'service_id' => $service[ 'data' ]->id,
        ] );

        return new Response( $this->twig->env->render( 'pages/service/show.twig', [ 
            'service'         => (array) $service[ 'data' ],
            'latest_schedule' => $latest_schedule,
            'serviceItems'    => $serviceItems,
            'budget'          => (array) $budget,
            'invoice'         => ( $invoice[ 'success' ] ) ? $invoice[ 'data' ] : null,

        ] ) );
    }

    public function change_status(): Response
    {
        // Validar dados do formulário
        $validated = ServiceChangeStatusFormRequest::validate( $this->request );

        // Obter os dados do formulário
        $data = $this->request->all();

        // Se os dados não forem válidos, redirecionar para a página de atualização do orçamento e mostrar a mensagem de erro
        if ( !$validated ) {
            return Redirect::redirect( '/provider/services/show/' . $data[ 'service_code' ] )->withMessage( 'error', 'Erro ao atualizar o serviço.' );
        }

        $response = $this->serviceService->handleStatusChange( $data, $this->authenticated );

        if ( $response[ 'status' ] === 'error' ) {
            return Redirect::redirect( '/provider/services/show/' . $data[ 'service_code' ] )->withMessage( 'error', $response[ 'message' ] );
        }
        // Registrar a mudança de status no histórico de atividades
        $this->activityLogger(
            $this->authenticated->tenant_id,
            $this->authenticated->user_id,
            'service_status_changed',
            'service',
            $data[ 'service_id' ],
            "Status do serviço {$data[ 'service_code' ]} alterado para {$response[ 'data' ][ 'new_status_name' ]}" .
            ( ( $response[ 'data' ][ 'new_status_budget_name' ] !== '' ) ? " e orçamento para {$response[ 'data' ][ 'new_status_budget_name' ]} ." : "." ),
            [ 'response_data' => $response[ 'data' ] ],
        );

        return Redirect::redirect( '/provider/services/show/' . $data[ 'service_code' ] )->withMessage( 'success', 'Status do serviço atualizado com sucesso!' );
    }

    public function update( string $code ): Response
    {
        $code       = $this->sanitize->sanitizeParamValue( $code, 'string' );
        $categories = $this->category->getAllCategories();
        //TODO IMPLEMENTAR USO DE UNITS
        $units        = $this->unit->findAllByTenant();
        $products     = $this->product->getAllProductsActive( $this->authenticated->tenant_id );
        $budgets      = $this->budget->getAllBudgetsNotCompleted( $this->authenticated->tenant_id );
        $service      = $this->service->getServiceFullByCode( $code, $this->authenticated->tenant_id );
        $serviceItems = $this->serviceItem->getAllServiceItemsByIdService(
            $service[ 'id' ],
            $this->authenticated->tenant_id,
        );

        return new Response( $this->twig->env->render( 'pages/service/update.twig', [ 
            'service'      => $service,
            'serviceItems' => $serviceItems,
            'budgets'      => $budgets,
            'categories'   => $categories,
            'units'        => $units,
            'products'     => $products,
        ] ) );
    }

    public function update_store(): Response
    {
        try {
            // Validar os dados do formulário de criação de cliente
            $validated = ServiceFormRequest::validate( $this->request );

            // Pegar os dados do formulário
            $data = $this->request->all();

            // Se os dados não forem válidos, redirecionar para a página de atualização de servico e mostrar a mensagem de erro
            if ( !$validated ) {
                return Redirect::redirect( '/provider/services/update/' . $data[ 'code' ] )
                    ->withMessage( 'error', 'Erro ao atualizar o servico.' );
            }

            $response = $this->serviceService->updateService( $data );

            // Se houve erro, redirecionar com a mensagem adequada
            if ( $response[ 'status' ] === 'error' ) {
                return Redirect::redirect( '/provider/services/update/' . $data[ 'code' ] )
                    ->withMessage( 'error', $response[ 'message' ] . ", tente novamente mais tarde ou entre em contato com suporte!" );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'service_updated',
                'service',
                $data[ 'id' ],
                $response[ 'message' ],
                $response[ 'data' ],
            );

            // Se tudo ocorreu bem, redirecionar para a página de detalhes do orçamento e mostrar a mensagem de sucesso
            return Redirect::redirect( '/provider/services/show/' . $data[ 'code' ] )
                ->withMessage( 'success', 'Serviço atualizado com sucesso!' );

        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );

            // Obter o ID do orçamento da requisição para redirecionar corretamente
            $code = $this->request->get( 'code' );
            ;
            $redirectUrl = $code ? "/provider/services/update/{$code}" : "/provider/services";

            Session::flash( 'error', "Falha ao atualizar o servico, tente novamente mais tarde ou entre em contato com suporte!" );

            return Redirect::redirect( $redirectUrl );
        }

    }

    public function view_service_status( string $code, string $token ): Response
    {
        $code                  = $this->sanitize->sanitizeParamValue( $code, 'string' );
        $token                 = $this->sanitize->sanitizeParamValue( $token, 'string' );
        $userConfirmationToken = null;

        // Buscar o token e verificar se existe
        $response = $this->sharedService->validateUserConfirmationToken( $token );

        if ( isset( $response[ 'data' ] ) ) {
            $userConfirmationToken = $response[ 'data' ];
            if ( $this->authenticated === null ) {
                $this->authenticated           = $this->sharedService->getProviderByToken( $userConfirmationToken );
                $this->authenticated->password = null; // Remover a senha do objeto autenticado
            }
        }

        if ( $response[ 'status' ] === 'error' ) {
            if ( $response[ 'condition' ] === 'expired' ) {
                $response = $this->serviceService->handleTokenUpdateScheduledStatus( $code, $response[ 'data' ], $this->authenticated );
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

        $service = $this->service->getServiceFullByCode(
            $code,
            $this->authenticated->tenant_id,
        );
        if ( !$service[ 'success' ] ) {
            return Redirect::redirect( '/' )->withMessage( 'error', 'Serviço não encontrado.' );
        }

        $budget = $this->budget->getBudgetFullByCode(
            $service[ 'data' ]->budget_code,
            $this->authenticated->tenant_id,
        );
        if ( !$budget[ 'success' ] ) {
            return Redirect::redirect( '/' )->withMessage( 'error', 'Orçamento não encontrado.' );
        }

        $serviceItems           = [];
        $all_services_completed = true;
        $serviceItems           = $this->serviceItem->getAllServiceItemsByIdService(
            $service[ 'data' ]->id,
            $this->authenticated->tenant_id,
        );
        $all_services_completed =
            in_array( $service[ 'data' ]->status_slug, [ 
                'COMPLETED',
                'PARTIAL',
                'CANCELLED',
                'NOT_PERFORMED',
                'EXPIRED',
            ] );

        $latest_schedule = $this->schedule->getLatestByServiceId(
            $service[ 'data' ]->id,
            $this->authenticated->tenant_id,
        );

        // Validação do agendamento e do token
        if ( !$latest_schedule[ 'success' ] ) {
            return Redirect::redirect( '/' )->withMessage( 'error', 'Nenhum agendamento encontrado para este serviço.' );
        }

        /** @var ScheduleEntity $latest_schedule */
        if ( $latest_schedule->user_confirmation_token_id !== $userConfirmationToken->id ) {
            return Redirect::redirect( '/' )->withMessage( 'error', 'Token inválido para este serviço.' );
        }
        if ( new \DateTime() > convertToDateTime( $service->due_date ) ) {
            return Redirect::redirect( '/' )->withMessage( 'error', 'A data de validade deste serviço expirou.' );
        }

        // Retornar a view do orçamento com os dados e services
        $body = $this->twig->env->render( 'pages/service/view_service_status.twig', [ 
            'budget'                 => (array) $budget,
            'service'                => (array) $service,
            'latest_schedule'        => $latest_schedule,
            'serviceItems'           => $serviceItems,
            'all_services_completed' => $all_services_completed,
            'token'                  => $token,
        ] );

        return new Response( $body );

    }

    public function choose_service_status_store(): Response
    {

        // Validar dados do formulário
        $validated = ServiceChooseStatusFormRequest::validate( $this->request );

        // Obter os dados do formulário
        $data = $this->request->all();

        // Se os dados não forem válidos, redirecionar para a página inicial e mostrar a mensagem de erro
        if ( !$validated ) {
            return Redirect::redirect( '/services/view-service-status/code/' . $data[ 'service_code' ] . '/token/' . $data[ 'token' ] )->withMessage( 'error', 'Erro ao atualizar o status do serviço.' );
        }

        // Buscar o token e verificar se existe

        $response = $this->sharedService->validateUserConfirmationToken( $data[ 'token' ] );

        if ( $response[ 'status' ] === 'error' ) {
            return Redirect::redirect( '/' )->withMessage( 'error', $response[ 'message' ] );
        }
        $userConfirmationToken = $response[ 'data' ];
        if ( $this->authenticated === null ) {
            $this->authenticated = $this->sharedService->getProviderByToken( $userConfirmationToken );
        }

        $response = $this->serviceService->handleStatusChange( $data, $this->authenticated );

        if ( $response[ 'status' ] === 'error' ) {
            return Redirect::redirect( '/services/view-service-status/code/' . $data[ 'service_code' ] . '/token/' . $data[ 'token' ] )->withMessage( 'error', $response[ 'message' ] );
        }

        // Registrar a mudança de status no histórico de atividades
        $this->activityLogger(
            $this->authenticated->tenant_id,
            $userConfirmationToken->user_id,
            'service_status_changed',
            'service',
            $data[ 'service_id' ],
            "Status do serviço {$data[ 'service_code' ]} alterado para {$response[ 'data' ][ 'new_status_name' ]}.",
            [ 'response_data' => $response[ 'data' ] ],
        );

        return Redirect::redirect( '/services/view-service-status/code/' . $data[ 'service_code' ] . '/token/' . $data[ 'token' ] )->withMessage( 'success', 'Status do serviço atualizado com sucesso!' );
    }

    public function delete_store( string $code ): Response
    {
        try {
            $response = [];
            $code     = $this->sanitize->sanitizeParamValue( $code, 'string' );

            $entity = $this->service->getServiceByCode( $code, $this->authenticated->tenant_id );

            if ( !$entity[ 'success' ] ) {
                return Redirect::redirect( '/provider/services' )
                    ->withMessage( 'error', "Serviço não encontrado, tente novamente mais tarde ou entre em contato com suporte!" );
            }

            /** @var ServiceEntity $entity */
            $id = $entity[ 'data' ]->id;

            $serviceItems = $this->serviceItem->getAllServiceItemsByIdService(
                $id,
                $this->authenticated->tenant_id,
            );
            foreach ( $serviceItems as $serviceItem ) {
                $response = $this->serviceItem->delete( $serviceItem[ 'id' ], $this->authenticated->tenant_id );
            }

            if ( !$response[ 'success' ] ) {
                return Redirect::redirect( '/provider/services' )
                    ->withMessage( 'error', "Erro ao excluir os itens do servico, tente novamente mais tarde ou entre em contato com suporte!" );
            }

            $response = $this->service->delete( $id, $this->authenticated->tenant_id );
            if ( !$response[ 'success' ] ) {
                return Redirect::redirect( '/provider/services' )
                    ->withMessage( 'error', 'Erro ao deletar o servico, tente novamente mais tarde ou entre em contato com suporte!' );

            }
            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'service_deleted',
                'service',
                $response[ 'data' ][ 'id' ],
                $response[ 'message' ],
                [ 
                    'service'      => $entity,
                    'serviceItems' => $serviceItems,
                ],
            );

            // Se tudo ocorreu bem, redirecionar para a página de atualização e mostrar a mensagem de sucesso
            return Redirect::redirect( '/provider/services' )
                ->withMessage( 'success', 'Serviço deletado com sucesso!' );

        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao deletar o servico, tente novamente mais tarde ou entre em contato com suporte!" );

            return Redirect::redirect( '/provider/services' );
        }
    }

    public function cancel( string $code ): Response
    {
        $code = $this->sanitize->sanitizeParamValue( $code, 'string' );

        // Buscar os dados do servico
        $service = $this->service->getServiceByCode( $code, $this->authenticated->tenant_id );

        // Converter o objeto para array
        $originalData = $service->toArray();

        // Popula UserEntity com os dados do formulário
        $serviceEntity = ServiceEntity::create( removeUnnecessaryIndexes(
            $originalData,
            [ 'created_at', 'updated_at' ],
            [ 'service_statuses_id' => 9 ], // id do status cancelado
        ) );

        // Atualizar ServiceEntity com os dados do formuláriorio
        $response = $this->service->update( $serviceEntity );
        if ( $response[ 'status' ] === 'error' ) {
            // Se tudo ocorreu bem, redirecionar para a página de atualização e mostrar a mensagem de sucesso
            return Redirect::redirect( '/provider/services/show/' . $code )
                ->withMessage( 'error', 'Falha ao cancelar o servico, pode haver relações com outros registros, contate o suporte!' );
        }
        $this->activityLogger(
            $this->authenticated->tenant_id,
            $this->authenticated->user_id,
            'service_updated',
            'service',
            $originalData[ 'id' ],
            "Serviço {$code} cancelado com sucesso!",
            [ 
                'id' => $originalData[ 'id' ],
            ],
        );

        // Se tudo ocorreu bem, redirecionar para a página de atualização e mostrar a mensagem de sucesso
        return Redirect::redirect( '/provider/services/show/' . $code )
            ->withMessage( 'success', 'Serviço cancelado com sucesso!' );

    }

    public function print( string $code, ?string $token = null ): Response
    {
        $code                  = $this->sanitize->sanitizeParamValue( $code, 'string' );
        $token                 = $this->sanitize->sanitizeParamValue( $token, 'string' );
        $userConfirmationToken = null;
        if ( !empty( $token ) && $this->authenticated === null ) {

            // Buscar o token e verificar se existe
            $response = $this->sharedService->validateUserConfirmationToken( $token );

            if ( isset( $response[ 'data' ] ) ) {
                $userConfirmationToken = $response[ 'data' ];
                if ( $this->authenticated === null ) {
                    $this->authenticated = $this->sharedService->getProviderByToken( $userConfirmationToken );
                }
            }

            if ( $response[ 'status' ] === 'error' ) {
                if ( $response[ 'condition' ] === 'expired' ) {
                    $response = $this->serviceService->handleTokenUpdateScheduledStatus( $code, $response[ 'data' ], $this->authenticated );
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

        $service = $this->service->getServiceFullByCode(
            $code,
            $this->authenticated->tenant_id,
        );
        if ( $service instanceof EntityNotFound ) {
            return Redirect::redirect( '/' )->withMessage( 'error', 'Serviço vinculado ao orçamento não encontrado.' );
        }
        $budget = $this->budget->getBudgetByIdWhithCustomerDatas(
            $service->budget_code,
            $this->authenticated->tenant_id,
        );
        if ( $budget instanceof EntityNotFound ) {
            return Redirect::redirect( '/' )->withMessage( 'error', 'Orçamento não encontrado.' );
        }

        $customer = $this->customer->getCustomerFullbyId( $budget->customer_id, $this->authenticated->tenant_id );
        if ( $customer instanceof EntityNotFound ) {
            return Redirect::redirect( '/' )->withMessage( 'error', 'Cliente vinculado ao orçamento não encontrado.' );
        }

        $service_items = $this->serviceItem->getAllServiceItemsByIdService(
            $service->id,
            $this->authenticated->tenant_id,
        );

        $latest_schedule = $this->schedule->getLatestByServiceId(
            $service->id,
            $this->authenticated->tenant_id,
        );

        if ( $userConfirmationToken !== null && $token !== null ) {
            // Validação do agendamento e do token
            if ( $latest_schedule instanceof EntityNotFound ) {
                return Redirect::redirect( '/' )->withMessage( 'success', 'Nenhum agendamento encontrado para este serviço.' )->withMessage( 'success', 'Um novo código foi enviado para seu email' );
            }

            /** @var ScheduleEntity $latest_schedule */
            if ( $latest_schedule->user_confirmation_token_id !== $userConfirmationToken->id ) {
                return Redirect::redirect( '/' )->withMessage( 'error', 'Token inválido para este serviço.' );
            }
            if ( new \DateTime() > convertToDateTime( $service->due_date ) ) {
                return Redirect::redirect( '/' )->withMessage( 'error', 'A data de validade deste serviço expirou.' );
            }
        }

        $response = $this->serviceService->printPDF( $this->authenticated, $customer, $budget, $service, $service_items, $latest_schedule );

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

    /**
     * @inheritDoc
     */
    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}
