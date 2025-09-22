<?php
declare(strict_types=1);

namespace app\controllers\admin;

use app\controllers\AbstractController;
use app\database\entitiesORM\UnitEntity;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\UnitService;
use app\request\UnitFormRequest;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Sanitize;
use core\library\Session;
use core\library\Twig;
use http\Redirect;
use http\Request;
use Throwable;

/**
 * Controller para gerenciamento de unidades
 */
class UnitController extends AbstractController
{

    /**
     * Construtor da classe UnitController
     *
     * @param Twig $twig Serviço de template
     * @param UnitService $unitService Serviço de unidades
     * @param Sanitize $sanitize Serviço de sanitização
     * @param ActivityService $activityService Serviço de atividades
     * @param Request $request Requisição HTTP
     */
    public function __construct(
        protected Twig $twig,
        private UnitService $unitService,
        protected Sanitize $sanitize,
        protected ActivityService $activityService,

        Request $request,
    ) {
        parent::__construct( $request );
    }

    /**
     * Exibe a lista de unidades
     *
     * @return Response
     */
    public function index(): Response
    {
        try {
            // Buscar todas as unidades usando o UnitService
            $units = $this->unitService->list();

            // Renderizar o template passando as unidades
            return new Response( $this->twig->env->render( 'pages/unit/index.twig', [ 
                'units' => $units
            ] ) );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            // Em caso de erro, renderizar a página com array vazio e mensagem de erro
            Session::flash( 'error', 'Erro ao carregar as unidades. Tente novamente mais tarde.' );
            return new Response( $this->twig->env->render( 'pages/unit/index.twig', [ 
                'units' => []
            ] ) );
        }
    }

    /**
     * Exibe o formulário de criação de unidade
     *
     * @return Response
     */
    public function create(): Response
    {
        return new Response( $this->twig->env->render( 'pages/unit/create.twig' ) );
    }

    /**
     * Processa o formulário de criação de unidade
     *
     * @return Response
     */
    public function store(): Response
    {
        try {
            // Validar os dados do formulário
            $validated = UnitFormRequest::validate( $this->request );

            // Se os dados não forem válidos, redirecionar para a página de criação
            if ( !$validated ) {
                return Redirect::redirect( '/admin/units/create' )
                    ->withMessage( 'error', 'Erro ao cadastrar a unidade.' );
            }

            // Obter e sanitizar automaticamente os dados do formulário com base na entidade UnitEntity
            $data     = $this->autoSanitizeForEntity( $this->request->all(), UnitEntity::class);
            $response = $this->unitService->create( $data );

            // Se houve erro, redirecionar com a mensagem adequada
            if ( !$response->isSuccess() ) {
                return Redirect::redirect( '/admin/units/create' )
                    ->withMessage( 'error', $response->message ?? 'Erro ao cadastrar a unidade.' );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'unit_created',
                'unit',
                $response->data->getId(),
                "Unidade {$data[ 'name' ]} criada",
                [ 
                    'entity' => $response->data->jsonSerialize()
                ],
            );

            // Se tudo ocorreu bem, redirecionar para a lista de unidades
            return Redirect::redirect( '/admin/units' )
                ->withMessage( 'success', 'Unidade cadastrada com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao cadastrar a unidade, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/units/create' );
        }
    }

    /**
     * Exibe os detalhes de uma unidade
     *
     * @param string $id ID da unidade
     * @return Response
     */
    public function show( string $id ): Response
    {
        // Sanitizar o ID usando o método do trait AutoSanitizationTrait
        $params       = $this->autoSanitizeForEntity( [ 'id' => $id ], UnitEntity::class);
        $unitResponse = $this->unitService->getById( $params[ 'id' ] );

        if ( !$unitResponse[ 'success' ] ) {
            return Redirect::redirect( '/admin/units' )
                ->withMessage( 'error', $unitResponse[ 'message' ] );
        }

        $unit = $unitResponse[ 'data' ][ 'entity' ];

        return new Response( $this->twig->env->render( 'pages/unit/show.twig', [ 
            'unit' => $unit,
        ] ) );
    }

    /**
     * Exibe o formulário de edição de unidade
     *
     * @param string $id ID da unidade
     * @return Response
     */
    public function edit( string $id ): Response
    {
        // Sanitizar o ID usando o método do trait AutoSanitizationTrait
        $params       = $this->autoSanitizeForEntity( [ 'id' => $id ], UnitEntity::class);
        $unitResponse = $this->unitService->getById( $params[ 'id' ] );

        if ( !$unitResponse[ 'success' ] ) {
            return Redirect::redirect( '/admin/units' )
                ->withMessage( 'error', $unitResponse[ 'message' ] );
        }

        $unit = $unitResponse[ 'data' ][ 'entity' ];

        return new Response( $this->twig->env->render( 'pages/unit/edit.twig', [ 
            'unit' => $unit,
        ] ) );
    }

    /**
     * Processa o formulário de edição de unidade
     *
     * @return Response
     */
    public function update(): Response
    {
        $requestData = $this->request->all();
        $id          = $requestData[ 'id' ] ?? null;
        // Se os dados não forem válidos, redirecionar para a página de edição
        if ( !$UnitFormRequest::validate( $this->request ) ) {
            $redirectId = $id ?? '';
            return Redirect::redirect( "/admin/units/edit/{$redirectId}" )
                ->withMessage( 'error', 'Erro ao atualizar a unidade.' );
        }

        try {
            // Sanitizar o ID manualmente para garantir que seja um inteiro válido
            $id = $this->sanitize->sanitizeParamValue( $id, 'int' );

            // Sanitizar automaticamente os dados do formulário com base na entidade UnitEntity
            $data     = $this->autoSanitizeForEntity( $requestData, UnitEntity::class);
            $response = $this->unitService->update( $id, $data );

            // Se houve erro, redirecionar com a mensagem adequada
            if ( !$response->isSuccess() ) {
                return Redirect::redirect( "/admin/units/edit/{$id}" )
                    ->withMessage( 'error', $response->message ?? 'Erro ao atualizar a unidade.' );
            }

            // Log da atividade de atualização
            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'unit_updated',
                'unit',
                $response->data->getId(),
                "Unidade {$data[ 'name' ]} atualizada",
                [ 
                    'entity' => $response->data->jsonSerialize()
                ],
            );

            // Se tudo ocorreu bem, redirecionar para a lista de unidades
            return Redirect::redirect( '/admin/units' )
                ->withMessage( 'success', 'Unidade atualizada com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao atualizar a unidade, tente novamente mais tarde ou entre em contato com suporte!" );
            $redirectId = $id ?? '';
            return Redirect::redirect( "/admin/units/edit/{$redirectId}" );
        }
    }

    /**
     * Exclui uma unidade
     *
     * @param string $id ID da unidade
     * @return Response
     */
    public function delete( string $id ): Response
    {
        try {
            // Sanitizar o ID usando o método do trait AutoSanitizationTrait
            $params = $this->autoSanitizeForEntity( [ 'id' => $id ], UnitEntity::class);
            $unitId = $params[ 'id' ];

            // Buscar dados da unidade antes de deletar para o log
            $unitResponse = $this->unitService->getById( $unitId );

            if ( !$unitResponse->isSuccess() ) {
                return Redirect::redirect( '/admin/units' )
                    ->withMessage( 'error', $unitResponse->message ?? 'Unidade não encontrada.' );
            }

            $unitData = $unitResponse->data;

            $response = $this->unitService->delete( $unitId );

            if ( !$response->isSuccess() ) {
                return Redirect::redirect( '/admin/units' )
                    ->withMessage( 'error', $response->message ?? 'Erro ao deletar unidade.' );
            }

            // Log da atividade de exclusão
            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'unit_deleted',
                'unit',
                $unitId,
                "Unidade {$unitData->getName()} removida",
                [ 
                    'deleted_unit' => [ 
                        'id'           => $unitData->getId(),
                        'slug'         => $unitData->getSlug(),
                        'name'         => $unitData->getName(),
                        'abbreviation' => $unitData->getAbbreviation(),
                        'created_at'   => $unitData->getCreatedAt()->format( 'Y-m-d H:i:s' ),
                        'updated_at'   => $unitData->getUpdatedAt()?->format( 'Y-m-d H:i:s' )
                    ]
                ],
            );

            return Redirect::redirect( '/admin/units' )
                ->withMessage( 'success', 'Unidade removida com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao remover a unidade, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/units' );
        }
    }

    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}
