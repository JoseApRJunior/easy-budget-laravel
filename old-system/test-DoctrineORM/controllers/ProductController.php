<?php

namespace app\controllers;

use app\database\entitiesORM\ProductEntity;
use app\database\models\Product;
use app\database\servicesORM\ActivityService;
use app\request\ProductFormRequest;
use core\library\Response;
use core\library\Sanitize;
use core\library\Session;
use core\library\Twig;
use core\support\UploadImage;
use http\Redirect;
use http\Request;
use Throwable;

class ProductController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        protected Sanitize $sanitize,
        private Product $product,
        private UploadImage $uploadImage,
        protected ActivityService $activityService,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    public function index(): Response
    {
        return new Response(
            $this->twig->env->render( 'pages/product/index.twig' ),
        );
    }

    public function create(): Response
    {
        return new Response(
            $this->twig->env->render( 'pages/product/create.twig' ),
        );
    }

    public function store(): Response
    {
        try {
            // Validar os dados do formulário de criação de produto
            $validated = ProductFormRequest::validate( $this->request );

            // Se os dados não forem válidos, redirecionar para a página de criação de produto e mostrar a mensagem de erro
            if ( !$validated ) {
                return Redirect::redirect( '/provider/products/create' )->withMessage( 'error', 'Erro ao cadastrar o produto.' );
            }

            // Obter os dados do formulário de criação de produto
            $data = $this->request->All();

            // Popula ProductEntity com os dados do formulário
            $properties                = getConstructorProperties( ProductEntity::class);
            $properties[ 'tenant_id' ] = $this->authenticated->tenant_id;

            // Gera o código do produto no formato PROD000001
            $last_code = $this->product->getLastCode( $this->authenticated->tenant_id );
            if ( !$last_code[ 'success' ] ) {
                $code = 'PROD000001';
            } else {
                /** @var ProductEntity $last_code */
                $number = (int) substr( $last_code[ 'data' ]->code, 4 ) + 1;
                $code   = 'PROD' . str_pad( (string) $number, 6, '0', STR_PAD_LEFT );
            }

            $properties[ 'code' ] = $code;
            $data[ 'price' ]      = convertMoneyToFloat( $data[ 'price' ] );

            // Verificar se o campo de imagem está vazio
            if ( $this->request->hasFile( 'image' ) ) {
                $this->uploadImage->make( 'image' )
                    ->resize( 200, null, true )
                    ->execute();
                $info = $this->uploadImage->get_image_info();

                $data[ 'image' ] = $info[ 'path' ];
            } else {
                $data[ 'image' ] = null;
            }
            // popula model ProductEntity
            $entity = ProductEntity::create( removeUnnecessaryIndexes(
                $properties,
                [ 'id', 'created_at', 'updated_at' ],
                $data,
            ) );

            // Criar novo produto
            $response = $this->product->create( $entity );

            // Se não foi possível criar o novo produto, redirecionar para a página de criação e mostrar a mensagem de erro
            if ( $response[ 'status' ] === 'error' ) {
                return Redirect::redirect( '/provider/products/create' )->withMessage( 'error', "Falha ao cadastrar o produto, tente novamente mais tarde ou entre em contato com suporte!" );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'product_created',
                'product',
                $response[ 'data' ][ 'id' ],
                "Produto {$properties[ 'code' ]} criado",
                $response[ 'data' ],
            );

            // Redirecionar para a página de produtos e mostrar a mensagem de sucesso
            return Redirect::redirect( '/provider/products' )->withMessage( 'success', 'Produto criado com sucesso!' );

        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao criar o produto, tente novamente mais tarde ou entre em contato com suporte!" );

            return Redirect::redirect( '/provider/products/create' );
        }
    }

    public function update( string $code ): Response
    {
        $code    = $this->sanitize->sanitizeParamValue( $code, 'string' );
        $product = $this->product->getProductByCode( $code, $this->authenticated->tenant_id );

        return new Response(
            $this->twig->env->render( 'pages/product/update.twig', [ 
                'product' => $product,
            ] ),
        );
    }

    public function update_store(): Response
    {
        try {
            // Validar dados do formulário
            $validated = ProductFormRequest::validate( $this->request );

            // Obter os dados do formulário
            $data = $this->request->all();

            // Se os dados não forem válidos, redirecionar para a página de atualização do produto e mostrar a mensagem de erro
            if ( !$validated ) {
                return Redirect::redirect( '/provider/products/update/' . $data[ 'code' ] )->withMessage( 'error', 'Erro ao atualizar produto' );
            }

            // Buscar os dados do produto
            $product = $this->product->getProductById( $data[ 'id' ], $this->authenticated->tenant_id );

            // Verificar se o produto existe
            if ( !$product[ 'success' ] ) {
                return Redirect::redirect( '/provider/products' )->withMessage( 'error', 'Produto não encontrado.' );
            }

            // Converter o objeto para array
            $originalData = $product[ 'data' ]->toArray();

            // Processar o preço
            if ( isset( $data[ 'price' ] ) ) {
                $data[ 'price' ] = convertMoneyToFloat( $data[ 'price' ] );
            }

            // Tratamento da imagem
            if ( $this->request->hasFile( 'image' ) ) {
                // Upload da nova imagem
                $this->uploadImage->make( 'image' )
                    ->resize( 200, null, true )
                    ->execute();
                $info = $this->uploadImage->get_image_info();

                $data[ 'image' ] = $info[ 'path' ];

                // Se havia uma imagem anterior, excluir
                if ( !empty( $originalData[ 'image' ] ) && file_exists( PUBLIC_PATH . $originalData[ 'image' ] ) ) {
                    unlink( PUBLIC_PATH . $originalData[ 'image' ] );
                }
            } elseif ( isset( $data[ 'remove_image' ] ) && $data[ 'remove_image' ] == 1 ) {
                // Se o checkbox de remover imagem estiver marcado
                $data[ 'image' ] = null;

                // Excluir a imagem física se existir
                if ( !empty( $originalData[ 'image' ] ) && file_exists( PUBLIC_PATH . $originalData[ 'image' ] ) ) {
                    unlink( PUBLIC_PATH . $originalData[ 'image' ] );
                }
            } else {
                // Manter a imagem atual
                unset( $data[ 'image' ] );
            }

            // Remover campos desnecessários
            unset( $data[ 'remove_image' ] );

            // Popula ProductEntity com os dados do formulário
            $productEntity = ProductEntity::create( removeUnnecessaryIndexes(
                $originalData,
                [ 'created_at', 'updated_at' ],
                $data,
            ) );

            // Verificar se os dados do formulário foram alterados
            if ( !compareObjects( $product, $productEntity, [ 'created_at', 'updated_at' ] ) ) {
                // Atualizar ProductEntity com os dados do formulário
                $response = $this->product->update( $productEntity );

                // Se não foi possível atualizar o produto, redirecionar para a página de atualização e mostrar a mensagem de erro
                if ( $response[ 'status' ] === 'error' ) {
                    return Redirect::redirect( '/provider/products/update/' . $data[ 'code' ] )
                        ->withMessage( 'error', "Falha ao atualizar o produto, tente novamente mais tarde ou entre em contato com suporte!" );
                }

                $this->activityLogger(
                    $this->authenticated->tenant_id,
                    $this->authenticated->user_id,
                    'product_updated',
                    'product',
                    $data[ 'id' ],
                    "Produto {$originalData[ 'code' ]} atualizado",
                    [ 
                        'before' => $originalData,
                        'after'  => $productEntity->toArray(),
                    ],
                );
            }

            // Se tudo ocorreu bem, redirecionar para a página de produtos e mostrar a mensagem de sucesso
            return Redirect::redirect( '/provider/products' )
                ->withMessage( 'success', 'Produto atualizado com sucesso!' );

        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );

            // Obter o code do produto da requisição para redirecionar corretamente
            $code        = $this->request->get( 'code' );
            $redirectUrl = $code ? "/provider/products/update/{$code}" : "/provider/products";

            Session::flash( 'error', "Falha ao atualizar o produto, tente novamente mais tarde ou entre em contato com suporte!" );

            return Redirect::redirect( $redirectUrl );
        }
    }

    public function deactivate( string $code ): Response
    {
        try {
            $code = $this->sanitize->sanitizeParamValue( $code, 'string' );

            // Buscar os dados do produto
            $product = $this->product->getProductByCode( $code, $this->authenticated->tenant_id );
            // Verificar se o produto existe
            if ( !$product[ 'success' ] ) {
                return Redirect::redirect( '/provider/products' )->withMessage( 'error', 'Produto não encontrado.' );
            }
            /** @var ProductEntity $product */
            $relationships = $this->product->checkRelationships(
                $product[ 'data' ]->id,
                $this->authenticated->tenant_id,
                [ 'inventory_movements', 'product_inventory' ],
            );

            if ( $relationships[ 'error' ] ) {
                // Trata erro
                return Redirect::redirect( '/provider/products/show/' . $code )
                    ->withMessage( 'error', $relationships[ 'message' ] );
            }

            if ( $relationships[ 'hasRelationships' ] ) {
                $message = "Produto não pode ser desativado pois possui {$relationships[ 'count' ]} ";
                $message .= "{$relationships[ 'table' ]} vinculados.";

                return Redirect::redirect( '/provider/products/show/' . $code )
                    ->withMessage( 'error', $message );
            }

            // Converter o objeto para array
            $originalData = $product->toArray();

            // Popula UserEntity com os dados do formulário
            $productEntity = ProductEntity::create( removeUnnecessaryIndexes(
                $originalData,
                [ 'created_at', 'updated_at' ],
                [ 'active' => false ],
            ) );

            // Atualizar ProductEntity com os dados do formuláriorio
            $response = $this->product->update( $productEntity );
            if ( $response[ 'status' ] === 'error' ) {
                // Se houve erro, redirecionar com a mensagem adequada
                return Redirect::redirect( '/provider/products/show/' . $code )
                    ->withMessage( 'error', 'Falha ao desativar o produto, pode haver relações com outros registros, contate o suporte!' );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'product_updated',
                'product',
                $product->id,
                "Produto {$originalData[ 'code' ]} desativado!",
                $response[ 'data' ],
            );

            // Se tudo ocorreu bem, redirecionar para a página de atualização e mostrar a mensagem de sucesso
            return Redirect::redirect( '/provider/products/show/' . $code )
                ->withMessage( 'success', 'Produto desativado com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao desativar o produto, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/provider/products' );
        }
    }

    public function activate( string $code ): Response
    {
        try {
            $code = $this->sanitize->sanitizeParamValue( $code, 'string' );

            // Buscar os dados do produto
            $product = $this->product->getProductByCode( $code, $this->authenticated->tenant_id );
            // Verificar se o produto existe
            if ( !$product[ 'success' ] ) {
                return Redirect::redirect( '/provider/products' )->withMessage( 'error', 'Produto não encontrado.' );
            }
            // Converter o objeto para array
            $originalData = $product[ 'data' ]->toArray();

            // Popula UserEntity com os dados do formulário
            $productEntity = ProductEntity::create( removeUnnecessaryIndexes(
                $originalData,
                [ 'created_at', 'updated_at' ],
                [ 'active' => true ],
            ) );

            // Atualizar ProductEntity com os dados do formuláriorio
            $response = $this->product->update( $productEntity );
            if ( !$response[ 'success' ] ) {
                // Se houve erro, redirecionar com a mensagem adequada
                return Redirect::redirect( '/provider/products/show/' . $code )
                    ->withMessage( 'error', 'Falha ao ativar o produto, pode haver relações com outros registros, contate o suporte!' );
            }
            /** @var ProductEntity $product */

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'product_updated',
                'product',
                $product->id,
                "Produto {$originalData[ 'code' ]} ativado!",
                $response[ 'data' ],
            );

            // Se tudo ocorreu bem, redirecionar para a página de atualização e mostrar a mensagem de sucesso
            return Redirect::redirect( '/provider/products/show/' . $code )
                ->withMessage( 'success', 'Produto ativado com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao ativar o produto, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/provider/products' );
        }
    }

    public function delete_store( string $code ): Response
    {
        try {
            $code    = $this->sanitize->sanitizeParamValue( $code, 'string' );
            $product = $this->product->getProductByCode( $code, $this->authenticated->tenant_id );
            // Verificar se o produto existe
            if ( !$product[ 'success' ] ) {
                return Redirect::redirect( '/provider/products' )->withMessage( 'error', 'Produto não encontrado.' );
            }
            /** @var ProductEntity $product */
            $relationships = $this->product->checkRelationships(
                $product[ 'data' ]->id,
                $this->authenticated->tenant_id,
                [ 'inventory_movements', 'product_inventory' ],
            );

            if ( $relationships[ 'error' ] ) {
                // Trata erro
                return Redirect::redirect( '/provider/products' )
                    ->withMessage( 'error', $relationships[ 'message' ] );
            }

            if ( $relationships[ 'hasRelationships' ] ) {
                $message = "Produto não pode ser desativado pois possui {$relationships[ 'count' ]} ";
                $message .= "{$relationships[ 'table' ]} vinculados.";

                return Redirect::redirect( '/provider/products' )
                    ->withMessage( 'error', $message );
            }

            $response = $this->product->delete( $product->id, $this->authenticated->tenant_id );
            if ( $response[ 'status' ] === 'error' ) {
                // Se houve erro, redirecionar com a mensagem adequada
                return Redirect::redirect( '/provider/products' )
                    ->withMessage( 'error', 'Produto não pode ser deletado, pode haver relações com outros registros, contate o suporte!' );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'product_deleted',
                'product',
                $product->id,
                "Produto deletado com sucesso!",
                $response[ 'data' ],
            );

            // Se tudo ocorreu bem, redirecionar para a página de atualização e mostrar a mensagem de sucesso
            return Redirect::redirect( '/provider/products' )
                ->withMessage( 'success', 'Produto deletado com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao excluir o produto, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/provider/products' );
        }
    }

    public function show( string $code ): Response
    {
        $product = $this->product->getProductsWhithInventoryByCode(
            $this->sanitize->sanitizeParamValue( $code, 'string' ),
            $this->authenticated->tenant_id,
        );

        // Retornar a view do orçamento com os dados e services
        return new Response( $this->twig->env->render( 'pages/product/show.twig', [ 
            'product' => $product,
        ] ) );
    }

    /**
     * @inheritDoc
     */
    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}
