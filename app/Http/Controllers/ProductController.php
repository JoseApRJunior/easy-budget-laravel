<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador para gerenciamento de produtos e catálogo.
 * Implementa operações CRUD tenant-aware para produtos/serviços.
 * Migração do sistema legacy app/controllers/ProductController.php.
 *
 * @package App\Http\Controllers
 * @author IA
 */
class ProductController extends BaseController
{
    /**
     * @var ProductService
     */
    protected ProductService $productService;

    /**
     * @var CategoryService
     */
    protected CategoryService $categoryService;

    /**
     * Construtor da classe ProductController.
     *
     * @param ProductService $productService
     * @param CategoryService $categoryService
     */
    public function __construct(
        ProductService $productService,
        CategoryService $categoryService,
    ) {
        parent::__construct();
        $this->productService  = $productService;
        $this->categoryService = $categoryService;
    }

    /**
     * Exibe uma listagem dos produtos do tenant atual.
     *
     * @param Request $request
     * @return View
     */
    public function index( Request $request ): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $this->logActivity(
            action: 'view_products',
            entity: 'products',
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $filters = [ 
            'category_id'  => $request->get( 'category_id' ),
            'status'       => $request->get( 'status' ),
            'price_from'   => $request->get( 'price_from' ),
            'price_to'     => $request->get( 'price_to' ),
            'search'       => $request->get( 'search' ),
            'stock_status' => $request->get( 'stock_status' )
        ];

        $products = $this->productService->getProductsByTenant(
            tenantId: $tenantId,
            filters: $filters,
            perPage: 20,
            orderBy: $request->get( 'order_by', 'name' ),
            orderDirection: $request->get( 'order_direction', 'asc' ),
        );

        $categories = $this->categoryService->getCategoriesForFilter( $tenantId );
        $stats      = $this->productService->getProductStats( $tenantId );

        return $this->renderView( 'products.index', [ 
            'products'      => $products,
            'filters'       => $filters,
            'categories'    => $categories,
            'stats'         => $stats,
            'tenantId'      => $tenantId,
            'stockStatuses' => [ 'in_stock', 'low_stock', 'out_of_stock' ]
        ] );
    }

    /**
     * Mostra o formulário para criação de um novo produto.
     *
     * @return View
     */
    public function create(): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $this->logActivity(
            action: 'view_create_product',
            entity: 'products',
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $categories = $this->categoryService->getCategoriesForSelection( $tenantId );
        $units      = $this->productService->getAvailableUnits();

        return $this->renderView( 'products.create', [ 
            'categories' => $categories,
            'units'      => $units,
            'tenantId'   => $tenantId
        ] );
    }

    /**
     * Armazena um novo produto no banco de dados.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store( Request $request ): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $request->validate( [ 
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:1000',
            'category_id'    => 'required|integer|exists:categories,id',
            'sku'            => 'nullable|string|max:100|unique:products,sku,NULL,id,tenant_id,' . $tenantId,
            'price'          => 'required|numeric|min:0',
            'cost_price'     => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'unit_id'        => 'nullable|integer|exists:units,id',
            'is_active'      => 'boolean',
            'track_stock'    => 'boolean',
            'images'         => 'nullable|array|max:5',
            'images.*'       => 'image|mimes:jpeg,png,jpg|max:2048'
        ] );

        $productData              = $request->validated();
        $productData[ 'tenant_id' ] = $tenantId;
        $productData[ 'is_active' ] = $request->boolean( 'is_active', true );

        // Processar upload de imagens
        if ( $request->hasFile( 'images' ) ) {
            $imagePaths = [];
            foreach ( $request->file( 'images' ) as $image ) {
                $path         = $image->store( 'product_images', 'public' );
                $imagePaths[] = $path;
            }
            $productData[ 'images' ] = json_encode( $imagePaths );
        }

        $result = $this->productService->createProduct( $productData );

        return $this->handleServiceResult(
            result: $result,
            request: $request,
            successMessage: 'Produto criado com sucesso.',
            errorMessage: 'Erro ao criar produto.',
        );
    }

    /**
     * Exibe o produto específico.
     *
     * @param int $id
     * @return View
     */
    public function show( int $id ): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $product = $this->productService->getProductById( $id, $tenantId );

        if ( !$product ) {
            return $this->errorRedirect( 'Produto não encontrado.' );
        }

        $this->logActivity(
            action: 'view_product',
            entity: 'products',
            entityId: $id,
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $relatedProducts = $this->productService->getRelatedProducts(
            productId: $id,
            categoryId: $product->category_id,
            limit: 4,
        );

        $category = $this->categoryService->getCategoryById( $product->category_id, $tenantId );

        return $this->renderView( 'products.show', [ 
            'product'         => $product,
            'relatedProducts' => $relatedProducts,
            'category'        => $category,
            'tenantId'        => $tenantId
        ] );
    }

    /**
     * Mostra o formulário para edição do produto.
     *
     * @param int $id
     * @return View
     */
    public function edit( int $id ): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $product = $this->productService->getProductById( $id, $tenantId );

        if ( !$product ) {
            return $this->errorRedirect( 'Produto não encontrado.' );
        }

        $this->logActivity(
            action: 'view_edit_product',
            entity: 'products',
            entityId: $id,
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $categories = $this->categoryService->getCategoriesForSelection( $tenantId );
        $units      = $this->productService->getAvailableUnits();

        return $this->renderView( 'products.edit', [ 
            'product'    => $product,
            'categories' => $categories,
            'units'      => $units,
            'tenantId'   => $tenantId
        ] );
    }

    /**
     * Atualiza o produto no banco de dados.
     *
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update( Request $request, int $id ): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $existingProduct = $this->productService->getProductById( $id, $tenantId );

        if ( !$existingProduct ) {
            return $this->errorRedirect( 'Produto não encontrado.' );
        }

        $request->validate( [ 
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:1000',
            'category_id'    => 'required|integer|exists:categories,id',
            'sku'            => 'nullable|string|max:100|unique:products,sku,' . $id . ',id,tenant_id,' . $tenantId,
            'price'          => 'required|numeric|min:0',
            'cost_price'     => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'unit_id'        => 'nullable|integer|exists:units,id',
            'is_active'      => 'boolean',
            'track_stock'    => 'boolean',
            'images'         => 'nullable|array|max:5',
            'images.*'       => 'image|mimes:jpeg,png,jpg|max:2048'
        ] );

        $productData              = $request->validated();
        $productData[ 'tenant_id' ] = $tenantId;
        $productData[ 'is_active' ] = $request->boolean( 'is_active', true );

        // Processar upload de novas imagens
        if ( $request->hasFile( 'images' ) ) {
            $imagePaths = [];
            foreach ( $request->file( 'images' ) as $image ) {
                $path         = $image->store( 'product_images', 'public' );
                $imagePaths[] = $path;
            }

            // Manter imagens existentes se não todas foram substituídas
            $existingImages = json_decode( $existingProduct->images, true ) ?? [];
            if ( !empty( $existingImages ) && count( $imagePaths ) < 5 ) {
                $productData[ 'images' ] = json_encode( array_merge( $existingImages, $imagePaths ) );
            } else {
                $productData[ 'images' ] = json_encode( $imagePaths );
            }
        }

        $result = $this->productService->updateProduct( $id, $productData );

        return $this->handleServiceResult(
            result: $result,
            request: $request,
            successMessage: 'Produto atualizado com sucesso.',
            errorMessage: 'Erro ao atualizar produto.',
        );
    }

    /**
     * Remove o produto do banco de dados.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy( int $id ): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $existingProduct = $this->productService->getProductById( $id, $tenantId );

        if ( !$existingProduct ) {
            return $this->errorRedirect( 'Produto não encontrado.' );
        }

        // Verifica se o produto está sendo usado em orçamentos ou faturas
        if ( $this->productService->isProductInUse( $id ) ) {
            return $this->errorRedirect( 'Este produto está sendo usado em orçamentos ou faturas e não pode ser excluído.' );
        }

        $result = $this->productService->deleteProduct( $id );

        return $this->handleServiceResult(
            result: $result,
            request: request(),
            successMessage: 'Produto excluído com sucesso.',
            errorMessage: 'Erro ao excluir produto.',
        );
    }

    /**
     * Atualiza estoque do produto.
     *
     * @param int $id
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function updateStock( int $id, Request $request ): RedirectResponse|JsonResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Tenant não encontrado.', statusCode: 403 );
            }
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $existingProduct = $this->productService->getProductById( $id, $tenantId );

        if ( !$existingProduct ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Produto não encontrado.', statusCode: 404 );
            }
            return $this->errorRedirect( 'Produto não encontrado.' );
        }

        $request->validate( [ 
            'stock_quantity'  => 'required|integer|min:0',
            'adjustment_type' => 'nullable|in:add,subtract',
            'reason'          => 'nullable|string|max:500'
        ] );

        $result = $this->productService->updateProductStock(
            productId: $id,
            newQuantity: $request->stock_quantity,
            adjustmentType: $request->adjustment_type ?? 'set',
            reason: $request->reason,
        );

        if ( request()->expectsJson() ) {
            if ( $result->isSuccess() ) {
                $this->logActivity(
                    action: 'update_product_stock',
                    entity: 'products',
                    entityId: $id,
                    metadata: [ 
                        'tenant_id'       => $tenantId,
                        'new_stock'       => $request->stock_quantity,
                        'adjustment_type' => $request->adjustment_type ?? 'set'
                    ],
                );

                return $this->jsonSuccess(
                    data: $result->getData(),
                    message: 'Estoque atualizado com sucesso.',
                );
            }

            return $this->jsonError(
                message: $result->getError() ?? 'Erro ao atualizar estoque.',
                statusCode: 422,
            );
        }

        return $this->handleServiceResult(
            result: $result,
            request: $request,
            successMessage: 'Estoque atualizado com sucesso.',
            errorMessage: 'Erro ao atualizar estoque.',
        );
    }

    /**
     * Busca produtos por nome ou SKU via AJAX.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search( Request $request ): JsonResponse
    {
        $request->validate( [ 
            'query'       => 'required|string|max:100',
            'limit'       => 'nullable|integer|min:1|max:50',
            'category_id' => 'nullable|integer|exists:categories,id'
        ] );

        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->jsonError( 'Tenant não encontrado.', statusCode: 403 );
        }

        $products = $this->productService->searchProducts(
            query: $request->query,
            tenantId: $tenantId,
            categoryId: $request->category_id,
            limit: $request->get( 'limit', 10 ),
            includeOutOfStock: $request->boolean( 'include_out_of_stock', false ),
        );

        $this->logActivity(
            action: 'search_products',
            entity: 'products',
            metadata: [ 
                'tenant_id'     => $tenantId,
                'search_query'  => $request->query,
                'results_count' => $products->count(),
                'category_id'   => $request->category_id
            ],
        );

        return $this->jsonSuccess(
            data: $products,
            message: 'Produtos encontrados com sucesso.',
        );
    }

    /**
     * Obtém produtos para seleção em dropdowns.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getForSelection( Request $request ): JsonResponse
    {
        $request->validate( [ 
            'category_id'      => 'nullable|integer|exists:categories,id',
            'limit'            => 'nullable|integer|min:1|max:100',
            'include_inactive' => 'nullable|boolean'
        ] );

        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->jsonError( 'Tenant não encontrado.', statusCode: 403 );
        }

        $products = $this->productService->getProductsForSelection(
            tenantId: $tenantId,
            categoryId: $request->category_id,
            limit: $request->get( 'limit', 50 ),
            includeInactive: $request->boolean( 'include_inactive', false ),
        );

        return $this->jsonSuccess(
            data: $products,
            message: 'Produtos carregados com sucesso.',
        );
    }

    /**
     * Importa produtos de arquivo CSV.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function import( Request $request ): RedirectResponse|JsonResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Tenant não encontrado.', statusCode: 403 );
            }
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $request->validate( [ 
            'csv_file'        => 'required|file|mimes:csv,txt|max:10240',
            'category_id'     => 'nullable|integer|exists:categories,id',
            'update_existing' => 'nullable|boolean'
        ] );

        try {
            $importData = [ 
                'tenant_id'       => $tenantId,
                'category_id'     => $request->category_id,
                'update_existing' => $request->boolean( 'update_existing', false ),
                'file_path'       => $request->file( 'csv_file' )->store( 'product_imports', 'public' )
            ];

            $result = $this->productService->importProductsFromCsv( $importData );

            if ( request()->expectsJson() ) {
                if ( $result->isSuccess() ) {
                    $this->logActivity(
                        action: 'import_products',
                        entity: 'products',
                        metadata: [ 
                            'tenant_id'      => $tenantId,
                            'imported_count' => $result->getData()[ 'imported_count' ] ?? 0,
                            'errors_count'   => $result->getData()[ 'errors_count' ] ?? 0
                        ],
                    );

                    return $this->jsonSuccess(
                        data: $result->getData(),
                        message: 'Importação concluída com sucesso.',
                    );
                }

                return $this->jsonError(
                    message: $result->getError() ?? 'Erro durante a importação.',
                    statusCode: 422,
                );
            }

            return $this->handleServiceResult(
                result: $result,
                request: $request,
                successMessage: 'Importação concluída com sucesso.',
                errorMessage: 'Erro durante a importação.',
            );

        } catch ( \Exception $e ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Erro ao processar arquivo de importação: ' . $e->getMessage(), statusCode: 422 );
            }
            return $this->errorRedirect( 'Erro ao processar arquivo de importação: ' . $e->getMessage() );
        }
    }

    /**
     * Exporta produtos para CSV.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function export( Request $request ): \Illuminate\Http\Response
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            abort( 403, 'Tenant não encontrado.' );
        }

        $filters = [ 
            'category_id'      => $request->get( 'category_id' ),
            'status'           => $request->get( 'status' ),
            'include_inactive' => $request->boolean( 'include_inactive', false )
        ];

        $csvContent = $this->productService->exportProductsToCsv(
            tenantId: $tenantId,
            filters: $filters,
        );

        $filename = 'produtos_' . date( 'Y-m-d_H-i-s' ) . '.csv';
        $headers  = [ 
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0'
        ];

        $this->logActivity(
            action: 'export_products',
            entity: 'products',
            metadata: [ 
                'tenant_id'        => $tenantId,
                'category_id'      => $filters[ 'category_id' ],
                'status'           => $filters[ 'status' ],
                'include_inactive' => $filters[ 'include_inactive' ]
            ],
        );

        return response( $csvContent, 200, $headers );
    }

}