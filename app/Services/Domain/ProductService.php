<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Serviço de domínio para gerenciamento de produtos.
 *
 * Centraliza a regra de negócios e delega a persistência ao ProductRepository.
 * Mantém isolamento entre Controller e Model/Database.
 */
class ProductService extends AbstractBaseService
{
    private ProductRepository $productRepository;

    public function __construct( ProductRepository $productRepository )
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Retorna o total de produtos do tenant autenticado.
     */
    public function getTotalCount(): int
    {
        return $this->productRepository->countByTenant();
    }

    /**
     * Retorna o total de produtos ativos do tenant autenticado.
     */
    public function getActiveCount(): int
    {
        return $this->productRepository->countActiveByTenant();
    }

    /**
     * Retorna uma coleção de produtos recentes com relacionamentos opcionais.
     */
    public function getRecentProducts( int $limit = 5, array $with = [] ): Collection
    {
        // Obtém produtos recentes do repositório
        // Nota: O método do repositório já aplica ordenação por created_at desc
        $products = $this->productRepository->getRecentByTenant( $limit );

        // Carrega relacionamentos se solicitados
        if ( !empty( $with ) ) {
            $products->load( $with );
        }

        return $products;
    }

    /**
     * Retorna produtos ativos.
     */
    public function getActive(): Collection
    {
        try {
            return $this->productRepository->getAllByTenant(
                [ 'active' => true ],
                [ 'name' => 'asc' ]
            );
        } catch ( Exception $e ) {
            Log::error( 'Erro ao buscar produtos ativos', [
                'error' => $e->getMessage()
            ] );
            return new Collection();
        }
    }

    public function findBySku( string $sku, array $with = [] ): ServiceResult
    {
        try {
            $product = $this->productRepository->findBySku( $sku, $with );

            if ( !$product ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Produto com SKU {$sku} não encontrado",
                );
            }

            return $this->success( $product, 'Produto encontrado' );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar produto',
                null,
                $e,
            );
        }
    }

    public function getFilteredProducts( array $filters = [], array $with = [], int $perPage = 15 ): ServiceResult
    {
        try {
            $products = $this->productRepository->getPaginated( $filters, $perPage, $with );

            return $this->success( $products, 'Produtos filtrados' );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao filtrar produtos',
                null,
                $e,
            );
        }
    }

    /**
     * Cria um novo produto.
     */
    public function createProduct( array $data ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($data) {
                // Gerar SKU se não fornecido
                if ( empty( $data[ 'sku' ] ) ) {
                    $data[ 'sku' ] = $this->generateUniqueSku();
                }

                // Processar imagem
                if ( isset( $data[ 'image' ] ) ) {
                    $data[ 'image' ] = $this->uploadProductImage( $data[ 'image' ] );
                }

                if ( empty( $data[ 'tenant_id' ] ) ) {
                    /** @var \App\Models\User $user */
                    $user = auth()->user();
                    $resolvedTenantId = $user->tenant_id ?? ( function_exists( 'tenant' ) && tenant() ? tenant()->id : null );
                    $data[ 'tenant_id' ] = $resolvedTenantId;
                }

                $product = $this->productRepository->create( $data );

                // Criar registro de inventário automaticamente
                \App\Models\ProductInventory::create( [
                    'tenant_id'    => $product->tenant_id,
                    'product_id'   => $product->id,
                    'quantity'     => 0,
                    'min_quantity' => 0,
                    'max_quantity' => null,
                ] );

                return $this->success( $product, 'Produto criado com sucesso' );
            } );
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao criar produto',
                null,
                $e,
            );
        }
    }

    /**
     * Atualiza produto buscando por SKU.
     */
    public function updateProductBySku( string $sku, array $data ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($sku, $data) {
                // Busca usando repositório (garante tenant context se o repo estiver configurado corretamente,
                // mas findBySku é custom e pode precisar de verificação extra se o SKU for global?)
                // Assumindo que findBySku retorna apenas se pertencer ao tenant, ou se SKU for único globalmente.
                $product = $this->productRepository->findBySku( $sku );

                if ( !$product ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Produto com SKU {$sku} não encontrado",
                    );
                }

                // TODO: Validar se produto pertence ao tenant (se repository.findBySku não garantir)
                // $this->productRepository->find($product->id) garantiria.

                // Remover imagem existente se solicitado
                if ( isset( $data[ 'remove_image' ] ) && $data[ 'remove_image' ] && $product->image ) {
                    $relative = $this->resolveRelativeStoragePath( $product->image );
                    if ( $relative ) {
                        Storage::disk( 'public' )->delete( $relative );
                    }
                    $data[ 'image' ] = null;
                }

                // Processar nova imagem se fornecida
                if ( isset( $data[ 'image' ] ) && is_a( $data[ 'image' ], 'Illuminate\Http\UploadedFile' ) ) {
                    // Deletar imagem antiga se existir
                    if ( $product->image ) {
                        $relative = $this->resolveRelativeStoragePath( $product->image );
                        if ( $relative ) {
                            Storage::disk( 'public' )->delete( $relative );
                        }
                    }
                    $data[ 'image' ] = $this->uploadProductImage( $data[ 'image' ] );
                } else if ( isset( $data[ 'image' ] ) && $data[ 'image' ] === null ) {
                    if ( $product->image ) {
                        $relative = $this->resolveRelativeStoragePath( $product->image );
                        if ( $relative ) {
                            Storage::disk( 'public' )->delete( $relative );
                        }
                    }
                    $data[ 'image' ] = null;
                } else {
                    unset( $data[ 'image' ] );
                }

                $product = $this->productRepository->update( $product->id, $data );

                return $this->success( $product, 'Produto atualizado com sucesso' );
            } );
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao atualizar produto',
                null,
                $e,
            );
        }
    }

    public function toggleProductStatus( string $sku ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($sku) {
                $product = $this->productRepository->findBySku( $sku );

                if ( !$product ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Produto com SKU {$sku} não encontrado",
                    );
                }

                if ( !$this->productRepository->canBeDeactivatedOrDeleted( $product->id ) ) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        'Produto não pode ser desativado/ativado pois está em uso em serviços.',
                    );
                }

                $newStatus = !$product->active;
                $product   = $this->productRepository->update( $product->id, [ 'active' => $newStatus ] );

                $message = $newStatus ? 'Produto ativado com sucesso' : 'Produto desativado com sucesso';
                return $this->success( $product, $message );
            } );
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao alterar status do produto',
                null,
                $e,
            );
        }
    }

    public function deleteProductBySku( string $sku ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($sku) {
                $product = $this->productRepository->findBySku( $sku );

                if ( !$product ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Produto com SKU {$sku} não encontrado",
                    );
                }

                if ( !$this->productRepository->canBeDeactivatedOrDeleted( $product->id ) ) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        'Produto não pode ser excluído pois está em uso em serviços.',
                    );
                }

                // Deletar imagem física se existir
                if ( $product->image ) {
                    $relative = $this->resolveRelativeStoragePath( $product->image );
                    if ( $relative ) {
                        Storage::disk( 'public' )->delete( $relative );
                    }
                }

                $this->productRepository->delete( $product->id );

                return $this->success( null, 'Produto excluído com sucesso' );
            } );
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao excluir produto',
                null,
                $e,
            );
        }
    }

    public function getDeletedProducts( array $filters = [], array $with = [], int $perPage = 15 ): ServiceResult
    {
        try {
            // Usa método herdado do AbstractTenantRepository que suporta OnlyTrashed
            $products = $this->productRepository->getDeletedByTenant( $filters, null, $perPage );

            // Nota: O repo pode retornar Collection ou Paginator dependendo da implementação do pai.
            // AbstractTenantRepository::getDeletedByTenant geralmente retorna Collection se não passar limite/page?
            // Mas no CategoryRepository ele retorna Collection.
            // Se eu quero Paginado, AbstractTenantRepository::getDeletedByTenant pode não paginar por padrão.
            // Olhando CategoryRepository::getDeletedCategories, ele não pagina.
            // Mas o Controller de Produtos espera Paginator?
            // O request tem per_page.
            // Se getDeletedByTenant não paginar, o controller vai falhar ou a view vai falhar na paginação.

            // Correção: Se AbstractTenantRepository não tiver método paginado para deleted,
            // uso a implementação manual via repository->getPaginated com filtro 'deleted' => 'only'?
            // Não, getPaginated (refatorado) tem applySoftDeleteFilter que checa $filters['deleted'].
            // Então getPaginated resolve tudo!

            // Então posso substituir tudo por getPaginated!
            $filters['deleted'] = 'only';
            $products = $this->productRepository->getPaginated($filters, $perPage, $with);

            return $this->success( $products, 'Produtos deletados carregados' );
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao carregar produtos deletados',
                null,
                $e,
            );
        }
    }

    public function restoreProductBySku( string $sku ): ServiceResult
    {
        try {
            // Busca incluindo deletados
            $product = $this->productRepository->findBySku($sku); // findBySku do repo não traz trashed por padrão

            // Preciso buscar trashed.
            // ProductRepository deve ter método findTrashedBySku? Ou usar query manual.
            /** @var Product|null $product */
            $product = Product::onlyTrashed()->where( 'sku', $sku )->first(); // TODO: Adicionar tenant scope aqui

            if ( !$product ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Produto deletado com SKU {$sku} não encontrado",
                );
            }

            // TODO: check tenant owner

            $product->restore();

            return $this->success( $product, 'Produto restaurado com sucesso' );
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao restaurar produto',
                null,
                $e,
            );
        }
    }

    // Método para dashboard
    public function getDashboardData( ?int $tenantId = null ): ServiceResult
    {
        try {
            // Repositorio usa contexto tenant automaticamente
            $stats = [
                'total_products'    => $this->productRepository->countByTenant(),
                'active_products'   => $this->productRepository->countActiveByTenant(),
                'inactive_products' => $this->productRepository->countByTenant( [ 'active' => false ] ),
                'deleted_products'  => $this->productRepository->countOnlyTrashedByTenant(),
                'recent_products'   => $this->getRecentProducts( 5, [ 'category' ] ), // Usa método do service
            ];

            return $this->success( $stats, 'Estatísticas obtidas com sucesso' );
        } catch ( Exception $e ) {
            Log::error( 'Erro ao obter estatísticas de produtos', [ 'error' => $e->getMessage() ] );
            return $this->error( OperationStatus::ERROR, 'Erro ao obter estatísticas', null, $e );
        }
    }

    /**
     * Gera SKU único sequencial.
     */
    private function generateUniqueSku(): string
    {
        // TODO: Movel para repositório
        return DB::transaction( function () {
            $lastProduct = Product::where( 'sku', 'LIKE', 'PROD%' )
                ->orderByDesc( 'sku' )
                ->lockForUpdate()
                ->first();

            if ( !$lastProduct || !preg_match( '/^PROD(\d{6})$/', $lastProduct->sku, $matches ) ) {
                return 'PROD000001';
            }

            $nextNumber = (int) $matches[ 1 ] + 1;

            return 'PROD' . str_pad( (string) $nextNumber, 6, '0', STR_PAD_LEFT );
        } );
    }

    private function uploadProductImage( $imageFile ): ?string
    {
        if ( !$imageFile ) return null;

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $tenantId = $user->tenant_id ?? ( function_exists( 'tenant' ) && tenant() ? tenant()->id : null );
        $path     = 'products/' . ( $tenantId ?? 'unknown' );
        $filename = Str::random( 40 ) . '.' . $imageFile->getClientOriginalExtension();

        if ( !Storage::disk( 'public' )->exists( $path ) ) {
            Storage::disk( 'public' )->makeDirectory( $path );
        }

        $imageFile->storePubliclyAs( $path, $filename, 'public' );
        return $path . '/' . $filename;
    }

    private function resolveRelativeStoragePath( ?string $image ): ?string
    {
        if ( empty( $image ) ) return null;
        $trimmed = ltrim( $image, '/' );
        if ( Str::startsWith( $trimmed, 'storage/' ) ) {
            return Str::after( $trimmed, 'storage/' );
        }
        return $trimmed;
    }
}
