<?php
declare(strict_types=1);

namespace app\controllers\admin;

use app\controllers\AbstractController;
use app\database\entitiesORM\CategoryEntity;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\CategoryService;
use app\enums\OperationStatus;
use app\request\CategoryFormRequest;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Sanitize;
use core\library\Session;
use core\library\Twig;
use http\Redirect;
use http\Request;
use Throwable;

/**
 * Controller para gerenciamento de categorias
 */
class CategoryController extends AbstractController
{

    /**
     * Construtor da classe CategoryController
     *
     * @param Twig $twig Serviço de template
     * @param CategoryService $categoryService Serviço de categorias
     * @param Sanitize $sanitize Serviço de sanitização
     * @param Request $request Requisição HTTP
     */
    public function __construct(
        protected Twig $twig,
        private CategoryService $categoryService,
        protected Sanitize $sanitize,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    /**
     * Exibe a lista de categorias
     *
     * @return Response
     */
    public function index(): Response
    {
        try {
            // Buscar todas as categorias usando o CategoryService
            $categories = $this->categoryService->list();

            if ($categories->isSuccess()) {
                return new Response( $this->twig->env->render( 'pages/category/index.twig', [
                    'categories' => $categories->data,
                ] ) );
            } else {
                return new Response( $this->twig->env->render( 'pages/category/index.twig', [
                    'categories' => [],
                ] ), 404 );
            }
        } catch ( Throwable $e ) {
            // Em caso de erro, renderizar a página com array vazio e mensagem de erro
            Session::flash( 'error', 'Erro ao carregar as categorias. Tente novamente mais tarde.' );
            return Redirect::redirect( '/admin/categories' )
                ->withMessage( 'error', $e->getMessage() );
        }
    }

    /**
     * Exibe o formulário de criação de categoria
     *
     * @return Response
     */
    public function create(): Response
    {
        return new Response( $this->twig->env->render( 'pages/category/create.twig' ) );
    }

    /**
     * Processa o formulário de criação de categoria
     *
     * @return Response
     */
    public function store(): Response
    {
        try {
            // Validar os dados do formulário
            $validated = CategoryFormRequest::validate( $this->request );

            // Se os dados não forem válidos, redirecionar para a página de criação
            if ( !$validated ) {
                return Redirect::redirect( '/admin/categories/create' )
                    ->withMessage( 'error', 'Erro ao cadastrar a categoria.' );
            }

            // Obter e sanitizar automaticamente os dados do formulário com base na entidade CategoryEntity
            $data     = $this->autoSanitizeForEntity( $this->request->all(), CategoryEntity::class);
            $response = $this->categoryService->create( $data );

            // Se houve erro, redirecionar com a mensagem adequada
            if ( !$response->isSuccess() ) {
                return Redirect::redirect( '/admin/categories/create' )
                    ->withMessage( 'error', $response->message ?? 'Erro ao cadastrar a categoria.' );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'category_created',
                'category',
                $response->data->getId(),
                "Categoria {$data[ 'name' ]} criada",
                [
                    'entity' => $response->data->jsonSerialize()
                ],
            );

            // Se tudo ocorreu bem, redirecionar para a lista de categorias
            return Redirect::redirect( '/admin/categories' )
                ->withMessage( 'success', 'Categoria cadastrada com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao cadastrar a categoria, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/categories/create' );
        }
    }

    /**
     * Exibe os detalhes de uma categoria
     *
     * @param string $id ID da categoria
     * @return Response
     */
    public function show( string $id ): Response
    {
        // Sanitizar o ID usando o método do trait AutoSanitizationTrait
        $params           = $this->autoSanitizeForEntity( [ 'id' => $id ], CategoryEntity::class);
        $categoryResponse = $this->categoryService->getById( $params[ 'id' ] );

        if ( !$categoryResponse->isSuccess() ) {
            return Redirect::redirect( '/admin/categories' )
                ->withMessage( 'error', $categoryResponse->message ?? 'Categoria não encontrada.' );
        }

        $category = $categoryResponse->data;

        return new Response( $this->twig->env->render( 'pages/category/show.twig', [
            'category' => $category,
        ] ) );
    }

    /**
     * Exibe o formulário de edição de categoria
     *
     * @param string $id ID da categoria
     * @return Response
     */
    public function edit( string $id ): Response
    {
        // Sanitizar o ID usando o método do trait AutoSanitizationTrait
        $params           = $this->autoSanitizeForEntity( [ 'id' => $id ], CategoryEntity::class);
        $categoryResponse = $this->categoryService->getById( $params[ 'id' ] );

        if ( !$categoryResponse->isSuccess() ) {
            return Redirect::redirect( '/admin/categories' )
                ->withMessage( 'error', $categoryResponse->message ?? 'Categoria não encontrada.' );
        }

        $category = $categoryResponse->data;

        return new Response( $this->twig->env->render( 'pages/category/edit.twig', [
            'category' => $category,
        ] ) );
    }

    /**
     * Processa o formulário de edição de categoria
     *
     * @return Response
     */
    public function update(): Response
    {
        $requestData = $this->request->all();
        try {
            // Validar os dados do formulário
            $validated = CategoryFormRequest::validate( $this->request );
            $id        = $requestData[ 'id' ];

            // Se os dados não forem válidos, redirecionar para a página de edição
            if ( !$validated ) {
                return Redirect::redirect( "/admin/categories/edit/{$id}" )
                    ->withMessage( 'error', 'Erro ao atualizar a categoria.' );
            }

            // Sanitizar o ID manualmente para garantir que seja um inteiro válido
            $id = $this->sanitize->sanitizeParamValue( $id, 'int' );

            // Sanitizar automaticamente os dados do formulário com base na entidade CategoryEntity
            $data     = $this->autoSanitizeForEntity( $requestData, CategoryEntity::class);
            $response = $this->categoryService->update( $id, $data );

            // Se houve erro, redirecionar com a mensagem adequada
            if ( !$response->isSuccess() ) {
                return Redirect::redirect( "/admin/categories/edit/{$id}" )
                    ->withMessage( 'error', $response->message ?? 'Erro ao atualizar a categoria.' );
            }

            // Log da atividade de atualização
            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'category_updated',
                'category',
                $response->data->getId(),
                "Categoria {$data[ 'name' ]} atualizada",
                [
                    'entity' => $response->data->jsonSerialize()
                ],
            );

            // Se tudo ocorreu bem, redirecionar para a lista de categorias
            return Redirect::redirect( '/admin/categories' )
                ->withMessage( 'success', 'Categoria atualizada com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao atualizar a categoria, tente novamente mais tarde ou entre em contato com suporte!" );
            $fallbackId = $requestData[ 'id' ] ?? '';
            return Redirect::redirect( "/admin/categories/edit/{$fallbackId}" );
        }
    }

    /**
     * Exclui uma categoria
     *
     * @param string $id ID da categoria
     * @return Response
     */
    public function delete( string $id ): Response
    {
        try {
            // Sanitizar o ID usando o método do trait AutoSanitizationTrait
            $params     = $this->autoSanitizeForEntity( [ 'id' => $id ], CategoryEntity::class);
            $categoryId = $params[ 'id' ];

            // Buscar dados da categoria antes de deletar para o log
            $categoryResponse = $this->categoryService->getById( $categoryId );

            if ( !$categoryResponse->isSuccess() ) {
                return Redirect::redirect( '/admin/categories' )
                    ->withMessage( 'error', $categoryResponse->message ?? 'Categoria não encontrada.' );
            }

            $categoryData = $categoryResponse->data;

            $response = $this->categoryService->delete( $categoryId );

            if ( !$response->isSuccess() ) {
                return Redirect::redirect( '/admin/categories' )
                    ->withMessage( 'error', $response->message ?? 'Erro ao deletar categoria.' );
            }

            // Log da atividade de exclusão
            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'category_deleted',
                'category',
                $categoryId,
                "Categoria {$categoryData->getName()} removida",
                [
                    'deleted_category' => [
                        'id'         => $categoryData->getId(),
                        'slug'       => $categoryData->getSlug(),
                        'name'       => $categoryData->getName(),
                        'created_at' => $categoryData->getCreatedAt()->format( 'Y-m-d H:i:s' ),
                        'updated_at' => $categoryData->getUpdatedAt()?->format( 'Y-m-d H:i:s' )
                    ]
                ],
            );

            return Redirect::redirect( '/admin/categories' )
                ->withMessage( 'success', 'Categoria removida com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao remover a categoria, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/categories' );
        }
    }

}
