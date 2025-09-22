<?php

namespace app\controllers;

use app\database\entitiesORM\AddressEntity;
use app\database\entitiesORM\CommonDataEntity;
use app\database\entitiesORM\ContactEntity;
use app\database\entitiesORM\CustomerEntity;
use app\database\repositories\AreaOfActivityRepository;
use app\database\repositories\ContactRepository;
use app\database\repositories\CustomerRepository;
use app\database\repositories\ProfessionRepository;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\CustomerService;
use app\request\CustomerFormRequest;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Sanitize;
use core\library\Session;
use core\library\Twig;
use http\Redirect;
use http\Request;
use Throwable;

class CustomerController extends AbstractController
{
    /**
     * Constructor for the CustomerController class.
     * Initializes a new instance of the CustomerController.
     *
     * @author Easy Budget System
     * @since 1.0.0
     */
    public function __construct(
        protected Twig $twig,
        private AreaOfActivityRepository $areaOfActivityRepository,
        private ProfessionRepository $professionRepository,
        protected CustomerService $customerService,
        private ContactRepository $contactRepository,
        Sanitize $sanitize,
        ActivityService $activityService,
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
        return new Response( $this->twig->env->render( 'pages/customer/index.twig' ) );
    }

    public function create(): Response
    {
        // Áreas de atuação
        $areas_of_activity = $this->areaOfActivityRepository->findAll();
        // Profissões
        $professions = $this->professionRepository->findAll();

        // Retornar a view de criação do cliente com os dados completos do cliente, áreas de atuação e profissões
        return new Response( $this->twig->env->render( 'pages/customer/create.twig', [ 
            'areas_of_activity' => $areas_of_activity,
            'professions'       => $professions,
        ] ) );
    }

    public function store(): Response
    {
        try {
            // Validar os dados do formulário de criação de cliente
            $validated = CustomerFormRequest::validate( $this->request );

            // Se os dados não forem válidos, redirecionar para a página de criação de cliente e mostrar a mensagem de erro
            if ( !$validated ) {
                return Redirect::redirect( '/provider/customers/create' )->withMessage( 'error', 'Erro ao cadastar o cliente.' );
            }

            // Obter os dados do formulário de criação de usuário
            $data = $this->request->all();

            // Verificar se email já existe
            $checkObj = $this->contactRepository->findByEmail( $data[ 'email' ], $this->authenticated->tenant_id );

            // Se já existe um cliente com este email, redirecionar para a página de criação de cliente e mostrar a mensagem de erro
            if ( $checkObj !== null ) {
                return Redirect::redirect( '/provider/customers/create' )->withMessage( 'error', 'Cliente com este e-mail já cadastrado.' );
            }

            // Criar novo cliente
            $response = $this->customerService->createByTenantId( $data, $this->authenticated->tenant_id );

            // Se não foi possível criar o novo usuário, redirecionar para a página inicial e mostrar a mensagem de erro
            if ( !$response->isSuccess() ) {
                return Redirect::redirect( '/provider/customers/create' )->withMessage( 'error', $response->getMessage() );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'customer_created',
                'customer',
                (int) $response[ 'data' ][ 'id' ],
                $response[ 'message' ],
                $response[ 'data' ],
            );

            // Se tudo ocorreu bem, redirecionar para a página de detalhes do cliente e mostrar a mensagem de sucesso
            return Redirect::redirect( '/provider/customers/show/' . $response[ 'data' ][ 'id' ] )->withMessage( 'success', $response[ 'message' ] );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao registrar o cliente, tente novamente mais tarde ou entre em contato com suporte!" );

            return Redirect::redirect( '/provider/customers/create' );
        }

    }

    public function show( int $id ): Response
    {
        try {
            $params = $this->autoSanitizeForEntity( [ 'id' => $id ], CustomerEntity::class);
            $id     = $params[ 'id' ];

            $customer = $this->customerService->getCustomerFullById( $id, $this->authenticated->tenant_id );

            if ( !$customer->isSuccess() ) {
                return Redirect::redirect( '/provider/customers' )->withMessage( 'error', $customer->message ?? 'Cliente não encontrado.' );
            }

            $customer = $customer->data;

            return new Response( $this->twig->env->render( 'pages/customer/show.twig', [ 
                'customer' => $customer,
            ] ) );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao carregar cliente.' );
            return Redirect::redirect( '/provider/customers' );
        }
    }

    public function update( int $id ): Response
    {
        try {
            $params = $this->autoSanitizeForEntity( [ 'id' => $id ], CustomerEntity::class);
            $id     = $params[ 'id' ];

            $customer = $this->customerService->getCustomerFullById( $id, $this->authenticated->tenant_id );

            if ( !$customer->isSuccess() ) {
                return Redirect::redirect( '/provider/customers' )->withMessage( 'error', $customer->message ?? 'Cliente não encontrado.' );
            }

            $customer = $customer->data;

            $areas_of_activity = $this->areaOfActivityRepository->findAll();
            $professions       = $this->professionRepository->findAll();

            return new Response( $this->twig->env->render( 'pages/customer/update.twig', [ 
                'customer'          => $customer,
                'areas_of_activity' => $areas_of_activity,
                'professions'       => $professions,
            ] ) );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao carregar cliente para edição.' );
            return Redirect::redirect( '/provider/customers' );
        }
    }

    public function update_store(): Response
    {
        try {
            $validated = CustomerFormRequest::validate( $this->request );

            if ( !$validated ) {
                return Redirect::redirect( '/provider/customers' )->withMessage( 'error', 'Dados inválidos.' );
            }

            $data = $this->request->all();

            $response = $this->customerService->updateByIdAndTenantId( $data[ 'id' ], $this->authenticated->tenant_id, $data );

            if ( !$response->isSuccess() ) {
                return Redirect::redirect( '/provider/customers/update/' . $data[ 'id' ] )
                    ->withMessage( 'error', $response->message ?? 'Erro ao atualizar.' );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'customer_updated',
                'customer',
                $data[ 'id' ],
                "Cliente atualizado com sucesso!",
                [ 'data' => $data ],
            );

            return Redirect::redirect( '/provider/customers/show/' . $data[ 'id' ] )
                ->withMessage( 'success', 'Cliente atualizado com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Falha ao atualizar cliente.' );
            return Redirect::redirect( '/provider/customers' );
        }
    }

    public function delete_store( int $id ): Response
    {
        try {
            $params = $this->autoSanitizeForEntity( [ 'id' => $id ], CustomerEntity::class);
            $id     = $params[ 'id' ];

            // Placeholder: Use public method in service
            $relationships = $this->customerService->checkForDeletion( $id, $this->authenticated->tenant_id );

            if ( !$relationships->isSuccess() ) {
                return Redirect::redirect( '/provider/customers' )
                    ->withMessage( 'error', $relationships->message ?? 'Erro ao verificar relacionamentos.' );
            }

            if ( $relationships->data[ 'hasRelationships' ] ) {
                $message = "Cliente não pode ser deletado pois possui {$relationships->data[ 'count' ]} ";
                $message .= "{$relationships->data[ 'table' ]} vinculado(s).";

                return Redirect::redirect( '/provider/customers' )
                    ->withMessage( 'error', $message );
            }

            $response = $this->customerService->deleteByIdAndTenantId( $id, $this->authenticated->tenant_id );
            if ( $response->isSuccess() ) {

                $this->activityLogger(
                    $this->authenticated->tenant_id,
                    $this->authenticated->user_id,
                    'customer_deleted',
                    'customer',
                    $id,
                    "Cliente deletado com sucesso!",
                    [ 
                        'id' => $id,
                    ],
                );

                return Redirect::redirect( '/provider/customers' )
                    ->withMessage( 'success', 'Cliente deletado com sucesso!' );
            }

            return Redirect::redirect( '/provider/customers' )
                ->withMessage( 'error', 'Cliente não pode ser deletado, pode haver relações com outros registros, contate o suporte!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Falha ao deletar cliente.' );
            return Redirect::redirect( '/provider/customers' );
        }
    }

    // activityLogger herdado do AbstractController
}
